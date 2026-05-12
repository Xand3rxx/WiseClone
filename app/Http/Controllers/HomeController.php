<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\IdempotencyKey;
use App\Models\Transaction;
use App\Models\User;
use App\Services\IdempotencyService;
use App\Services\LedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly LedgerService $ledgerService,
        private readonly IdempotencyService $idempotencyService
    ) {
        $this->middleware('auth');
    }

    /**
     * Display the authenticated user dashboard.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $transactions = $user->isAdmin()
            ? Transaction::with(['user', 'recipient', 'sourceCurrency', 'targetCurrency'])
                ->latest()
                ->take(500)
                ->get()
            : Transaction::forUser($user->id)
                ->with(['user', 'recipient', 'sourceCurrency', 'targetCurrency'])
                ->latest()
                ->take(200)
                ->get();

        return view('application.index', [
            'transactions' => $transactions,
            'user' => $user,
        ]);
    }

    /**
     * Fund the user's dollar account.
     */
    public function fundAccount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'idempotency_key' => 'required|string|max:100',
        ]);
        /** @var User $user */
        $user = $request->user();
        $idempotency = $this->idempotencyService->start($user->id, 'fund-account', $validated['idempotency_key'], $validated);

        if ($idempotency->status === IdempotencyKey::STATUS_COMPLETED) {
            return redirect()->route($idempotency->response_payload['route'] ?? 'transaction.create')
                ->with($idempotency->response_payload['flash_type'] ?? 'success', $idempotency->response_payload['message'] ?? 'Your funding request was already processed.');
        }

        // Prevent admin users from funding accounts
        if ($user->isAdmin()) {
            return back()->with('error', 'Admin accounts cannot be funded.');
        }

        $latestCurrencyBalance = $user->latestCurrencyBalance;

        // If no balance exists or USD balance is not zero, redirect back
        if (! $latestCurrencyBalance) {
            return back()->with('error', 'Unable to process request. Please contact support.');
        }

        if ((float) $latestCurrencyBalance->USD > 0) {
            return back()->with('info', 'Your USD account already has a balance.');
        }

        $currency = Currency::where('code', 'USD')->firstOrFail();
        $systemUser = User::whereHas('role', fn ($query) => $query->where('name', 'administrator'))->firstOrFail();
        $fundingAmount = 1000.00;

        // Demo funding is a system credit with no transfer fee.
        $this->ledgerService->fundAccount($user, $systemUser, $currency, (string) $fundingAmount, $idempotency);
        $this->idempotencyService->complete($idempotency, [
            'route' => 'transaction.create',
            'flash_type' => 'success',
            'message' => 'Your dollar account has been credited with $1,000',
        ]);

        return redirect()->route('transaction.create')
            ->with('success', 'Your dollar account has been credited with $1,000');
    }
}
