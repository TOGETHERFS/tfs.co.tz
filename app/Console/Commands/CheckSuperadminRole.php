<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CheckSuperadminRole extends Command
{
    protected $signature = 'user:check-superadmin-role';
    protected $description = 'Check superadmin user role and position';

    public function handle()
    {
        $user = User::where('email', 'phidtechnology@gmail.com')->first();
        
        if (!$user) {
            $this->error('Superadmin user not found!');
            return 1;
        }
        
        $this->info('=== Superadmin User Details ===');
        $this->info("ID: {$user->id}");
        $this->info("Name: {$user->name}");
        $this->info("Email: {$user->email}");
        $this->info("Role: " . ($user->role ?? 'NULL'));
        $this->info("Position: " . ($user->position ?? 'NULL'));
        $this->info("Tenant ID: " . ($user->tenant_id ?? 'NULL'));
        
        // Check what the login logic will evaluate
        $superAliases = ['super_admin', 'superadmin', 'super-admin', 'super admin', 'admin'];
        $isSuperAdmin = in_array(strtolower($user->role ?? ''), $superAliases) || 
                       strtolower($user->position ?? '') === 'superadmin';
        
        $this->info("\n=== Login Logic Check ===");
        $this->info("Role (lowercase): " . strtolower($user->role ?? ''));
        $this->info("Position (lowercase): " . strtolower($user->position ?? ''));
        $this->info("Is in superAliases: " . (in_array(strtolower($user->role ?? ''), $superAliases) ? 'YES' : 'NO'));
        $this->info("Position is superadmin: " . (strtolower($user->position ?? '') === 'superadmin' ? 'YES' : 'NO'));
        $this->info("Will redirect to admin dashboard: " . ($isSuperAdmin ? 'YES' : 'NO'));
        
        if (!$isSuperAdmin) {
            $this->error("\n✗ User will NOT be redirected to admin dashboard!");
            $this->info("\nTo fix, run one of these SQL commands:");
            $this->info("UPDATE users SET role = 'admin' WHERE email = 'phidtechnology@gmail.com';");
            $this->info("OR");
            $this->info("UPDATE users SET position = 'superadmin' WHERE email = 'phidtechnology@gmail.com';");
        } else {
            $this->info("\n✓ User will be redirected to admin dashboard correctly!");
        }
        
        return 0;
    }
}
