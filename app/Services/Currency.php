<?php

namespace App\Services;

class Currency
{
    /**
     * Get flag asset URL for a currency.
     *
     * @param string $currencyId The currency ID
     * @return object Object containing the flag URL
     */
    public function flag(string $currencyId): object
    {
        $flags = [
            '1' => 'european-union.svg',
            '2' => 'nigeria.svg',
            '3' => 'united-states.svg',
        ];

        $filename = $flags[$currencyId] ?? 'uk.svg';

        return (object) [
            'url' => asset("assets/media/flags/{$filename}"),
        ];
    }

    /**
     * Get currency code by ID.
     *
     * @param int $currencyId The currency ID
     * @return string The currency code
     */
    public function getCodeById(int $currencyId): string
    {
        return match ($currencyId) {
            1 => 'EUR',
            2 => 'NGN',
            3 => 'USD',
            default => 'USD',
        };
    }

    /**
     * Get currency ID by code.
     *
     * @param string $code The currency code
     * @return int The currency ID
     */
    public function getIdByCode(string $code): int
    {
        return match (strtoupper($code)) {
            'EUR' => 1,
            'NGN' => 2,
            'USD' => 3,
            default => 3,
        };
    }

    /**
     * Format amount with currency symbol.
     *
     * @param float $amount The amount to format
     * @param string $code The currency code
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

        return $symbol . number_format($amount, 2);
    }
}
