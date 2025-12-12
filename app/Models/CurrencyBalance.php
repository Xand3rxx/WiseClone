<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurrencyBalance extends Model
{
    use HasFactory;

    protected $table = 'currency_balances';

    protected $guarded = ['created_at', 'updated_at'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'USD' => 'decimal:2',
            'EUR' => 'decimal:2',
            'NGN' => 'decimal:2',
        ];
    }

    /**
     * Get the user associated with this balance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transaction associated with this balance.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Get balance for a specific currency code.
     */
    public function getBalanceForCurrency(string $code): float
    {
        return match (strtoupper($code)) {
            'USD' => (float) $this->USD,
            'EUR' => (float) $this->EUR,
            'NGN' => (float) $this->NGN,
            default => 0.0,
        };
    }

    /**
     * Get total balance in USD equivalent.
     */
    public function getTotalBalanceAttribute(): float
    {
        // Simple conversion - in production, use real exchange rates
        return (float) $this->USD + ((float) $this->EUR * 1.09) + ((float) $this->NGN * 0.0026);
    }
}
