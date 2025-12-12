<?php

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

        $this->command->info('Currency charges/rates seeded: ' . count($charges) . ' combinations');
    }

    /**
     * Get the charge configurations.
     *
     * @return array<int, array<string, mixed>>
     */
    private function getCharges(): array
    {
        return [
            // EUR conversions
            [
                'source_currency_id' => 1,  // EUR to EUR
                'target_currency_id' => 1,
                'rate' => 1.0,
                'variable_percentage' => 0,
                'fixed_fee' => 0.41,
            ],
            [
                'source_currency_id' => 1,  // EUR to NGN
                'target_currency_id' => 2,
                'rate' => 1650.50,  // Updated realistic rate
                'variable_percentage' => 0.57,
                'fixed_fee' => 0.71,
            ],
            [
                'source_currency_id' => 1,  // EUR to USD
                'target_currency_id' => 3,
                'rate' => 1.085,  // Updated realistic rate
                'variable_percentage' => 0.41,
                'fixed_fee' => 0.58,
            ],

            // NGN conversions
            [
                'source_currency_id' => 2,  // NGN to EUR
                'target_currency_id' => 1,
                'rate' => 0.000606,  // Updated realistic rate
                'variable_percentage' => 0.55,
                'fixed_fee' => 500.00,
            ],
            [
                'source_currency_id' => 2,  // NGN to NGN
                'target_currency_id' => 2,
                'rate' => 1.0,
                'variable_percentage' => 0,
                'fixed_fee' => 250.00,
            ],
            [
                'source_currency_id' => 2,  // NGN to USD
                'target_currency_id' => 3,
                'rate' => 0.000658,  // Updated realistic rate
                'variable_percentage' => 0.55,
                'fixed_fee' => 400.00,
            ],

            // USD conversions
            [
                'source_currency_id' => 3,  // USD to EUR
                'target_currency_id' => 1,
                'rate' => 0.922,  // Updated realistic rate
                'variable_percentage' => 0.42,
                'fixed_fee' => 4.67,
            ],
            [
                'source_currency_id' => 3,  // USD to NGN
                'target_currency_id' => 2,
                'rate' => 1520.00,  // Updated realistic rate
                'variable_percentage' => 0.59,
                'fixed_fee' => 5.01,
            ],
            [
                'source_currency_id' => 3,  // USD to USD
                'target_currency_id' => 3,
                'rate' => 1.0,
                'variable_percentage' => 0,
                'fixed_fee' => 4.86,
            ],
        ];
    }
}
