<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminUserRequest;
use App\Models\Currency;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Services\LedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.users.index', [
            'users' => User::with(['role', 'currency', 'latestCurrencyBalance'])
                ->latest()
                ->paginate(25),
            'user' => $request->user(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.users.create', [
            'roles' => Role::orderBy('name')->get(),
            'currencies' => Currency::orderBy('code')->get(),
            'user' => $request->user(),
        ]);
    }

    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /** @var User $admin */
        $admin = $request->user();

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
            app(LedgerService::class)->fundAccount($managedUser, $admin, $currency, '0.00');

            return $managedUser;
        });

        return redirect()
            ->route('admin.users.show', $managedUser->uuid)
            ->with('success', 'User created successfully.');
    }

    public function show(Request $request, string $uuid): View
    {
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
            'user' => $request->user(),
        ]);
    }

    public function block(Request $request, string $uuid): RedirectResponse
    {
        $managedUser = $this->findActiveUser($uuid);
        $this->ensureCanManage($managedUser, (int) $request->user()->id, 'block');

        $managedUser->forceFill(['blocked_at' => now()])->save();

        return back()->with('success', 'User blocked successfully.');
    }

    public function unblock(Request $request, string $uuid): RedirectResponse
    {
        $managedUser = $this->findActiveUser($uuid);
        $this->ensureCanManage($managedUser, (int) $request->user()->id, 'unblock');

        $managedUser->forceFill(['blocked_at' => null])->save();

        return back()->with('success', 'User unblocked successfully.');
    }

    public function destroy(Request $request, string $uuid): RedirectResponse
    {
        $managedUser = $this->findActiveUser($uuid);
        $this->ensureCanManage($managedUser, (int) $request->user()->id, 'delete');

        $managedUser->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully. Transaction history is retained.');
    }

    private function findActiveUser(string $uuid): User
    {
        return User::where('uuid', $uuid)->firstOrFail();
    }

    private function ensureCanManage(User $managedUser, int $actorId, string $action): void
    {
        abort_if($managedUser->id === $actorId, 422, "You cannot {$action} your own account.");
    }
}
