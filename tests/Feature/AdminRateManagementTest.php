<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Charge;
use App\Models\Currency;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRateManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $customer;

    protected Charge $charge;

    protected function setUp(): void
    {
        parent::setUp();

        $usd = Currency::create(['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$']);
        $ngn = Currency::create(['name' => 'Nigerian Naira', 'code' => 'NGN', 'symbol' => '₦']);

        Role::create(['name' => 'administrator', 'url' => 'administrator']);
        Role::create(['name' => 'customer', 'url' => 'customer']);

        $this->admin = User::factory()->admin()->create();
        $this->customer = User::factory()->customer()->create();

        $this->charge = Charge::create([
            'source_currency_id' => $usd->id,
            'target_currency_id' => $ngn->id,
            'rate' => 1390,
            'variable_percentage' => 0.35,
            'fixed_fee' => 2.50,
        ]);
    }

    public function test_admin_can_view_rate_management_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.rates.index'));

        $response->assertStatus(200);
        $response->assertSee('Rate Management');
        $response->assertSeeText('USD -> NGN');
    }

    public function test_customer_cannot_view_rate_management_page(): void
    {
        $response = $this->actingAs($this->customer)->get(route('admin.rates.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_update_rate_and_fees(): void
    {
        $response = $this->actingAs($this->admin)->patch(route('admin.rates.update', $this->charge), [
            'rate' => 1405.25,
            'variable_percentage' => 0.22,
            'fixed_fee' => 1.75,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->charge->refresh();
        $this->assertSame('1405.250000', $this->charge->rate);
        $this->assertSame('0.2200', $this->charge->variable_percentage);
        $this->assertSame('1.7500', $this->charge->fixed_fee);
    }

    public function test_customer_cannot_update_rate_and_fees(): void
    {
        $response = $this->actingAs($this->customer)->patch(route('admin.rates.update', $this->charge), [
            'rate' => 1405.25,
            'variable_percentage' => 0.22,
            'fixed_fee' => 1.75,
        ]);

        $response->assertStatus(403);
    }
}
