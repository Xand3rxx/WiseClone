<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Charge extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_currency_id',
        'target_currency_id',
        'rate',
        'variable_percentage',
        'fixed_fee',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:6',
            'variable_percentage' => 'decimal:4',
            'fixed_fee' => 'decimal:4',
        ];
    }

    /**
     * Model event to trigger action on creating.
     */
    protected static function booted(): void
    {
        static::creating(function (Charge $charge): void {
            // Generate unique uuid for a new charge.
            $charge->uuid = (string) Str::uuid();
        });
    }

    /**
     * Get the source currency.
     */
    public function sourceCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'source_currency_id');
    }

    /**
     * Get the target currency.
     */
    public function targetCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'target_currency_id');
    }

    /**
     * Calculate the transfer fee for a given amount.
     */
    public function calculateTransferFee(float $amount): float
    {
        $variableFee = ($this->variable_percentage / 100) * $amount;
        return $variableFee + (float) $this->fixed_fee;
    }

    /**
     * Calculate the target amount after fees and conversion.
     */
    public function calculateTargetAmount(float $sourceAmount): float
    {
        $transferFee = $this->calculateTransferFee($sourceAmount);
        $amountToConvert = $sourceAmount - $transferFee;
        return $amountToConvert * (float) $this->rate;
    }
}
