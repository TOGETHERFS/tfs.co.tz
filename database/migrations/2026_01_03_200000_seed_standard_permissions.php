<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            ['name' => 'Dashboard', 'slug' => 'dashboard', 'description' => 'Access to dashboard'],
            ['name' => 'Borrowers', 'slug' => 'borrowers', 'description' => 'Manage borrowers/clients'],
            ['name' => 'Loans', 'slug' => 'loans', 'description' => 'Manage loans'],
            ['name' => 'Repayments', 'slug' => 'repayments', 'description' => 'Manage repayments'],
            ['name' => 'Reports', 'slug' => 'reports', 'description' => 'Access reports'],
            ['name' => 'Staff & Roles', 'slug' => 'staff-roles', 'description' => 'Manage staff and roles'],
            ['name' => 'Branches', 'slug' => 'branches', 'description' => 'Manage branches'],
            ['name' => 'Billing', 'slug' => 'billing', 'description' => 'Access billing and subscription'],
            ['name' => 'Settings', 'slug' => 'settings', 'description' => 'Access organization settings'],
            ['name' => 'Messages', 'slug' => 'messages', 'description' => 'Access messaging/SMS features'],
            ['name' => 'Accounts', 'slug' => 'accounts', 'description' => 'Manage financial accounts'],
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Full administrative access'],
        ];

        // Get all tenants
        $tenants = DB::table('tenants')->pluck('id');

        foreach ($tenants as $tenantId) {
            foreach ($permissions as $permission) {
                // Check if permission already exists for this tenant
                $exists = DB::table('permissions')
                    ->where('tenant_id', $tenantId)
                    ->where('slug', $permission['slug'])
                    ->exists();

                if (!$exists) {
                    DB::table('permissions')->insert([
                        'tenant_id' => $tenantId,
                        'name' => $permission['name'],
                        'slug' => $permission['slug'],
                        'description' => $permission['description'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't delete permissions on rollback
    }
};
