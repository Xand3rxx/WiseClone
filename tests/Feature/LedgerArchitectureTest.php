<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\BackupDatabaseJob;
use App\Jobs\ExportImmutableAuditLogJob;
use App\Jobs\ReconcileLedgerJob;
use App\Models\AuditExport;
use App\Models\BackupRun;
use App\Models\Charge;
use App\Models\Currency;
use App\Models\CurrencyBalance;
use App\Models\LedgerEntry;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransferQuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class LedgerArchitectureTest extends TestCase
{
    use RefreshDatabase;

    private User $sender;

    private User $recipient;

    private Currency $usd;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        Currency::create(['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€']);
        Currency::create(['name' => 'Nigerian Naira', 'code' => 'NGN', 'symbol' => '₦']);
        $this->usd = Currency::create(['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$']);

        Role::create(['name' => 'administrator', 'url' => 'administrator']);
        Role::create(['name' => 'customer', 'url' => 'customer']);

        Charge::create([
            'source_currency_id' => $this->usd->id,
            'target_currency_id' => $this->usd->id,
            'rate' => 1,
            'variable_percentage' => 0,
            'fixed_fee' => 1,
        ]);

        $this->sender = User::factory()->create();
        $this->recipient = User::factory()->create();

        $this->seedBalance($this->sender, 1000);
        $this->seedBalance($this->recipient, 500);
    }

    public function test_transfer_persists_quote_idempotency_accounts_and_explicit_ledger_events(): void
    {
        $quote = app(TransferQuoteService::class)->createQuote($this->sender, $this->usd, $this->usd, '100');
        $idempotencyKey = (string) Str::uuid();

        $this->actingAs($this->sender)
            ->post(route('transaction.store'), [
                'recipient_uuid' => $this->recipient->uuid,
                'source_amount' => '100',
                'target_amount' => (string) $quote->target_amount,
                'quote_uuid' => $quote->uuid,
                'idempotency_key' => $idempotencyKey,
                'source_currency_id' => $this->usd->id,
                'target_currency_id' => $this->usd->id,
            ])
            ->assertRedirect(route('home'));

        $this->assertNotNull($quote->fresh()->accepted_at);
        $this->assertDatabaseHas('idempotency_keys', [
            'key' => $idempotencyKey,
            'scope' => 'transfer.store',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('accounts', [
            'user_id' => $this->sender->id,
            'currency_id' => $this->usd->id,
            'balance' => '900.00',
        ]);
        $this->assertDatabaseHas('ledger_entries', [
            'event_type' => LedgerEntry::EVENT_TRANSFER_PENDING,
            'status' => LedgerEntry::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('ledger_entries', [
            'event_type' => LedgerEntry::EVENT_TRANSFER_DEBIT,
            'status' => LedgerEntry::STATUS_POSTED,
            'amount' => '100.00',
        ]);
        $this->assertDatabaseHas('ledger_entries', [
            'event_type' => LedgerEntry::EVENT_TRANSFER_SETTLED,
            'status' => LedgerEntry::STATUS_SETTLED,
        ]);
    }

    public function test_duplicate_idempotency_key_does_not_create_duplicate_transfer(): void
    {
        $quote = app(TransferQuoteService::class)->createQuote($this->sender, $this->usd, $this->usd, '100');
        $payload = [
            'recipient_uuid' => $this->recipient->uuid,
            'source_amount' => '100',
            'target_amount' => (string) $quote->target_amount,
            'quote_uuid' => $quote->uuid,
            'idempotency_key' => (string) Str::uuid(),
            'source_currency_id' => $this->usd->id,
            'target_currency_id' => $this->usd->id,
        ];

        $this->actingAs($this->sender)->post(route('transaction.store'), $payload)->assertRedirect(route('home'));
        $this->actingAs($this->sender)->post(route('transaction.store'), $payload)->assertRedirect(route('home'));

        $this->assertSame(2, Transaction::whereNotNull('transfer_group_uuid')->count());
    }

    public function test_operational_jobs_create_reconciliation_audit_export_and_backup_records(): void
    {
        Storage::fake('local');

        $quote = app(TransferQuoteService::class)->createQuote($this->sender, $this->usd, $this->usd, '100');
        $this->actingAs($this->sender)->post(route('transaction.store'), [
            'recipient_uuid' => $this->recipient->uuid,
            'source_amount' => '100',
            'target_amount' => (string) $quote->target_amount,
            'quote_uuid' => $quote->uuid,
            'idempotency_key' => (string) Str::uuid(),
            'source_currency_id' => $this->usd->id,
            'target_currency_id' => $this->usd->id,
        ]);

        (new ReconcileLedgerJob)->handle();
        (new ExportImmutableAuditLogJob)->handle();
        (new BackupDatabaseJob)->handle();

        $this->assertDatabaseHas('reconciliation_runs', [
            'status' => 'passed',
            'mismatches_found' => 0,
        ]);
        $this->assertSame(1, AuditExport::where('status', 'completed')->count());
        $this->assertSame(1, BackupRun::where('status', 'completed')->count());
        Storage::assertExists(AuditExport::firstOrFail()->path);
        Storage::assertExists(BackupRun::firstOrFail()->path);
    }

    private function seedBalance(User $user, float $amount): void
    {
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'recipient_id' => $user->id,
            'source_currency_id' => $this->usd->id,
            'target_currency_id' => $this->usd->id,
            'amount' => $amount,
            'rate' => 1,
            'transfer_fee' => 0,
            'variable_fee' => 0,
            'fixed_fee' => 0,
            'type' => Transaction::TYPE['Credit'],
            'status' => Transaction::STATUS['Success'],
        ]);

        CurrencyBalance::create([
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
            'USD' => $amount,
            'EUR' => 0,
            'NGN' => 0,
        ]);
    }
}
