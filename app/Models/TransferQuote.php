<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TransferQuote extends Model
{
    protected $fillable = [
        'user_id',
        'recipient_id',
        'source_currency_id',
        'target_currency_id',
        'source_amount',
        'target_amount',
        'rate',
        'fixed_fee',
        'variable_fee',
        'transfer_fee',
        'amount_to_convert',
        'fee_breakdown',
        'accepted_metadata',
        'expires_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'source_amount' => 'decimal:2',
            'target_amount' => 'decimal:2',
            'rate' => 'decimal:8',
            'fixed_fee' => 'decimal:2',
            'variable_fee' => 'decimal:2',
            'transfer_fee' => 'decimal:2',
            'amount_to_convert' => 'decimal:2',
            'fee_breakdown' => 'array',
            'accepted_metadata' => 'array',
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TransferQuote $quote): void {
            $quote->uuid = (string) Str::uuid();
        });
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id')->withTrashed();
    }

    public function sourceCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'source_currency_id');
    }

    public function targetCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'target_currency_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
