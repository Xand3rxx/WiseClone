<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Account extends Model
{
    public const TYPE_CUSTOMER = 'customer';

    protected $fillable = [
        'user_id',
        'currency_id',
        'type',
        'balance',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Account $account): void {
            $account->uuid = (string) Str::uuid();
            $account->type ??= self::TYPE_CUSTOMER;
            $account->balance = Money::round($account->balance ?? '0');
        });
    }

    public static function forUserCurrency(User $user, Currency $currency, string $type = self::TYPE_CUSTOMER): self
    {
        return self::firstOrCreate(
            [
                'user_id' => $user->id,
                'currency_id' => $currency->id,
                'type' => $type,
            ],
            ['balance' => '0.00']
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }
}
