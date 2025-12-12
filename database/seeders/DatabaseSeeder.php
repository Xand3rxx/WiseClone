<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CurrencySeeder::class,
            RoleSeeder::class,
            ChargeSeeder::class,
            UserSeeder::class,
            TransactionSeeder::class,
        ]);

        $this->command->info('Database seeded successfully!');
        $this->command->info('');
        $this->command->info('Demo Credentials:');
        $this->command->info('Admin: admin@wiseclone.com / password');
        $this->command->info('User: user@wiseclone.com / password');
    }
}
