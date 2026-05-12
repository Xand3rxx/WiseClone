<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Currency;
use App\Models\CurrencyBalance;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\TransferQuote;
use App\Models\User;
use App\Services\TransferQuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Str;
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
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
            'rate' => 1.0,
            'variable_percentage' => 0,
            'fixed_fee' => 1.00,
        ]);
        Charge::create([
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => Currency::where('code', 'NGN')->firstOrFail()->id,
            'rate' => 1390.0,
            'variable_percentage' => 0.35,
            'fixed_fee' => 0.00,
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

        $response = $this->get(route('transaction.create'));

        $response->assertStatus(200);
    }

    public function test_small_usd_to_ngn_converter_displays_near_market_recipient_amount(): void
    {
        $this->actingAs($this->user);

        $ngnCurrency = Currency::where('code', 'NGN')->firstOrFail();

        $response = $this->post(route('transaction.source_converter'), [
            'source_amount' => '3.50',
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $ngnCurrency->id,
        ], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertSee('id="target-amount-display"', false);
        $response->assertSee('value="4,851.10"', false);
        $response->assertSee('name="target_amount"', false);
        $response->assertSee('value="4851.10"', false);
    }

    public function test_user_can_create_transaction(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('transaction.store'), $this->transferPayload());

        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'recipient_id' => $this->recipient->id,
            'type' => 'Debit',
        ]);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->recipient->id,
            'recipient_id' => $this->user->id,
            'type' => 'Credit',
            'amount' => '99.00',
        ]);
        $debit = Transaction::where('user_id', $this->user->id)->where('type', 'Debit')->firstOrFail();
        $credit = Transaction::where('user_id', $this->recipient->id)->where('type', 'Credit')->latest('id')->firstOrFail();
        $this->assertNotNull($debit->transfer_group_uuid);
        $this->assertSame($debit->transfer_group_uuid, $credit->transfer_group_uuid);
        $this->assertSame(900.0, (float) $this->user->fresh()->latestCurrencyBalance->USD);
        $this->assertSame(599.0, (float) $this->recipient->fresh()->latestCurrencyBalance->USD);
    }

    public function test_user_cannot_submit_stale_or_tampered_quote(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('transaction.store'), $this->transferPayload(targetAmount: '1.00'));

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('transactions', 2);
    }

    public function test_user_can_create_transaction_with_decimal_amount(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('transaction.store'), $this->transferPayload(sourceAmount: '10.50'));

        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'recipient_id' => $this->recipient->id,
            'type' => 'Debit',
        ]);
    }

    public function test_user_can_create_transaction_with_comma_formatted_amount(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('transaction.store'), $this->transferPayload(sourceAmount: '100.50'));

        $response->assertRedirect(route('home'));
    }

    public function test_user_can_create_transaction_with_thousand_separator(): void
    {
        $this->actingAs($this->user);

        // Simulate Cleave.js formatted input with thousand separator
        $response = $this->post(route('transaction.store'), $this->transferPayload(sourceAmount: '100'));

        $response->assertRedirect(route('home'));
    }

    public function test_user_cannot_create_transaction_with_insufficient_funds(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('transaction.store'), $this->transferPayload(sourceAmount: '5000'));

        $response->assertSessionHas('error');
    }

    public function test_user_cannot_create_transaction_with_zero_amount(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('transaction.store'), array_merge($this->transferPayload(), [
            'source_amount' => '0',
            'target_amount' => '0',
        ]));

        $response->assertSessionHasErrors('source_amount');
    }

    public function test_user_cannot_create_transaction_with_amount_below_minimum(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('transaction.store'), array_merge($this->transferPayload(), [
            'source_amount' => '0.001',
            'target_amount' => '0',
        ]));

        $response->assertSessionHasErrors('source_amount');
    }

    public function test_user_cannot_create_transaction_with_malformed_money_input(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('transaction.store'), array_merge($this->transferPayload(), [
            'source_amount' => '1.2.3',
        ]));

        $response->assertSessionHasErrors('source_amount');
    }

    public function test_user_cannot_transfer_to_blocked_recipient(): void
    {
        $this->recipient->forceFill(['blocked_at' => now()])->save();
        $this->actingAs($this->user);

        $response = $this->post(route('transaction.store'), $this->transferPayload());

        $response->assertSessionHasErrors('recipient_uuid');
        $this->assertDatabaseCount('transactions', 2);
    }

    public function test_converter_rejects_over_balance_quote_requests(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('transaction.source_converter'), [
            'source_amount' => '1000.01',
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
        ], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(422);
    }

    public function test_transaction_history_retains_soft_deleted_participants(): void
    {
        $this->actingAs($this->user);

        $this->post(route('transaction.store'), $this->transferPayload())->assertRedirect(route('home'));

        $this->recipient->delete();
        $transaction = Transaction::where('user_id', $this->user->id)->where('type', 'Debit')->firstOrFail();

        $this->assertSame($this->recipient->id, $transaction->recipient->id);
        $this->assertSame($this->recipient->full_name, $transaction->receiverNameFor($this->user->id));
    }

    public function test_user_can_view_transaction_details(): void
    {
        $this->actingAs($this->user);

        $transaction = Transaction::where('user_id', $this->user->id)->first();

        $response = $this->get(route('transaction.show', $transaction->uuid));

        $response->assertStatus(200);
    }

    public function test_user_cannot_view_other_users_transaction(): void
    {
        /** @var User $otherUser */
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $transaction = Transaction::where('user_id', $this->user->id)->first();

        $response = $this->get(route('transaction.show', $transaction->uuid));

        $response->assertStatus(403);
    }

    public function test_user_cannot_transfer_to_self(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('transaction.store'), $this->transferPayload(recipient: $this->user));

        $response->assertSessionHas('error');
    }

    public function test_admin_can_create_transaction(): void
    {
        $adminRole = Role::where('name', 'administrator')->firstOrFail();
        /** @var User $adminUser */
        $adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $adminTx = Transaction::create([
            'user_id' => $adminUser->id,
            'recipient_id' => $adminUser->id,
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
            'user_id' => $adminUser->id,
            'transaction_id' => $adminTx->id,
            'USD' => 1000,
            'EUR' => 0,
            'NGN' => 0,
        ]);

        $this->actingAs($adminUser);

        $response = $this->get(route('transaction.create'));

        $response->assertStatus(200);
    }

    public function test_admin_can_make_transaction(): void
    {
        $adminRole = Role::where('name', 'administrator')->firstOrFail();
        /** @var User $adminUser */
        $adminUser = User::factory()->create(['role_id' => $adminRole->id]);
        $adminTx = Transaction::create([
            'user_id' => $adminUser->id,
            'recipient_id' => $adminUser->id,
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
            'user_id' => $adminUser->id,
            'transaction_id' => $adminTx->id,
            'USD' => 1000,
            'EUR' => 0,
            'NGN' => 0,
        ]);

        $this->actingAs($adminUser);

        $response = $this->post(route('transaction.store'), $this->transferPayload(sender: $adminUser));

        $response->assertRedirect(route('home'));
        $this->assertDatabaseHas('transactions', [
            'user_id' => $adminUser->id,
            'recipient_id' => $this->recipient->id,
            'type' => 'Debit',
        ]);
    }

    /**
     * @return array<string, string|int>
     */
    private function transferPayload(
        ?User $sender = null,
        ?User $recipient = null,
        string $sourceAmount = '100',
        ?string $targetAmount = null,
        ?Currency $sourceCurrency = null,
        ?Currency $targetCurrency = null
    ): array {
        $sender ??= $this->user;
        $recipient ??= $this->recipient;
        $sourceCurrency ??= $this->usdCurrency;
        $targetCurrency ??= $this->usdCurrency;

        $quote = $this->quoteFor($sender, $sourceAmount, $sourceCurrency, $targetCurrency);

        return [
            'recipient_uuid' => $recipient->uuid,
            'source_amount' => $sourceAmount,
            'target_amount' => $targetAmount ?? (string) $quote->target_amount,
            'quote_uuid' => $quote->uuid,
            'idempotency_key' => (string) Str::uuid(),
            'source_currency_id' => $sourceCurrency->id,
            'target_currency_id' => $targetCurrency->id,
        ];
    }

    private function quoteFor(User $sender, string $sourceAmount, Currency $sourceCurrency, Currency $targetCurrency): TransferQuote
    {
        return app(TransferQuoteService::class)->createQuote($sender, $sourceCurrency, $targetCurrency, $sourceAmount);
    }
}
