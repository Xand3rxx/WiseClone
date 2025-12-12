<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\CurrencyBalance;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the authenticated user dashboard.
     */
    public function index(): View
    {
        $user = auth()->user();

        $transactions = $user->isAdmin()
            ? Transaction::with(['user', 'recipient', 'sourceCurrency', 'targetCurrency'])
                ->latest()
                ->take(500)
                ->get()
            : $user->transactions()
                ->with(['recipient', 'sourceCurrency', 'targetCurrency'])
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
    public function fundAccount(): RedirectResponse
    {
        $user = auth()->user();

        // Prevent admin users from funding accounts
        if ($user->isAdmin()) {
            return back()->with('error', 'Admin accounts cannot be funded.');
        }

        $latestCurrencyBalance = $user->latestCurrencyBalance;

        // If no balance exists or USD balance is not zero, redirect back
        if (!$latestCurrencyBalance) {
            return back()->with('error', 'Unable to process request. Please contact support.');
        }

        if ((float) $latestCurrencyBalance->USD > 0) {
            return back()->with('info', 'Your USD account already has a balance.');
        }

        $currency = Currency::where('code', 'USD')->firstOrFail();
        $fundingAmount = 1000.00;
        $fixedFee = 4.86;

        // Credit the user with $1000
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'recipient_id' => 1, // Admin/System account
            'source_currency_id' => $currency->id,
            'target_currency_id' => $currency->id,
            'amount' => $fundingAmount,
            'rate' => 1.0,
            'transfer_fee' => $fixedFee,
            'variable_fee' => 0,
            'fixed_fee' => $fixedFee,
            'type' => Transaction::TYPE['Credit'],
            'status' => Transaction::STATUS['Success'],
        ]);

        CurrencyBalance::create([
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
            'USD' => $fundingAmount,
            'EUR' => $latestCurrencyBalance->EUR,
            'NGN' => $latestCurrencyBalance->NGN,
        ]);

        return redirect()->route('transaction.create')
            ->with('success', 'Your dollar account has been credited with $1,000');
    }
}
