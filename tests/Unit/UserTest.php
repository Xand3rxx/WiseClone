<?php

namespace Tests\Unit;

use App\Models\Currency;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Currency::create(['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$']);
        Role::create(['name' => 'administrator', 'url' => 'administrator']);
        Role::create(['name' => 'customer', 'url' => 'customer']);
    }

    public function test_user_has_uuid_on_creation(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->uuid);
        $this->assertIsString($user->uuid);
    }

    public function test_user_belongs_to_role(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->role);
        $this->assertInstanceOf(Role::class, $user->role);
    }

    public function test_user_belongs_to_currency(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->currency);
        $this->assertInstanceOf(Currency::class, $user->currency);
    }

    public function test_user_full_name_attribute(): void
    {
        $user = User::factory()->create(['name' => 'john doe']);

        $this->assertEquals('John doe', $user->full_name);
    }

    public function test_user_is_admin_method(): void
    {
        $adminUser = User::factory()->create(['role_id' => 1]);
        $customerUser = User::factory()->create(['role_id' => 2]);

        $this->assertTrue($adminUser->isAdmin());
        $this->assertFalse($customerUser->isAdmin());
    }

    public function test_user_is_customer_method(): void
    {
        $adminUser = User::factory()->create(['role_id' => 1]);
        $customerUser = User::factory()->create(['role_id' => 2]);

        $this->assertFalse($adminUser->isCustomer());
        $this->assertTrue($customerUser->isCustomer());
    }
}

