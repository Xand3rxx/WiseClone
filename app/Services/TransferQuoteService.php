<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Charge;
use App\Models\Currency;
use App\Models\TransferQuote;
use App\Models\User;
use App\Support\Money;
use App\Traits\ExchangeRate;
use RuntimeException;

class TransferQuoteService
{
    use ExchangeRate;

    public function createQuote(
        User $user,
        Currency $sourceCurrency,
        Currency $targetCurrency,
        float|string $sourceAmount,
        ?User $recipient = null
    ): TransferQuote {
        $charge = Charge::where('source_currency_id', $sourceCurrency->id)
            ->where('target_currency_id', $targetCurrency->id)
            ->firstOrFail();

        $fixedFee = Money::round($charge->fixed_fee);
        $variableFee = Money::percentage($sourceAmount, $charge->variable_percentage);
        $transferFee = Money::add($variableFee, $fixedFee);
        $amountToConvert = Money::maxZero(Money::subtract($sourceAmount, $transferFee));
        $rate = Money::assertDecimal(
            $this->currentExchangeRate($sourceCurrency->code, $targetCurrency->code, (float) $sourceAmount)
                ?? (string) $charge->rate
        );
        $targetAmount = Money::multiplyByRate($amountToConvert, $rate);

        return TransferQuote::create([
            'user_id' => $user->id,
            'recipient_id' => $recipient?->id,
            'source_currency_id' => $sourceCurrency->id,
            'target_currency_id' => $targetCurrency->id,
            'source_amount' => Money::round($sourceAmount),
            'target_amount' => $targetAmount,
            'rate' => $rate,
            'fixed_fee' => $fixedFee,
            'variable_fee' => $variableFee,
            'transfer_fee' => $transferFee,
            'amount_to_convert' => $amountToConvert,
            'fee_breakdown' => [
                'source_currency' => $sourceCurrency->code,
                'target_currency' => $targetCurrency->code,
                'fixed_fee' => $fixedFee,
                'variable_fee' => $variableFee,
                'transfer_fee' => $transferFee,
                'amount_to_convert' => $amountToConvert,
                'rate' => $rate,
            ],
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    public function usableQuoteFor(User $user, string $uuid): TransferQuote
    {
        $quote = TransferQuote::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($quote->isExpired()) {
            throw new RuntimeException('The quote has expired. Please request a new quote and try again.');
        }

        return $quote;
    }

    /**
     * @return array<string, string>
     */
    public function summaryFor(TransferQuote $quote): array
    {
        $variablePercentage = Charge::query()
            ->where('source_currency_id', $quote->source_currency_id)
            ->where('target_currency_id', $quote->target_currency_id)
            ->value('variable_percentage') ?? '0';

        return [
            'transferFee' => number_format((float) $quote->transfer_fee, 2),
            'amountToConvert' => number_format((float) $quote->amount_to_convert, 2),
            'fixedFee' => number_format((float) $quote->fixed_fee, 2),
            'variableFeeText' => number_format((float) $quote->variable_fee, 2).' '.$quote->sourceCurrency->code.' ('.(string) $variablePercentage.'%)',
            'variableFee' => number_format((float) $quote->variable_fee, 2),
            'rate' => (string) $quote->rate,
        ];
    }
}
