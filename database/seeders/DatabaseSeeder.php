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
            PlansSeeder::class,
            UserSeeder::class,
            LoanProductsSeeder::class,
            DemoLoanSeeder::class,
            RbacSeeder::class,
            // Register Super Admin seeding
            SuperAdminSeeder::class,
        ]);
    }
}