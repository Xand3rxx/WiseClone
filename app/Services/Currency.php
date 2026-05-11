<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Currency as CurrencyModel;

class Currency
{
    /**
     * Get flag asset URL for a currency.
     *
     * @param  string  $currencyId  The currency ID
     * @return object Object containing the flag URL
     */
    public function flag(string $currencyId): object
    {
        $currency = CurrencyModel::find($currencyId);

        $flags = [
            'EUR' => 'european-union.svg',
            'NGN' => 'nigeria.svg',
            'USD' => 'united-states.svg',
        ];

        $filename = $flags[$currency?->code] ?? 'uk.svg';

        return (object) [
            'url' => asset("assets/media/flags/{$filename}"),
        ];
    }

    /**
     * Get currency code by ID.
     *
     * @param  int  $currencyId  The currency ID
     * @return string The currency code
     */
    public function getCodeById(int $currencyId): string
    {
        return CurrencyModel::find($currencyId)?->code ?? 'USD';
    }

    /**
     * Get currency ID by code.
     *
     * @param  string  $code  The currency code
     * @return int The currency ID
     */
    public function getIdByCode(string $code): int
    {
        return CurrencyModel::where('code', strtoupper($code))->value('id')
            ?? CurrencyModel::where('code', 'USD')->value('id')
            ?? 0;
    }

    /**
     * Format amount with currency symbol.
     *
     * @param  float  $amount  The amount to format
     * @param  string  $code  The currency code
     * @return string Formatted amount with symbol
     */
    public function formatAmount(float $amount, string $code): string
    {
        $symbols = [
            'EUR' => '€',
            'NGN' => '₦',
            'USD' => '$',
        ];

        $symbol = $symbols[strtoupper($code)] ?? '$';

        return $symbol.number_format($amount, 2);
    }
}
