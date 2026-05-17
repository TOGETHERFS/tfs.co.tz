<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a demo plan if none exists
        $plan = Plan::first();
        if (!$plan) {
            $plan = Plan::create([
                'name' => 'Demo Plan',
                'price' => 0,
                'features' => json_encode(['Demo features']),
                'max_users' => 10,
                'max_clients' => 100,
            ]);
        }

        // Create or get demo tenant
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'phidtech-demo'],
            [
                'name' => 'PhidTech Demo Organization',
                'contact_email' => 'admin@phidtech.com',
                'phone' => '+1234567890',
                'status' => 'active',
                'plan_id' => $plan->id,
                'trial_ends_at' => now()->addDays(3),
            ]
        );

        // Create demo admin user if not exists
        User::updateOrCreate(
            ['email' => 'bagokap.8275@gmail.com'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Admin User',
                'password' => Hash::make('Phidtech@@2023'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Create demo regular user if not exists
        User::updateOrCreate(
            ['email' => 'user@phidtech.com'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Regular User',
                'password' => Hash::make('password'),
                'role' => 'officer',
                'email_verified_at' => now(),
            ]
        );
    }
}
