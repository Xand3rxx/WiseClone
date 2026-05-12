<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CurrencyBalanceRequest;
use App\Http\Requests\SourceConverterRequest;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Charge;
use App\Models\Currency;
use App\Models\IdempotencyKey;
use App\Models\Transaction;
use App\Models\TransferQuote;
use App\Models\User;
use App\Services\IdempotencyService;
use App\Services\LedgerService;
use App\Services\TransferQuoteService;
use App\Support\Money;
use App\Traits\CanPay;
use App\Traits\ExchangeRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TransactionController extends Controller
{
    use CanPay, ExchangeRate;

    public function __construct(
        private readonly LedgerService $ledgerService,
        private readonly TransferQuoteService $transferQuoteService,
        private readonly IdempotencyService $idempotencyService
    ) {}

    /**
     * Display a listing of transactions.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();
        $transactions = $user->isAdmin()
            ? Transaction::with(['user', 'recipient', 'sourceCurrency', 'targetCurrency'])
                ->latest()
                ->paginate(50)
            : Transaction::forUser($user->id)
                ->with(['user', 'recipient', 'sourceCurrency', 'targetCurrency'])
                ->latest()
                ->paginate(50);

        return view('application.transactions.index', compact('transactions'));
    }

    /**
     * Show the form for creating a new transaction.
     */
    public function create(Request $request): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $sourceCurrency = $user->currency;
        $sourceAmount = $this->ledgerService->balanceFor($user, $sourceCurrency);

        return $this->converter($sourceCurrency, $sourceCurrency, $sourceAmount, 'application.transactions.create', $user);
    }

    /**
     * Store a newly created transaction in storage.
     */
    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $amount = Money::round($validated['source_amount']);
        $currency = Currency::findOrFail($validated['source_currency_id']);
        /** @var User $user */
        $user = $request->user();
        $idempotency = $this->idempotencyService->start($user->id, 'transfer.store', $validated['idempotency_key'], $validated);

        if ($idempotency->status === IdempotencyKey::STATUS_COMPLETED) {
            return redirect()->route($idempotency->response_payload['route'] ?? 'home')
                ->with($idempotency->response_payload['flash_type'] ?? 'success', $idempotency->response_payload['message'] ?? 'Your transaction was already processed.');
        }

        // Prevent self-transfer
        $recipient = User::where('uuid', $validated['recipient_uuid'])->first();
        if ($recipient && $recipient->id === $user->id) {
            return back()->with('error', 'Sorry! You cannot transfer money to yourself.');
        }

        // Check if recipient has a currency balance (required for credit)
        if ($recipient && ! $recipient->latestCurrencyBalance) {
            return back()->with('error', 'Sorry! The recipient account is not properly set up. Please contact support.');
        }

        // Check if user can make payment (has sufficient funds)
        if (! $this->canMakePayment($currency->code, $amount)) {
            return back()->with('error', "Oops! Your {$currency->name} account balance is insufficient to complete this transaction.");
        }

        // Validate minimum amount
        if (Money::isLessThan($amount, '0.01')) {
            return back()->with('error', "Sorry! The source amount cannot be less than {$currency->symbol}0.01.");
        }

        // Calculate the target amount to be sent to recipient
        try {
            $quote = $this->transferQuoteService->usableQuoteFor($user, $validated['quote_uuid']);
        } catch (\Exception $e) {
            $this->idempotencyService->fail($idempotency, $e->getMessage());

            return back()->with('error', $e->getMessage());
        }

        $validated = $this->calculation($validated, $amount, $user, $quote);

        if (Money::compare($quote->source_amount, $amount) !== 0
            || (int) $quote->source_currency_id !== (int) $validated['source_currency_id']
            || (int) $quote->target_currency_id !== (int) $validated['target_currency_id']
            || Money::isGreaterThan(abs((float) Money::subtract($validated['target_amount'], $quote->target_amount)), '0.01')) {
            $this->idempotencyService->fail($idempotency, 'The quote changed before submission.');

            return back()->with('error', 'The quote changed before submission. Please review the latest amount and try again.');
        }

        // Ensure amount after fees is positive
        if (Money::isLessThan($validated['amountToConvert'], '0.01')) {
            return back()->with('error', 'The transfer amount is too small. The fees exceed the amount you want to send.');
        }

        try {
            if (Transaction::recordTransfer($validated, $amount)) {
                $this->idempotencyService->complete($idempotency, [
                    'route' => 'home',
                    'flash_type' => 'success',
                    'message' => 'Your transaction was successful',
                ]);

                return redirect()->route('home')
                    ->with('success', 'Your transaction was successful');
            }

            $this->idempotencyService->fail($idempotency, 'An error occurred while making the transfer.');

            return back()->with('error', 'Sorry! An error occurred while making the transfer.');
        } catch (\Exception $e) {
            // Record failed attempts without mutating balances.
            Transaction::failedTransaction($validated, $amount, 'Debit', $user->id, $validated['recipient_id']);
            Transaction::failedTransaction($validated, $validated['targetAmount'], 'Credit', $validated['recipient_id'], $user->id);
            $this->ledgerService->recordFailedTransfer(
                $user,
                $currency,
                $validated['quote'] ?? null,
                $validated['transfer_group_uuid'] ?? (string) Str::uuid(),
                $e->getMessage(),
                $idempotency
            );

            $this->idempotencyService->fail($idempotency, 'An error occurred while making the transaction.');
            report($e);

            return redirect()->route('home')
                ->with('error', 'Sorry! An error occurred while making the transaction.');
        }
    }

    /**
     * Display the details of a specific transaction.
     */
    public function show(Request $request, string $uuid): View
    {
        $transaction = Transaction::where('uuid', $uuid)
            ->with(['currencyBalance', 'user', 'recipient', 'sourceCurrency', 'targetCurrency'])
            ->firstOrFail();

        // Ensure user can only view their own transactions
        /** @var User $user */
        $user = $request->user();
        if (! $user->isAdmin() && $transaction->user_id !== $user->id && $transaction->recipient_id !== $user->id) {
            abort(403, 'Unauthorized to view this transaction.');
        }

        return view('application.show', compact('transaction'));
    }

    /**
     * Show the form for editing a transaction.
     */
    public function edit(int $id): View
    {
        abort(404, 'Transaction editing is not supported.');
    }

    /**
     * Update a transaction.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        abort(404, 'Transaction updating is not supported.');
    }

    /**
     * Remove a transaction.
     */
    public function destroy(int $id): RedirectResponse
    {
        abort(404, 'Transaction deletion is not supported.');
    }

    /**
     * Convert the source amount and currency (AJAX endpoint).
     */
    public function sourceConverter(SourceConverterRequest $request): View|JsonResponse|null
    {
        if (! $request->ajax()) {
            return response()->json(['error' => 'Invalid request'], 400);
        }

        $filters = $request->validated();

        /** @var User $user */
        $user = $request->user();
        // Get the maximum available amount for the source currency
        $sourceCurrency = Currency::findOrFail($filters['source_currency_id']);
        $availableBalance = $this->ledgerService->balanceFor($user, $sourceCurrency);
        if (Money::isGreaterThan($filters['source_amount'], (string) $availableBalance)) {
            return response()->json([
                'error' => 'Amount cannot be greater than the available '.$sourceCurrency->code.' balance.',
            ], 422);
        }

        $sourceAmount = Money::round($filters['source_amount']);

        $targetCurrency = Currency::findOrFail($filters['target_currency_id']);

        return $this->converter($sourceCurrency, $targetCurrency, $sourceAmount, 'application.transactions.includes._transaction_breakdown', $user);
    }

    /**
     * Convert currencies and render the appropriate view.
     */
    public function converter(Currency $sourceCurrency, Currency $targetCurrency, float|string $sourceAmount, string $view, User $user): View
    {
        $quote = $this->transferQuoteService->createQuote($user, $sourceCurrency, $targetCurrency, $sourceAmount);
        $summary = $this->transferQuoteService->summaryFor($quote);

        return view($view, [
            'user' => $user,
            'recipients' => User::whereKeyNot($user->id)
                ->whereNull('blocked_at')
                ->whereHas('latestCurrencyBalance')
                ->orderBy('name')
                ->get(),
            'currencies' => Currency::all(),
            'sourceCurrency' => $sourceCurrency,
            'targetCurrency' => $targetCurrency,
            'sourceCurrencyBalance' => (float) $sourceAmount,
            'targetAmount' => $quote->target_amount,
            'quote' => $quote,
            'idempotencyKey' => (string) Str::uuid(),
            'charges' => Charge::all(),
            'summary' => $summary,
        ]);
    }

    /**
     * Get exchange rate (current or fallback).
     */
    public function getRate(float|string $rate, string $sourceCurrency, string $targetCurrency, float|string $sourceAmount): string
    {
        $currentRate = $this->currentExchangeRate($sourceCurrency, $targetCurrency, (float) $sourceAmount);

        return Money::assertDecimal($currentRate ?? $rate);
    }

    /**
     * Calculate variable fee.
     */
    public function getVariableFee(float|string $variablePercentage, float|string $sourceAmount): string
    {
        return Money::percentage($sourceAmount, $variablePercentage);
    }

    /**
     * Execute calculations and conversions.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function calculation(array $validated, float|string $amount, User $user, TransferQuote $quote): array
    {
        $validated['user_id'] = $user->id;
        $validated['recipient_id'] = User::where('uuid', $validated['recipient_uuid'])
            ->whereNull('blocked_at')
            ->firstOrFail()
            ->id;
        $validated['variableFee'] = (string) $quote->variable_fee;
        $validated['rate'] = (string) $quote->rate;
        $validated['fixedFee'] = (string) $quote->fixed_fee;
        $validated['transferFee'] = (string) $quote->transfer_fee;
        $validated['amountToConvert'] = (string) $quote->amount_to_convert;
        $validated['targetAmount'] = (string) $quote->target_amount;
        $validated['transfer_group_uuid'] = (string) Str::uuid();
        $validated['quote'] = $quote;
        $validated['type'] = 'Debit';
        $validated['currency_id'] = $validated['source_currency_id'];
        $validated['sign'] = '-';

        return $validated;
    }

    /**
     * Get currency balance for AJAX requests.
     */
    public function currencyBalance(CurrencyBalanceRequest $request): JsonResponse|array
    {
        if (! $request->ajax()) {
            return response()->json(['error' => 'Invalid request'], 400);
        }

        $filters = $request->validated();
        $sourceCurrency = Currency::findOrFail($filters['source_currency_id']);
        /** @var User $user */
        $user = $request->user();
        $latestCurrencyBalance = $user->latestCurrencyBalance;

        if (! $latestCurrencyBalance) {
            return response()->json(['error' => 'No balance found'], 400);
        }

        $sourceAmount = $latestCurrencyBalance->getBalanceForCurrency($sourceCurrency->code);

        return [
            'sourceCurrency' => $sourceCurrency,
            'sourceCurrencyBalance' => $sourceAmount,
        ];
    }
}
