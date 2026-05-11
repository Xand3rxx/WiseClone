<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Currency::create(['name' => 'Euro', 'code' => 'EUR', 'symbol' => '€']);
        Currency::create(['name' => 'Nigerian Naira', 'code' => 'NGN', 'symbol' => '₦']);
        Currency::create(['name' => 'US Dollar', 'code' => 'USD', 'symbol' => '$']);

        Role::create(['name' => 'administrator', 'url' => 'administrator']);
        Role::create(['name' => 'customer', 'url' => 'customer']);

        $this->user = User::factory()->create([
            'password' => Hash::make('OldWiseClone123!'),
        ]);
    }

    public function test_user_can_view_change_password_page(): void
    {
        $response = $this->actingAs($this->user)->get('/account/password');

        $response->assertStatus(200);
        $response->assertSee('Change Password');
    }

    public function test_user_can_change_password(): void
    {
        $response = $this->actingAs($this->user)->patch('/account/password', [
            'current_password' => 'OldWiseClone123!',
            'password' => 'NewWiseClone123!',
            'password_confirmation' => 'NewWiseClone123!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('NewWiseClone123!', $this->user->fresh()->password));
    }

    public function test_user_cannot_change_password_with_wrong_current_password(): void
    {
        $response = $this->actingAs($this->user)->patch('/account/password', [
            'current_password' => 'WrongWiseClone123!',
            'password' => 'NewWiseClone123!',
            'password_confirmation' => 'NewWiseClone123!',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('OldWiseClone123!', $this->user->fresh()->password));
    }
}
