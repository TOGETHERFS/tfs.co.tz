<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('plans')->upsert([
            [
                'code' => 'free_trial',
                'name' => 'Free Trial',
                'period' => 'monthly',
                'price' => 0,
                'currency' => 'TZS',
                'is_active' => true,
                'staff_limit' => 2,
                'features' => json_encode([
                    '3-day free trial',
                    'Up to 2 staff members',
                    'Unlimited clients',
                    'Basic loan management',
                    'Payment tracking',
                    'Basic reporting',
                    'Email support',
                    'Mobile responsive interface',
                    'No credit card required',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'starter',
                'name' => 'Starter',
                'period' => 'monthly',
                'price' => 30000,
                'currency' => 'TZS',
                'is_active' => true,
                'staff_limit' => 5,
                'features' => json_encode([
                    'Up to 5 staff members',
                    'Unlimited clients',
                    'Basic loan management',
                    'Payment tracking',
                    'Basic reporting',
                    'Email support',
                    'Mobile responsive interface',
                    'Data backup',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'growth',
                'name' => 'Growth',
                'period' => 'monthly',
                'price' => 80000,
                'currency' => 'TZS',
                'is_active' => true,
                'staff_limit' => 10,
                'features' => json_encode([
                    'Up to 10 staff members',
                    'Unlimited clients',
                    'Advanced loan management',
                    'Payment tracking & automation',
                    'Advanced reporting & analytics',
                    'Priority email support',
                    'Mobile responsive interface',
                    'Daily data backup',
                    'Multi-currency support',
                    'Custom loan products',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'enterprise',
                'name' => 'Enterprise',
                'period' => 'monthly',
                'price' => 100000,
                'currency' => 'TZS',
                'is_active' => true,
                'staff_limit' => 20,
                'features' => json_encode([
                    'Up to 20 staff members',
                    'Unlimited clients',
                    'Full loan management suite',
                    'Automated payment processing',
                    'Comprehensive reporting & analytics',
                    'Priority phone & email support',
                    'Mobile responsive interface',
                    'Real-time data backup',
                    'Multi-currency support',
                    'Custom loan products',
                    'White-label options',
                    'Dedicated account manager',
                    'Custom integrations',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['code']);
    }
}