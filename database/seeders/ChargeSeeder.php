<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Charge;
use App\Models\Currency;
use Illuminate\Database\Seeder;

class ChargeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $charges = $this->getCharges();

        foreach ($charges as $charge) {
            Charge::updateOrCreate(
                [
                    'source_currency_id' => $charge['source_currency_id'],
                    'target_currency_id' => $charge['target_currency_id'],
                ],
                $charge
            );
        }

        $this->command->info('Currency charges/rates seeded: '.count($charges).' combinations');
    }

    /**
     * Get the charge configurations.
     * Exchange rates updated as of May 11, 2026 using mid-market rates.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getCharges(): array
    {
        $currencies = Currency::whereIn('code', ['EUR', 'NGN', 'USD'])
            ->pluck('id', 'code');

        return [
            // EUR conversions
            [
                'source_currency_id' => $currencies['EUR'],  // EUR to EUR
                'target_currency_id' => $currencies['EUR'],
                'rate' => 1.0,
                'variable_percentage' => 0,
                'fixed_fee' => 0.10,
            ],
            [
                'source_currency_id' => $currencies['EUR'],  // EUR to NGN
                'target_currency_id' => $currencies['NGN'],
                'rate' => 1600.00,
                'variable_percentage' => 0.35,
                'fixed_fee' => 0.35,
            ],
            [
                'source_currency_id' => $currencies['EUR'],  // EUR to USD
                'target_currency_id' => $currencies['USD'],
                'rate' => 1.151079,
                'variable_percentage' => 0.25,
                'fixed_fee' => 0.30,
            ],

            // NGN conversions
            [
                'source_currency_id' => $currencies['NGN'],  // NGN to EUR
                'target_currency_id' => $currencies['EUR'],
                'rate' => 0.000625,
                'variable_percentage' => 0.30,
                'fixed_fee' => 150.00,
            ],
            [
                'source_currency_id' => $currencies['NGN'],  // NGN to NGN
                'target_currency_id' => $currencies['NGN'],
                'rate' => 1.0,
                'variable_percentage' => 0,
                'fixed_fee' => 100.00,
            ],
            [
                'source_currency_id' => $currencies['NGN'],  // NGN to USD
                'target_currency_id' => $currencies['USD'],
                'rate' => 0.000719,
                'variable_percentage' => 0.30,
                'fixed_fee' => 150.00,
            ],

            // USD conversions
            [
                'source_currency_id' => $currencies['USD'],  // USD to EUR
                'target_currency_id' => $currencies['EUR'],
                'rate' => 0.868075,
                'variable_percentage' => 0.25,
                'fixed_fee' => 0.00,
            ],
            [
                'source_currency_id' => $currencies['USD'],  // USD to NGN
                'target_currency_id' => $currencies['NGN'],
                'rate' => 1390.00,
                'variable_percentage' => 0.35,
                'fixed_fee' => 0.00,
            ],
            [
                'source_currency_id' => $currencies['USD'],  // USD to USD
                'target_currency_id' => $currencies['USD'],
                'rate' => 1.0,
                'variable_percentage' => 0,
                'fixed_fee' => 1.00,
            ],
        ];
    }
}
