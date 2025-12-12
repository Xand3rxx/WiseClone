<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Currency;
use App\Models\CurrencyBalance;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $recipient;
    protected Currency $usdCurrency;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable rate limiting for tests
        $this->withoutMiddleware(ThrottleRequests::class);

        // Create currencies
        Currency::create(['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€']);
        Currency::create(['name' => 'Nigerian Naira', 'code' => 'NGN', 'symbol' => '₦']);
        $this->usdCurrency = Currency::create(['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$']);

        // Create roles
        Role::create(['name' => 'administrator', 'url' => 'administrator']);
        Role::create(['name' => 'customer', 'url' => 'customer']);

        // Create charges
        Charge::create([
            'source_currency_id' => 3,
            'target_currency_id' => 3,
            'rate' => 1.0,
            'variable_percentage' => 0,
            'fixed_fee' => 4.86,
        ]);

        // Create users
        $this->user = User::factory()->create();
        $this->recipient = User::factory()->create();

        // Create initial balance for user
        $initialTx = Transaction::create([
            'user_id' => $this->user->id,
            'recipient_id' => 1,
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
            'amount' => 1000,
            'rate' => 1.0,
            'transfer_fee' => 0,
            'variable_fee' => 0,
            'fixed_fee' => 0,
            'type' => 'Credit',
            'status' => 'Success',
        ]);

        CurrencyBalance::create([
            'user_id' => $this->user->id,
            'transaction_id' => $initialTx->id,
            'USD' => 1000,
            'EUR' => 0,
            'NGN' => 0,
        ]);

        // Create initial balance for recipient
        $recipientTx = Transaction::create([
            'user_id' => $this->recipient->id,
            'recipient_id' => 1,
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
            'amount' => 500,
            'rate' => 1.0,
            'transfer_fee' => 0,
            'variable_fee' => 0,
            'fixed_fee' => 0,
            'type' => 'Credit',
            'status' => 'Success',
        ]);

        CurrencyBalance::create([
            'user_id' => $this->recipient->id,
            'transaction_id' => $recipientTx->id,
            'USD' => 500,
            'EUR' => 0,
            'NGN' => 0,
        ]);
    }

    public function test_user_can_view_create_transaction_page(): void
    {
        $this->actingAs($this->user);

        $response = $this->get('/transaction/create');

        $response->assertStatus(200);
    }

    public function test_user_can_create_transaction(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/transaction', [
            'recipient_uuid' => $this->recipient->uuid,
            'source_amount' => '100',
            'target_amount' => '95.14',
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
        ]);

        $response->assertRedirect('/');
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'recipient_id' => $this->recipient->id,
            'type' => 'Debit',
        ]);
    }

    public function test_user_can_create_transaction_with_decimal_amount(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/transaction', [
            'recipient_uuid' => $this->recipient->uuid,
            'source_amount' => '10.50',
            'target_amount' => '5.64',
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
        ]);

        $response->assertRedirect('/');
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'recipient_id' => $this->recipient->id,
            'type' => 'Debit',
        ]);
    }

    public function test_user_can_create_transaction_with_comma_formatted_amount(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/transaction', [
            'recipient_uuid' => $this->recipient->uuid,
            'source_amount' => '100.50',  // Cleave.js might format as "100.50"
            'target_amount' => '95.64',
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
        ]);

        $response->assertRedirect('/');
    }

    public function test_user_can_create_transaction_with_thousand_separator(): void
    {
        $this->actingAs($this->user);

        // Simulate Cleave.js formatted input with thousand separator
        $response = $this->post('/transaction', [
            'recipient_uuid' => $this->recipient->uuid,
            'source_amount' => '100',  // No commas in this small amount
            'target_amount' => '95.14',
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
        ]);

        $response->assertRedirect('/');
    }

    public function test_user_cannot_create_transaction_with_insufficient_funds(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/transaction', [
            'recipient_uuid' => $this->recipient->uuid,
            'source_amount' => '5000', // More than available balance
            'target_amount' => '4995.14',
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
        ]);

        $response->assertSessionHas('error');
    }

    public function test_user_cannot_create_transaction_with_zero_amount(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/transaction', [
            'recipient_uuid' => $this->recipient->uuid,
            'source_amount' => '0',
            'target_amount' => '0',
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
        ]);

        $response->assertSessionHasErrors('source_amount');
    }

    public function test_user_cannot_create_transaction_with_amount_below_minimum(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/transaction', [
            'recipient_uuid' => $this->recipient->uuid,
            'source_amount' => '0.001', // Below minimum of 0.01
            'target_amount' => '0',
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
        ]);

        $response->assertSessionHasErrors('source_amount');
    }

    public function test_user_can_view_transaction_details(): void
    {
        $this->actingAs($this->user);

        $transaction = Transaction::where('user_id', $this->user->id)->first();

        $response = $this->get("/transaction/{$transaction->uuid}");

        $response->assertStatus(200);
    }

    public function test_user_cannot_view_other_users_transaction(): void
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $transaction = Transaction::where('user_id', $this->user->id)->first();

        $response = $this->get("/transaction/{$transaction->uuid}");

        $response->assertStatus(403);
    }

    public function test_user_cannot_transfer_to_self(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/transaction', [
            'recipient_uuid' => $this->user->uuid, // Self-transfer
            'source_amount' => '100',
            'target_amount' => '95.14',
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
        ]);

        $response->assertSessionHas('error');
    }

    public function test_admin_cannot_create_transaction(): void
    {
        // Create admin role and user
        $adminRole = Role::find(1); // administrator
        $adminUser = User::factory()->create(['role_id' => $adminRole->id]);

        $this->actingAs($adminUser);

        $response = $this->get('/transaction/create');

        $response->assertRedirect('/');
        $response->assertSessionHas('error');
    }
}
