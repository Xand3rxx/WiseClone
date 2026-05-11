<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\CurrencyBalance;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        $this->authorizeAdmin();

        return view('admin.users.index', [
            'users' => User::with(['role', 'currency', 'latestCurrencyBalance'])
                ->latest()
                ->paginate(25),
            'user' => Auth::user(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        return view('admin.users.create', [
            'roles' => Role::orderBy('name')->get(),
            'currencies' => Currency::orderBy('code')->get(),
            'user' => Auth::user(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);

        /** @var User $admin */
        $admin = Auth::user();

        $managedUser = DB::transaction(function () use ($validated, $admin): User {
            $managedUser = User::create([
                'role_id' => $validated['role_id'],
                'currency_id' => $validated['currency_id'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'email_verified_at' => now(),
            ]);

            $currency = Currency::findOrFail($validated['currency_id']);
            $this->createOpeningBalance($managedUser, $admin, $currency);

            return $managedUser;
        });

        return redirect()
            ->route('admin.users.show', $managedUser->uuid)
            ->with('success', 'User created successfully.');
    }

    public function show(string $uuid): View
    {
        $this->authorizeAdmin();

        $managedUser = User::withTrashed()
            ->with(['role', 'currency', 'latestCurrencyBalance'])
            ->where('uuid', $uuid)
            ->firstOrFail();

        $transactions = Transaction::with(['user', 'recipient', 'sourceCurrency', 'targetCurrency'])
            ->forUser($managedUser->id)
            ->latest()
            ->paginate(25);

        return view('admin.users.show', [
            'managedUser' => $managedUser,
            'transactions' => $transactions,
            'user' => Auth::user(),
        ]);
    }

    public function block(string $uuid): RedirectResponse
    {
        $this->authorizeAdmin();

        $managedUser = $this->findActiveUser($uuid);
        $this->ensureCanManage($managedUser, 'block');

        $managedUser->forceFill(['blocked_at' => now()])->save();

        return back()->with('success', 'User blocked successfully.');
    }

    public function unblock(string $uuid): RedirectResponse
    {
        $this->authorizeAdmin();

        $managedUser = $this->findActiveUser($uuid);
        $this->ensureCanManage($managedUser, 'unblock');

        $managedUser->forceFill(['blocked_at' => null])->save();

        return back()->with('success', 'User unblocked successfully.');
    }

    public function destroy(string $uuid): RedirectResponse
    {
        $this->authorizeAdmin();

        $managedUser = $this->findActiveUser($uuid);
        $this->ensureCanManage($managedUser, 'delete');

        $managedUser->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully. Transaction history is retained.');
    }

    private function createOpeningBalance(User $managedUser, User $admin, Currency $currency): void
    {
        $transaction = Transaction::create([
            'user_id' => $managedUser->id,
            'recipient_id' => $admin->id,
            'source_currency_id' => $currency->id,
            'target_currency_id' => $currency->id,
            'amount' => 0,
            'rate' => 1.0,
            'transfer_fee' => 0,
            'variable_fee' => 0,
            'fixed_fee' => 0,
            'type' => Transaction::TYPE['Credit'],
            'status' => Transaction::STATUS['Success'],
        ]);

        CurrencyBalance::create([
            'user_id' => $managedUser->id,
            'transaction_id' => $transaction->id,
            'USD' => 0,
            'EUR' => 0,
            'NGN' => 0,
        ]);
    }

    private function findActiveUser(string $uuid): User
    {
        return User::where('uuid', $uuid)->firstOrFail();
    }

    private function ensureCanManage(User $managedUser, string $action): void
    {
        abort_if($managedUser->id === Auth::id(), 422, "You cannot {$action} your own account.");
    }

    private function authorizeAdmin(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user?->isAdmin(), 403, 'Only administrators can manage users.');
    }
}
