<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Charge;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminRateController extends Controller
{
    public function index(): View
    {
        $this->authorizeAdmin();

        return view('admin.rates.index', [
            'charges' => Charge::with(['sourceCurrency', 'targetCurrency'])
                ->orderBy('source_currency_id')
                ->orderBy('target_currency_id')
                ->get(),
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request, Charge $charge): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'rate' => ['required', 'numeric', 'gt:0', 'max:999999999.999999'],
            'variable_percentage' => ['required', 'numeric', 'min:0', 'max:10'],
            'fixed_fee' => ['required', 'numeric', 'min:0', 'max:999999.9999'],
        ]);

        $charge->update($validated);

        return back()->with('success', 'Rate and fees updated successfully.');
    }

    private function authorizeAdmin(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user?->isAdmin(), 403, 'Only administrators can manage rates.');
    }
}
