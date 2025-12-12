<?php

namespace Tests\Unit;

use App\Models\Currency;
use App\Models\CurrencyBalance;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyBalanceTest extends TestCase
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

        // Create user
        $this->user = User::factory()->create();
    }

    public function test_get_balance_for_currency(): void
    {
        $transaction = Transaction::create([
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

        $balance = CurrencyBalance::create([
            'user_id' => $this->user->id,
            'transaction_id' => $transaction->id,
            'USD' => 1000,
            'EUR' => 500,
            'NGN' => 100000,
        ]);

        $this->assertEquals(1000, $balance->getBalanceForCurrency('USD'));
        $this->assertEquals(500, $balance->getBalanceForCurrency('EUR'));
        $this->assertEquals(100000, $balance->getBalanceForCurrency('NGN'));
        $this->assertEquals(0, $balance->getBalanceForCurrency('GBP')); // Unknown currency
    }

    public function test_currency_balance_belongs_to_user(): void
    {
        $transaction = Transaction::create([
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

        $balance = CurrencyBalance::create([
            'user_id' => $this->user->id,
            'transaction_id' => $transaction->id,
            'USD' => 1000,
            'EUR' => 0,
            'NGN' => 0,
        ]);

        $this->assertInstanceOf(User::class, $balance->user);
        $this->assertEquals($this->user->id, $balance->user->id);
    }

    public function test_currency_balance_belongs_to_transaction(): void
    {
        $transaction = Transaction::create([
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

        $balance = CurrencyBalance::create([
            'user_id' => $this->user->id,
            'transaction_id' => $transaction->id,
            'USD' => 1000,
            'EUR' => 0,
            'NGN' => 0,
        ]);

        $this->assertInstanceOf(Transaction::class, $balance->transaction);
        $this->assertEquals($transaction->id, $balance->transaction->id);
    }
}

