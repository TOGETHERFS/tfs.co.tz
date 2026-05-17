<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class TestLoginRedirects extends Command
{
    protected $signature = 'admin:test-login-redirects';
    protected $description = 'Test login redirect logic for different user types';

    public function handle()
    {
        $this->info('Testing Login Redirect Logic');
        $this->info('================================');
        
        // Test 1: Superadmin
        $this->info("\n1. Testing Superadmin Users:");
        $superadmins = User::where(function($q) {
            $q->where('role', 'super_admin')
              ->orWhere('role', 'superadmin')
              ->orWhere('position', 'superadmin');
        })->get();
        
        foreach ($superadmins as $user) {
            $this->line("   ✓ {$user->name} ({$user->email})");
            $this->line("     - Role: {$user->role}");
            $this->line("     - Position: {$user->position}");
            $this->line("     - Tenant ID: " . ($user->tenant_id ?? 'NULL'));
            $this->line("     - Admin Role ID: " . ($user->admin_role_id ?? 'NULL'));
            $this->line("     → Should redirect to: ADMIN DASHBOARD");
        }
        
        // Test 2: Admin Staff
        $this->info("\n2. Testing Admin Staff Members:");
        $adminStaff = User::whereNotNull('admin_role_id')->get();
        
        if ($adminStaff->isEmpty()) {
            $this->warn("   No admin staff members found");
        } else {
            foreach ($adminStaff as $user) {
                $this->line("   ✓ {$user->name} ({$user->email})");
                $this->line("     - Role: {$user->role}");
                $this->line("     - Tenant ID: " . ($user->tenant_id ?? 'NULL'));
                $this->line("     - Admin Role ID: {$user->admin_role_id}");
                $this->line("     - Admin Role: " . ($user->adminRole->name ?? 'N/A'));
                
                if ($user->tenant_id !== null) {
                    $this->error("     ⚠ WARNING: Admin staff should NOT have tenant_id set!");
                    $this->line("     → Run: php artisan admin:cleanup-staff-tenants");
                }
                
                $this->line("     → Should redirect to: ADMIN DASHBOARD");
            }
        }
        
        // Test 3: Tenant Staff
        $this->info("\n3. Testing Tenant Staff Members:");
        $tenantStaff = User::whereNotNull('tenant_id')
            ->whereNull('admin_role_id')
            ->where('role', '!=', 'super_admin')
            ->where('role', '!=', 'superadmin')
            ->limit(10)
            ->get();
        
        if ($tenantStaff->isEmpty()) {
            $this->warn("   No tenant staff members found");
        } else {
            foreach ($tenantStaff as $user) {
                $this->line("   ✓ {$user->name} ({$user->email})");
                $this->line("     - Role: {$user->role}");
                $this->line("     - Tenant ID: {$user->tenant_id}");
                $this->line("     - Tenant: " . ($user->tenant->name ?? 'N/A'));
                $this->line("     - Admin Role ID: " . ($user->admin_role_id ?? 'NULL'));
                $this->line("     → Should redirect to: USER DASHBOARD (tenant-specific)");
            }
        }
        
        // Test 4: Users with issues
        $this->info("\n4. Checking for Potential Issues:");
        
        // Users with both admin_role_id and tenant_id
        $conflictUsers = User::whereNotNull('admin_role_id')
            ->whereNotNull('tenant_id')
            ->get();
        
        if ($conflictUsers->isNotEmpty()) {
            $this->error("   ⚠ Found {$conflictUsers->count()} user(s) with BOTH admin_role_id AND tenant_id:");
            foreach ($conflictUsers as $user) {
                $this->error("     - {$user->name} ({$user->email})");
                $this->line("       Run: php artisan admin:cleanup-staff-tenants");
            }
        } else {
            $this->info("   ✓ No conflicts found");
        }
        
        // Users with neither admin_role_id nor tenant_id
        $orphanUsers = User::whereNull('admin_role_id')
            ->whereNull('tenant_id')
            ->where('role', '!=', 'super_admin')
            ->where('role', '!=', 'superadmin')
            ->where('position', '!=', 'superadmin')
            ->get();
        
        if ($orphanUsers->isNotEmpty()) {
            $this->warn("   ⚠ Found {$orphanUsers->count()} user(s) with NO admin_role_id AND NO tenant_id:");
            foreach ($orphanUsers as $user) {
                $this->warn("     - {$user->name} ({$user->email})");
                $this->line("       These users may have login issues");
            }
        } else {
            $this->info("   ✓ No orphan users found");
        }
        
        $this->info("\n================================");
        $this->info("Test Complete!");
        
        return 0;
    }
}
