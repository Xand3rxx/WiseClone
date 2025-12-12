<?php

namespace Tests\Unit;

use App\Models\Charge;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChargeTest extends TestCase
{
    use RefreshDatabase;

    protected Currency $eurCurrency;
    protected Currency $usdCurrency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eurCurrency = Currency::create(['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€']);
        Currency::create(['name' => 'Nigerian Naira', 'code' => 'NGN', 'symbol' => '₦']);
        $this->usdCurrency = Currency::create(['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$']);
    }

    public function test_charge_has_uuid_on_creation(): void
    {
        $charge = Charge::create([
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->usdCurrency->id,
            'rate' => 1.0,
            'variable_percentage' => 0,
            'fixed_fee' => 4.86,
        ]);

        $this->assertNotNull($charge->uuid);
    }

    public function test_charge_belongs_to_source_currency(): void
    {
        $charge = Charge::create([
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->eurCurrency->id,
            'rate' => 0.92,
            'variable_percentage' => 0.42,
            'fixed_fee' => 4.67,
        ]);

        $this->assertInstanceOf(Currency::class, $charge->sourceCurrency);
        $this->assertEquals('USD', $charge->sourceCurrency->code);
    }

    public function test_charge_belongs_to_target_currency(): void
    {
        $charge = Charge::create([
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->eurCurrency->id,
            'rate' => 0.92,
            'variable_percentage' => 0.42,
            'fixed_fee' => 4.67,
        ]);

        $this->assertInstanceOf(Currency::class, $charge->targetCurrency);
        $this->assertEquals('EUR', $charge->targetCurrency->code);
    }

    public function test_calculate_transfer_fee(): void
    {
        $charge = Charge::create([
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->eurCurrency->id,
            'rate' => 0.92,
            'variable_percentage' => 0.5,
            'fixed_fee' => 5.00,
        ]);

        // For $100: variable fee = 0.5% of 100 = $0.50, total = $0.50 + $5.00 = $5.50
        $this->assertEquals(5.50, $charge->calculateTransferFee(100));

        // For $1000: variable fee = 0.5% of 1000 = $5.00, total = $5.00 + $5.00 = $10.00
        $this->assertEquals(10.00, $charge->calculateTransferFee(1000));
    }

    public function test_calculate_target_amount(): void
    {
        $charge = Charge::create([
            'source_currency_id' => $this->usdCurrency->id,
            'target_currency_id' => $this->eurCurrency->id,
            'rate' => 0.92,
            'variable_percentage' => 0.5,
            'fixed_fee' => 5.00,
        ]);

        // For $100: fee = $5.50, amount to convert = $94.50, target = 94.50 * 0.92 = 86.94
        $targetAmount = $charge->calculateTargetAmount(100);
        $this->assertEquals(86.94, round($targetAmount, 2));
    }
}

