<?php

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Currency;
use App\Models\CurrencyBalance;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Currency $usdCurrency;

    protected function setUp(): void
    {
        parent::setUp();

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

        // Create user
        $this->user = User::factory()->create();
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        // Create initial balance
        $tx = Transaction::create([
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
            'transaction_id' => $tx->id,
            'USD' => 1000,
            'EUR' => 0,
            'NGN' => 0,
        ]);

        $this->actingAs($this->user);

        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_user_can_fund_account_when_balance_is_zero(): void
    {
        // Create initial zero balance
        $tx = Transaction::create([
            'user_id' => $this->user->id,
            'recipient_id' => 1,
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
            'amount' => 0,
            'rate' => 1.0,
            'transfer_fee' => 0,
            'variable_fee' => 0,
            'fixed_fee' => 0,
            'type' => 'Credit',
            'status' => 'Success',
        ]);

        CurrencyBalance::create([
            'user_id' => $this->user->id,
            'transaction_id' => $tx->id,
            'USD' => 0,
            'EUR' => 0,
            'NGN' => 0,
        ]);

        $this->actingAs($this->user);

        $response = $this->get('/fund-account');

        $response->assertRedirect('/transaction/create');
        $response->assertSessionHas('success');

        // Check that balance was updated
        $newBalance = $this->user->fresh()->latestCurrencyBalance;
        $this->assertEquals(1000, (float) $newBalance->USD);
    }

    public function test_user_cannot_fund_account_when_balance_is_not_zero(): void
    {
        // Create initial balance with some USD
        $tx = Transaction::create([
            'user_id' => $this->user->id,
            'recipient_id' => 1,
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
            'amount' => 100,
            'rate' => 1.0,
            'transfer_fee' => 0,
            'variable_fee' => 0,
            'fixed_fee' => 0,
            'type' => 'Credit',
            'status' => 'Success',
        ]);

        CurrencyBalance::create([
            'user_id' => $this->user->id,
            'transaction_id' => $tx->id,
            'USD' => 100,
            'EUR' => 0,
            'NGN' => 0,
        ]);

        $this->actingAs($this->user);

        $response = $this->get('/fund-account');

        // Should redirect back with info message
        $response->assertSessionHas('info');
    }
}

