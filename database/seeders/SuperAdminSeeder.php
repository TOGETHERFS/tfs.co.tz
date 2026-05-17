<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Create the Super Admin account and assign the role.
     */
    public function run(): void
    {
        // Locate an active tenant to attach the super admin account to
        $tenant = Tenant::where('slug', 'phidtech-demo')->first();
        if (!$tenant) {
            $tenant = Tenant::where('status', 'active')->first();
        }

        if (!$tenant) {
            $this->command?->error('No active tenant found. Create a tenant before seeding Super Admin.');
            return;
        }

        // Create or update the Super Admin user
        $email = env('SUPERADMIN_EMAIL', 'phidtechnology@gmail.com');
        $password = env('SUPERADMIN_PASSWORD', 'Dyna@@2023');
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'tenant_id' => $tenant->id,
                'name' => env('SUPERADMIN_NAME', 'Phid Technology Super Admin'),
                'password' => Hash::make($password),
                // users.role enum does not include superadmin; set to admin for compatibility
                'role' => 'admin',
                'email_verified_at' => now(),
                'position' => 'superadmin',
            ]
        );

        // Ensure the Super Admin role exists for this tenant
        $role = Role::firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'superadmin'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Super Admin',
                'category' => 'system',
                'is_system' => true,
            ]
        );

        // Attach the role to the user via the pivot with tenant_id
        DB::table('user_role')->updateOrInsert(
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role_id' => $role->id,
            ],
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role_id' => $role->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command?->info('Super Admin user created/updated and role assigned.');
    }
}