<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('account.password.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        Auth::logoutOtherDevices($validated['current_password']);

        $user->forceFill([
            'password' => $validated['password'],
            'remember_token' => null,
        ])->save();

        $request->session()->regenerate();

        return back()->with('success', 'Password changed successfully.');
    }
}
