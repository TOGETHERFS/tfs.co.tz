<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Check if admin user already exists
        $existingUser = DB::table('users')->where('email', 'admin@phidtech.com')->first();
        
        if (!$existingUser) {
            DB::table('users')->insert([
                'name' => 'Admin User',
                'email' => 'admin@phidtech.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'tenant_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            echo "Admin user created successfully!\n";
            echo "Email: admin@phidtech.com\n";
            echo "Password: password\n";
        } else {
            echo "Admin user already exists!\n";
        }
        
        // Also create a regular user
        $existingRegularUser = DB::table('users')->where('email', 'user@phidtech.com')->first();
        
        if (!$existingRegularUser) {
            DB::table('users')->insert([
                'name' => 'Regular User',
                'email' => 'user@phidtech.com',
                'password' => Hash::make('password'),
                'role' => 'user',
                'tenant_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            echo "Regular user created successfully!\n";
            echo "Email: user@phidtech.com\n";
            echo "Password: password\n";
        } else {
            echo "Regular user already exists!\n";
        }
    }
}