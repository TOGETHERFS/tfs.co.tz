<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CheckUserConfig extends Command
{
    protected $signature = 'admin:check-user {email}';
    protected $description = 'Check user configuration for debugging redirect issues';

    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User not found: {$email}");
            return 1;
        }
        
        $this->info("User Configuration for: {$user->name} ({$user->email})");
        $this->info("=================================================");
        $this->line("ID: {$user->id}");
        $this->line("Role: " . ($user->role ?? 'NULL'));
        $this->line("Position: " . ($user->position ?? 'NULL'));
        $this->line("Tenant ID: " . ($user->tenant_id ?? 'NULL'));
        $this->line("Admin Role ID: " . ($user->admin_role_id ?? 'NULL'));
        
        if ($user->admin_role_id) {
            $this->line("Admin Role: " . ($user->adminRole->name ?? 'N/A'));
        }
        
        if ($user->tenant_id) {
            $this->line("Tenant: " . ($user->tenant->name ?? 'N/A'));
        }
        
        $this->info("\nRedirect Logic Analysis:");
        $this->info("=================================================");
        
        // Check superadmin
        $superAliases = ['super_admin', 'superadmin', 'super-admin', 'super admin', 'admin'];
        $isSuperAdmin = in_array(strtolower($user->role ?? ''), $superAliases) || 
                       strtolower($user->position ?? '') === 'superadmin';
        
        if ($isSuperAdmin) {
            $this->info("✓ Is Superadmin: YES");
            $this->info("→ Should redirect to: ADMIN DASHBOARD");
        } else {
            $this->line("✓ Is Superadmin: NO");
        }
        
        // Check admin staff
        if ($user->admin_role_id) {
            $this->info("✓ Is Admin Staff: YES (admin_role_id = {$user->admin_role_id})");
            $this->info("→ Should redirect to: ADMIN DASHBOARD");
        } else {
            $this->line("✓ Is Admin Staff: NO");
        }
        
        // Check tenant staff
        if ($user->tenant_id && !$user->admin_role_id && !$isSuperAdmin) {
            $this->info("✓ Is Tenant Staff: YES (tenant_id = {$user->tenant_id})");
            $this->info("→ Should redirect to: USER DASHBOARD");
        } else {
            $this->line("✓ Is Tenant Staff: NO");
        }
        
        // Issues
        $this->info("\nPotential Issues:");
        $this->info("=================================================");
        
        if ($user->admin_role_id && $user->tenant_id) {
            $this->error("⚠ User has BOTH admin_role_id AND tenant_id!");
            $this->error("  This will cause redirect conflicts.");
            $this->error("  Run: php artisan admin:cleanup-staff-tenants");
        } else {
            $this->info("✓ No configuration conflicts detected");
        }
        
        return 0;
    }
}
