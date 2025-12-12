<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'administrator')->first();
        $customerRole = Role::where('name', 'customer')->first();
        $usdCurrency = Currency::where('code', 'USD')->first();

        // Create admin user
        User::updateOrCreate(
            ['email' => 'admin@wiseclone.com'],
            [
                'role_id' => $adminRole->id,
                'currency_id' => $usdCurrency->id,
                'name' => 'WiseClone Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create main demo user
        User::updateOrCreate(
            ['email' => 'user@wiseclone.com'],
            [
                'role_id' => $customerRole->id,
                'currency_id' => $usdCurrency->id,
                'name' => 'Anthony Joboy',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create additional demo users with diverse names
        $demoUsers = [
            ['name' => 'Sarah Johnson', 'email' => 'sarah.johnson@example.com'],
            ['name' => 'Michael Chen', 'email' => 'michael.chen@example.com'],
            ['name' => 'Emma Williams', 'email' => 'emma.williams@example.com'],
            ['name' => 'David Okonkwo', 'email' => 'david.okonkwo@example.com'],
            ['name' => 'Fatima Ahmed', 'email' => 'fatima.ahmed@example.com'],
            ['name' => 'James Rodriguez', 'email' => 'james.rodriguez@example.com'],
            ['name' => 'Aisha Bello', 'email' => 'aisha.bello@example.com'],
            ['name' => 'Robert Kim', 'email' => 'robert.kim@example.com'],
            ['name' => 'Chioma Eze', 'email' => 'chioma.eze@example.com'],
            ['name' => 'Maria Garcia', 'email' => 'maria.garcia@example.com'],
            ['name' => 'John Smith', 'email' => 'john.smith@example.com'],
            ['name' => 'Ngozi Okafor', 'email' => 'ngozi.okafor@example.com'],
            ['name' => 'Li Wei', 'email' => 'li.wei@example.com'],
            ['name' => 'Priya Sharma', 'email' => 'priya.sharma@example.com'],
            ['name' => 'Ahmed Hassan', 'email' => 'ahmed.hassan@example.com'],
        ];

        foreach ($demoUsers as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'role_id' => $customerRole->id,
                    'currency_id' => $usdCurrency->id,
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }

        // Generate additional random users using factory
        User::factory(5)->create();

        $this->command->info('Users seeded: 1 admin + ' . (count($demoUsers) + 6) . ' customers');
    }
}
