<?php

namespace App\Traits;

trait CanPay
{
    /**
     * Determine if the authenticated user can make a payment.
     *
     * Returns TRUE if the user has sufficient funds.
     * Returns FALSE if insufficient balance or balance is zero.
     *
     * @param string $currencyCode The currency code (USD, EUR, NGN)
     * @param float $amount The amount to pay
     * @return bool True if payment can be made, false otherwise
     */
    public function canMakePayment(string $currencyCode, float $amount): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        $latestCurrencyBalance = $user->latestCurrencyBalance;

        if (!$latestCurrencyBalance) {
            return false;
        }

        $availableBalance = $latestCurrencyBalance->getBalanceForCurrency($currencyCode);

        // User can pay if:
        // 1. Available balance is greater than zero
        // 2. Amount requested is less than or equal to available balance
        return $availableBalance > 0 && $amount <= $availableBalance;
    }

    /**
     * Get the available balance for a specific currency.
     *
     * @param string $currencyCode The currency code (USD, EUR, NGN)
     * @return float The available balance
     */
    public function getAvailableBalance(string $currencyCode): float
    {
        $user = auth()->user();

        if (!$user) {
            return 0.0;
        }

        $latestCurrencyBalance = $user->latestCurrencyBalance;

        if (!$latestCurrencyBalance) {
            return 0.0;
        }

        return $latestCurrencyBalance->getBalanceForCurrency($currencyCode);
    }

    /**
     * Check if the user has any balance in any currency.
     *
     * @return bool True if user has any balance
     */
    public function hasAnyBalance(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        $latestCurrencyBalance = $user->latestCurrencyBalance;

        if (!$latestCurrencyBalance) {
            return false;
        }

        return (float) $latestCurrencyBalance->USD > 0 ||
               (float) $latestCurrencyBalance->EUR > 0 ||
               (float) $latestCurrencyBalance->NGN > 0;
    }
}
