<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Money;
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
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Get the transaction associated with this balance.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class)->withTrashed();
    }

    /**
     * Get balance for a specific currency code.
     */
    public function getBalanceForCurrency(string $code): float
    {
        $currency = Currency::where('code', strtoupper($code))->first();
        $accountBalance = $currency
            ? Account::where('user_id', $this->user_id)
                ->where('currency_id', $currency->id)
                ->where('type', Account::TYPE_CUSTOMER)
                ->value('balance')
            : null;

        if ($accountBalance !== null) {
            return (float) $accountBalance;
        }

        return match (strtoupper($code)) {
            'USD' => (float) $this->USD,
            'EUR' => (float) $this->EUR,
            'NGN' => (float) $this->NGN,
            default => 0.0,
        };
    }

    /**
     * Get total balance in USD equivalent.
     * Uses managed rates, with May 2026 fallback rates if rates are not seeded yet.
     */
    public function getTotalBalanceAttribute(): float
    {
        $eurInUsd = Money::multiplyByRate($this->EUR, (string) $this->rateToUsd('EUR', 1.151079));
        $ngnInUsd = Money::multiplyByRate($this->NGN, (string) $this->rateToUsd('NGN', 0.000719));

        return (float) Money::add(Money::add($this->USD, $eurInUsd), $ngnInUsd);
    }

    private function rateToUsd(string $sourceCurrency, float $fallbackRate): float
    {
        $rate = Charge::query()
            ->whereHas('sourceCurrency', fn ($query) => $query->where('code', $sourceCurrency))
            ->whereHas('targetCurrency', fn ($query) => $query->where('code', 'USD'))
            ->value('rate');

        return $rate !== null ? (float) $rate : $fallbackRate;
    }
}
