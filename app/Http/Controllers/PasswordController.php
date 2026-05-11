<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('account.password.edit', [
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()
                ->withErrors(['current_password' => 'The current password is incorrect.'])
                ->onlyInput('current_password');
        }

        $user->forceFill([
            'password' => $validated['password'],
            'remember_token' => null,
        ])->save();

        return back()->with('success', 'Password changed successfully.');
    }
}
