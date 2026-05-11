<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $customer;

    protected Role $adminRole;

    protected Role $customerRole;

    protected Currency $usdCurrency;

    protected function setUp(): void
    {
        parent::setUp();

        Currency::create(['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€']);
        Currency::create(['name' => 'Nigerian Naira', 'code' => 'NGN', 'symbol' => '₦']);
        $this->usdCurrency = Currency::create(['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$']);

        $this->adminRole = Role::create(['name' => 'administrator', 'url' => 'administrator']);
        $this->customerRole = Role::create(['name' => 'customer', 'url' => 'customer']);

        $this->admin = User::factory()->admin()->create([
            'password' => Hash::make('password'),
        ]);
        $this->customer = User::factory()->customer()->create([
            'password' => Hash::make('password'),
        ]);
    }

    public function test_admin_can_view_user_list(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/users');

        $response->assertStatus(200);
        $response->assertSee('User Management');
        $response->assertSee($this->customer->email);
    }

    public function test_customer_cannot_view_user_list(): void
    {
        $response = $this->actingAs($this->customer)->get('/admin/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_create_user_with_opening_balance(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/users', [
            'name' => 'Created Customer',
            'email' => 'created@example.com',
            'role_id' => $this->customerRole->id,
            'currency_id' => $this->usdCurrency->id,
            'password' => 'WiseCloneCreated123!',
            'password_confirmation' => 'WiseCloneCreated123!',
        ]);

        $createdUser = User::where('email', 'created@example.com')->firstOrFail();

        $response->assertRedirect(route('admin.users.show', $createdUser->uuid));
        $this->assertTrue($createdUser->hasVerifiedEmail());
        $this->assertDatabaseHas('currency_balances', [
            'user_id' => $createdUser->id,
            'USD' => 0,
            'EUR' => 0,
            'NGN' => 0,
        ]);
    }

    public function test_admin_can_view_user_details_with_transactions(): void
    {
        Transaction::create([
            'user_id' => $this->customer->id,
            'recipient_id' => $this->admin->id,
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
            'amount' => 25,
            'rate' => 1,
            'transfer_fee' => 0,
            'variable_fee' => 0,
            'fixed_fee' => 0,
            'type' => Transaction::TYPE['Credit'],
            'status' => Transaction::STATUS['Success'],
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/users/{$this->customer->uuid}");

        $response->assertStatus(200);
        $response->assertSee($this->customer->email);
        $response->assertSee('Transactions');
        $response->assertSee('+25.00');
    }

    public function test_admin_can_block_and_unblock_user(): void
    {
        $blockResponse = $this->actingAs($this->admin)->patch("/admin/users/{$this->customer->uuid}/block");

        $blockResponse->assertRedirect();
        $this->assertNotNull($this->customer->fresh()->blocked_at);

        $unblockResponse = $this->actingAs($this->admin)->patch("/admin/users/{$this->customer->uuid}/unblock");

        $unblockResponse->assertRedirect();
        $this->assertNull($this->customer->fresh()->blocked_at);
    }

    public function test_blocked_user_cannot_login(): void
    {
        $this->customer->forceFill(['blocked_at' => now()])->save();

        $response = $this->post('/login', [
            'email' => $this->customer->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_blocked_authenticated_user_is_signed_out(): void
    {
        $this->customer->forceFill(['blocked_at' => now()])->save();

        $response = $this->actingAs($this->customer)->get('/');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_admin_can_soft_delete_user(): void
    {
        $response = $this->actingAs($this->admin)->delete("/admin/users/{$this->customer->uuid}");

        $response->assertRedirect('/admin/users');
        $this->assertSoftDeleted('users', [
            'id' => $this->customer->id,
        ]);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $response = $this->actingAs($this->admin)->delete("/admin/users/{$this->admin->uuid}");

        $response->assertStatus(422);
        $this->assertNotSoftDeleted('users', [
            'id' => $this->admin->id,
        ]);
    }
}
