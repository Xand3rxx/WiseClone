<?php

namespace Tests\Unit;

use App\Models\Currency;
use App\Models\CurrencyBalance;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionModelTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $recipient;
    protected Currency $usdCurrency;

    protected function setUp(): void
    {
        parent::setUp();

        // Create currencies first (required for foreign keys)
        Currency::create(['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€']);
        Currency::create(['name' => 'Nigerian Naira', 'code' => 'NGN', 'symbol' => '₦']);
        $this->usdCurrency = Currency::create(['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$']);

        // Create roles (required for foreign keys)
        Role::create(['name' => 'administrator', 'url' => 'administrator']);
        Role::create(['name' => 'customer', 'url' => 'customer']);

        // Create users
        $this->user = User::factory()->create();
        $this->recipient = User::factory()->create();
    }

    public function test_transaction_has_uuid_on_creation(): void
    {
        $transaction = Transaction::create([
            'user_id' => $this->user->id,
            'recipient_id' => $this->recipient->id,
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
            'amount' => 100,
            'rate' => 1.0,
            'transfer_fee' => 4.86,
            'variable_fee' => 0,
            'fixed_fee' => 4.86,
            'type' => 'Debit',
            'status' => 'Success',
        ]);

        $this->assertNotNull($transaction->uuid);
    }

    public function test_transaction_has_unique_reference(): void
    {
        $transaction1 = Transaction::create([
            'user_id' => $this->user->id,
            'recipient_id' => $this->recipient->id,
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
            'amount' => 100,
            'rate' => 1.0,
            'transfer_fee' => 4.86,
            'variable_fee' => 0,
            'fixed_fee' => 4.86,
            'type' => 'Debit',
            'status' => 'Success',
        ]);

        $transaction2 = Transaction::create([
            'user_id' => $this->user->id,
            'recipient_id' => $this->recipient->id,
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
            'amount' => 200,
            'rate' => 1.0,
            'transfer_fee' => 4.86,
            'variable_fee' => 0,
            'fixed_fee' => 4.86,
            'type' => 'Debit',
            'status' => 'Success',
        ]);

        $this->assertNotEquals($transaction1->reference, $transaction2->reference);
    }

    public function test_transaction_belongs_to_user(): void
    {
        $transaction = Transaction::create([
            'user_id' => $this->user->id,
            'recipient_id' => $this->recipient->id,
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
            'amount' => 100,
            'rate' => 1.0,
            'transfer_fee' => 4.86,
            'variable_fee' => 0,
            'fixed_fee' => 4.86,
            'type' => 'Debit',
            'status' => 'Success',
        ]);

        $this->assertInstanceOf(User::class, $transaction->user);
        $this->assertEquals($this->user->id, $transaction->user->id);
    }

    public function test_transaction_belongs_to_recipient(): void
    {
        $transaction = Transaction::create([
            'user_id' => $this->user->id,
            'recipient_id' => $this->recipient->id,
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
            'amount' => 100,
            'rate' => 1.0,
            'transfer_fee' => 4.86,
            'variable_fee' => 0,
            'fixed_fee' => 4.86,
            'type' => 'Debit',
            'status' => 'Success',
        ]);

        $this->assertInstanceOf(User::class, $transaction->recipient);
        $this->assertEquals($this->recipient->id, $transaction->recipient->id);
    }

    public function test_remove_comma_from_amount(): void
    {
        $this->assertEquals(1000.50, Transaction::removeComma('1,000.50'));
        $this->assertEquals(1000000.00, Transaction::removeComma('1,000,000.00'));
        $this->assertEquals(100.5, Transaction::removeComma(100.5));
    }

    public function test_transaction_status_helper(): void
    {
        $transaction = Transaction::create([
            'user_id' => $this->user->id,
            'recipient_id' => $this->recipient->id,
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
            'amount' => 100,
            'rate' => 1.0,
            'transfer_fee' => 4.86,
            'variable_fee' => 0,
            'fixed_fee' => 4.86,
            'type' => 'Debit',
            'status' => 'Success',
        ]);

        $status = $transaction->status();

        $this->assertEquals('Success', $status->name);
        $this->assertEquals('light-primary', $status->class);
    }

    public function test_transaction_type_helper(): void
    {
        $transaction = Transaction::create([
            'user_id' => $this->user->id,
            'recipient_id' => $this->recipient->id,
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
            'amount' => 100,
            'rate' => 1.0,
            'transfer_fee' => 4.86,
            'variable_fee' => 0,
            'fixed_fee' => 4.86,
            'type' => 'Debit',
            'status' => 'Success',
        ]);

        $type = $transaction->type();

        $this->assertEquals('Debit', $type->name);
        $this->assertEquals('-', $type->sign);
    }
}
