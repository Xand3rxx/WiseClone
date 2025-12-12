<?php

namespace Tests\Unit;

use App\Models\Currency;
use App\Models\CurrencyBalance;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Traits\CanPay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CanPayTraitTest extends TestCase
{
    use RefreshDatabase;
    use CanPay;

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

    public function test_can_make_payment_returns_true_with_sufficient_funds(): void
    {
        $this->createBalanceForUser($this->user, 1000);
        $this->actingAs($this->user);

        $this->assertTrue($this->canMakePayment('USD', 500));
        $this->assertTrue($this->canMakePayment('USD', 1000));
    }

    public function test_can_make_payment_returns_false_with_insufficient_funds(): void
    {
        $this->createBalanceForUser($this->user, 100);
        $this->actingAs($this->user);

        $this->assertFalse($this->canMakePayment('USD', 500));
    }

    public function test_can_make_payment_returns_false_with_zero_balance(): void
    {
        $this->createBalanceForUser($this->user, 0);
        $this->actingAs($this->user);

        $this->assertFalse($this->canMakePayment('USD', 100));
    }

    public function test_get_available_balance(): void
    {
        $this->createBalanceForUser($this->user, 500);
        $this->actingAs($this->user);

        $this->assertEquals(500.0, $this->getAvailableBalance('USD'));
    }

    public function test_has_any_balance(): void
    {
        $this->createBalanceForUser($this->user, 100);
        $this->actingAs($this->user);

        $this->assertTrue($this->hasAnyBalance());
    }

    public function test_has_no_balance(): void
    {
        $this->createBalanceForUser($this->user, 0);
        $this->actingAs($this->user);

        $this->assertFalse($this->hasAnyBalance());
    }

    private function createBalanceForUser(User $user, float $usdAmount): void
    {
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'recipient_id' => 1,
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
            'amount' => $usdAmount,
            'rate' => 1.0,
            'transfer_fee' => 0,
            'variable_fee' => 0,
            'fixed_fee' => 0,
            'type' => 'Credit',
            'status' => 'Success',
        ]);

        CurrencyBalance::create([
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
            'USD' => $usdAmount,
            'EUR' => 0,
            'NGN' => 0,
        ]);
    }
}

