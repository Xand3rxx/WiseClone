<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait ExchangeRate
{
    /**
     * Get current exchange rate using external API.
     *
     * @param string $sourceCurrency Source currency code
     * @param string $targetCurrency Target currency code
     * @param float $sourceAmount Source amount (not used in rate calculation)
     * @return float|null The exchange rate or null if unavailable
     */
    public static function currentExchangeRate(string $sourceCurrency, string $targetCurrency, float $sourceAmount): ?float
    {
        // Return 1 for same currency
        if ($sourceCurrency === $targetCurrency) {
            return 1.0;
        }

        // Get API key from config
        $apiKey = config('services.currency_converter.key');

        // If no API key configured, return null to use fallback rate
        if (empty($apiKey)) {
            return null;
        }

        try {
            $fromCurrency = urlencode($sourceCurrency);
            $toCurrency = urlencode($targetCurrency);
            $query = "{$fromCurrency}_{$toCurrency}";

            $response = Http::timeout(5)->get("https://free.currconv.com/api/v7/convert", [
                'q' => $query,
                'compact' => 'ultra',
                'apiKey' => $apiKey,
            ]);

            if ($response->failed()) {
                Log::warning('Currency API request failed', [
                    'status' => $response->status(),
                    'query' => $query,
                ]);
                return null;
            }

            $data = $response->json();

            if (!isset($data[$query])) {
                Log::warning('Currency API returned unexpected response', [
                    'response' => $data,
                    'query' => $query,
                ]);
                return null;
            }

            $rate = (float) $data[$query];

            return $rate > 0 ? round($rate, 6) : null;
        } catch (\Exception $e) {
            Log::error('Currency exchange rate error', [
                'message' => $e->getMessage(),
                'source' => $sourceCurrency,
                'target' => $targetCurrency,
            ]);
            return null;
        }
    }

    /**
     * Get exchange rate with fallback to stored rate.
     *
     * @param float $fallbackRate The fallback rate from database
     * @param string $sourceCurrency Source currency code
     * @param string $targetCurrency Target currency code
     * @param float $sourceAmount Source amount
     * @return float The exchange rate
     */
    public static function getExchangeRateWithFallback(
        float $fallbackRate,
        string $sourceCurrency,
        string $targetCurrency,
        float $sourceAmount
    ): float {
        $currentRate = self::currentExchangeRate($sourceCurrency, $targetCurrency, $sourceAmount);

        return $currentRate ?? $fallbackRate;
    }
}
