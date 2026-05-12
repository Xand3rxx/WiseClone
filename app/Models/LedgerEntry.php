<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LedgerEntry extends Model
{
    public const DIRECTION_DEBIT = 'debit';

    public const DIRECTION_CREDIT = 'credit';

    public const STATUS_PENDING = 'pending';

    public const STATUS_POSTED = 'posted';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REVERSED = 'reversed';

    public const STATUS_SETTLED = 'settled';

    public const STATUS_REFUNDED = 'refunded';

    public const EVENT_TRANSFER_PENDING = 'transfer_pending';

    public const EVENT_TRANSFER_DEBIT = 'transfer_debit';

    public const EVENT_TRANSFER_CREDIT = 'transfer_credit';

    public const EVENT_TRANSFER_SETTLED = 'transfer_settled';

    public const EVENT_TRANSFER_FAILED = 'transfer_failed';

    public const EVENT_TRANSFER_REVERSED = 'transfer_reversed';

    public const EVENT_TRANSFER_REFUNDED = 'transfer_refunded';

    public const EVENT_ACCOUNT_FUNDING = 'account_funding';

    protected $fillable = [
        'ledger_event_uuid',
        'transfer_group_uuid',
        'account_id',
        'user_id',
        'currency_id',
        'transaction_id',
        'transfer_quote_id',
        'idempotency_key_id',
        'event_type',
        'direction',
        'status',
        'amount',
        'balance_after',
        'metadata',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'metadata' => 'array',
            'posted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (LedgerEntry $entry): void {
            $entry->uuid = (string) Str::uuid();
            $entry->amount = Money::round($entry->amount ?? '0');
            $entry->balance_after = Money::round($entry->balance_after ?? '0');
        });
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class)->withTrashed();
    }

    public function transferQuote(): BelongsTo
    {
        return $this->belongsTo(TransferQuote::class);
    }

    public function idempotencyKey(): BelongsTo
    {
        return $this->belongsTo(IdempotencyKey::class);
    }
}
