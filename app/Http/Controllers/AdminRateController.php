<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRateRequest;
use App\Models\Charge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminRateController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.rates.index', [
            'charges' => Charge::with(['sourceCurrency', 'targetCurrency'])
                ->orderBy('source_currency_id')
                ->orderBy('target_currency_id')
                ->get(),
            'user' => $request->user(),
        ]);
    }

    public function update(UpdateRateRequest $request, Charge $charge): RedirectResponse
    {
        $charge->update($request->validated());

        return back()->with('success', 'Rate and fees updated successfully.');
    }
}
