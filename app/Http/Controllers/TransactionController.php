<?php

namespace App\Http\Controllers;

use App\Models\Charge;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use App\Traits\CanPay;
use App\Traits\ExchangeRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    use ExchangeRate, CanPay;

    /**
     * Display a listing of transactions.
     */
    public function index(): View
    {
        $user = auth()->user();
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
    public function create(): View
    {
        $user = auth()->user();
        $sourceCurrency = $user->currency;
        $latestBalance = $user->latestCurrencyBalance;

        if (!$latestBalance) {
            abort(403, 'No currency balance found. Please contact support.');
        }

        $sourceAmount = $latestBalance->getBalanceForCurrency($sourceCurrency->code);

        return $this->converter($sourceCurrency, $sourceCurrency, $sourceAmount, 'application.transactions.create');
    }

    /**
     * Store a newly created transaction in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateRequest();
        $amount = Transaction::removeComma($validated['source_amount']);
        $currency = Currency::findOrFail($validated['source_currency_id']);

        // Check if user can make payment (has sufficient funds)
        if (!$this->canMakePayment($currency->code, $amount)) {
            return back()->with('error', "Oops! Your {$currency->name} account balance is insufficient to complete this transaction.");
        }

        // Validate minimum amount
        if ($amount < 1.0) {
            return back()->with('error', "Sorry! The source amount cannot be less than {$currency->symbol}1.");
        }

        // Calculate the target amount to be sent to recipient
        $validated = $this->calculation($validated, $amount);

        try {
            // Record first transaction for authenticated user (debit)
            if (Transaction::doubleEntryRecord($validated, $amount)) {
                // Alternate data for recipient transaction record (credit)
                $recipientAmount = $validated['targetAmount'];
                $validated = $this->alternateSourceRecord($validated);

                // Record second transaction for recipient
                if (Transaction::doubleEntryRecord($validated, $recipientAmount)) {
                    return redirect()->route('home')
                        ->with('success', 'Your transaction was successful');
                }
            }

            return back()->with('error', 'Sorry! An error occurred while making the transfer.');
        } catch (\Exception $e) {
            // Record double-entry failed transactions
            Transaction::failedTransaction($validated, $amount, 'Debit', auth()->id(), $validated['recipient_id']);
            Transaction::failedTransaction($validated, $validated['targetAmount'], 'Credit', $validated['recipient_id'], auth()->id());

            report($e);

            return redirect()->route('home')
                ->with('error', 'Sorry! An error occurred while making the transaction.');
        }
    }

    /**
     * Display the details of a specific transaction.
     */
    public function show(string $uuid): View
    {
        $transaction = Transaction::where('uuid', $uuid)
            ->with(['currencyBalance', 'user', 'recipient', 'sourceCurrency', 'targetCurrency'])
            ->firstOrFail();

        // Ensure user can only view their own transactions
        $user = auth()->user();
        if (!$user->isAdmin() && $transaction->user_id !== $user->id && $transaction->recipient_id !== $user->id) {
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
    public function sourceConverter(Request $request): View|JsonResponse|null
    {
        if (!$request->ajax()) {
            return response()->json(['error' => 'Invalid request'], 400);
        }

        $filters = $request->only('source_amount', 'source_currency_id', 'target_currency_id');

        // Validate input
        if (empty($filters['source_amount']) || $filters['source_amount'] === 'NaN') {
            return null;
        }

        $user = auth()->user();
        $latestCurrencyBalance = $user->latestCurrencyBalance;

        if (!$latestCurrencyBalance) {
            return response()->json(['error' => 'No balance found'], 400);
        }

        // Get the maximum available amount for the source currency
        $sourceCurrency = Currency::findOrFail($filters['source_currency_id']);
        $availableBalance = $latestCurrencyBalance->getBalanceForCurrency($sourceCurrency->code);
        $sourceAmount = min((float) $filters['source_amount'], $availableBalance);

        $targetCurrency = Currency::findOrFail($filters['target_currency_id']);

        return $this->converter($sourceCurrency, $targetCurrency, $sourceAmount, 'application.transactions.includes._transaction_breakdown');
    }

    /**
     * Convert currencies and render the appropriate view.
     */
    public function converter(Currency $sourceCurrency, Currency $targetCurrency, float $sourceAmount, string $view): View
    {
        $charge = Charge::where('source_currency_id', $sourceCurrency->id)
            ->where('target_currency_id', $targetCurrency->id)
            ->firstOrFail();

        $fixedFee = (float) $charge->fixed_fee;
        $variableFee = ($charge->variable_percentage / 100) * $sourceAmount;
        $transferFee = $variableFee + $fixedFee;
        $amountToConvert = max(0, $sourceAmount - $transferFee);
        $rate = (float) $charge->rate;
        $targetAmount = $amountToConvert * $rate;

        $user = auth()->user();

        return view($view, [
            'user' => $user,
            'recipients' => User::where('role_id', 2)
                ->where('id', '!=', $user->id)
                ->orderBy('name')
                ->get(),
            'currencies' => Currency::all(),
            'sourceCurrency' => $sourceCurrency,
            'targetCurrency' => $targetCurrency,
            'sourceCurrencyBalance' => $sourceAmount,
            'targetAmount' => $targetAmount,
            'charges' => Charge::all(),
            'summary' => [
                'transferFee' => number_format($transferFee, 2),
                'amountToConvert' => number_format($amountToConvert, 2),
                'fixedFee' => number_format($fixedFee, 2),
                'variableFeeText' => number_format($variableFee, 2) . ' ' . $sourceCurrency->code . ' (' . $charge->variable_percentage . '%)',
                'variableFee' => number_format($variableFee, 2),
                'rate' => $rate,
            ],
        ]);
    }

    /**
     * Get exchange rate (current or fallback).
     */
    public function getRate(float $rate, string $sourceCurrency, string $targetCurrency, float $sourceAmount): float
    {
        $currentRate = $this->currentExchangeRate($sourceCurrency, $targetCurrency, $sourceAmount);
        return $currentRate ?? $rate;
    }

    /**
     * Calculate variable fee.
     */
    public function getVariableFee(float $variablePercentage, float $sourceAmount): float
    {
        return ($variablePercentage / 100) * $sourceAmount;
    }

    /**
     * Execute calculations and conversions.
     *
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    public function calculation(array $validated, float $amount): array
    {
        $charge = Charge::where('source_currency_id', $validated['source_currency_id'])
            ->where('target_currency_id', $validated['target_currency_id'])
            ->firstOrFail();

        $validated['user_id'] = auth()->id();
        $validated['recipient_id'] = User::where('uuid', $validated['recipient_uuid'])->firstOrFail()->id;
        $validated['variableFee'] = $this->getVariableFee($charge->variable_percentage, $amount);
        $validated['rate'] = $this->getRate(
            $charge->rate,
            $charge->sourceCurrency->code,
            $charge->targetCurrency->code,
            $amount
        );
        $validated['fixedFee'] = (float) $charge->fixed_fee;
        $validated['transferFee'] = $validated['variableFee'] + $validated['fixedFee'];
        $validated['amountToConvert'] = $amount - $validated['transferFee'];
        $validated['targetAmount'] = $validated['amountToConvert'] * $validated['rate'];
        $validated['type'] = 'Debit';
        $validated['currency_id'] = $validated['source_currency_id'];
        $validated['sign'] = '-';

        return $validated;
    }

    /**
     * Validate user input fields.
     *
     * @return array<string, mixed>
     */
    private function validateRequest(): array
    {
        return request()->validate([
            'recipient_uuid' => 'bail|required|string|exists:users,uuid',
            'source_amount' => 'bail|required|numeric|min:1|max:99999999.99',
            'target_amount' => 'bail|required|numeric|min:0|max:99999999.99',
            'source_currency_id' => 'bail|required|integer|exists:currencies,id',
            'target_currency_id' => 'bail|required|integer|exists:currencies,id',
        ]);
    }

    /**
     * Get currency balance for AJAX requests.
     */
    public function currencyBalance(Request $request): JsonResponse|array
    {
        if (!$request->ajax()) {
            return response()->json(['error' => 'Invalid request'], 400);
        }

        $filters = $request->only('source_currency_id');
        $sourceCurrency = Currency::findOrFail($filters['source_currency_id']);
        $latestCurrencyBalance = auth()->user()->latestCurrencyBalance;

        if (!$latestCurrencyBalance) {
            return response()->json(['error' => 'No balance found'], 400);
        }

        $sourceAmount = $latestCurrencyBalance->getBalanceForCurrency($sourceCurrency->code);

        return [
            'sourceCurrency' => $sourceCurrency,
            'sourceCurrencyBalance' => $sourceAmount,
        ];
    }

    /**
     * Alternate data for recipient transaction record.
     *
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    public function alternateSourceRecord(array $validated): array
    {
        $originalRecipientId = $validated['recipient_id'];

        $validated['type'] = 'Credit';
        $validated['user_id'] = $originalRecipientId;
        $validated['recipient_id'] = auth()->id();
        $validated['currency_id'] = $validated['target_currency_id'];
        $validated['sign'] = '+';

        return $validated;
    }
}
