<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class RemoveSuperadminTenant extends Command
{
    protected $signature = 'user:remove-superadmin-tenant';
    protected $description = 'Remove tenant_id from superadmin user to restore admin dashboard access';

    public function handle()
    {
        $this->info('Removing tenant_id from superadmin user...');
        
        $user = User::where('email', 'phidtechnology@gmail.com')->first();
        
        if (!$user) {
            $this->error('Superadmin user not found!');
            return 1;
        }
        
        $this->info("Current user details:");
        $this->info("  Name: {$user->name}");
        $this->info("  Email: {$user->email}");
        $this->info("  Role: {$user->role}");
        $this->info("  Position: {$user->position}");
        $this->info("  Current Tenant ID: " . ($user->tenant_id ?? 'NULL'));
        
        if (!$user->tenant_id) {
            $this->info('User already has no tenant_id. No changes needed.');
            return 0;
        }
        
        // Remove tenant_id
        $user->tenant_id = null;
        $user->save();
        
        $this->info("\n✓ Successfully removed tenant_id from superadmin user!");
        $this->info("✓ Superadmin can now access admin dashboard");
        $this->info("✓ SMS sending will use PHIDTECH T LIMITED tenant automatically");
        $this->info("\nNext steps:");
        $this->info("1. Clear cache: php artisan cache:clear");
        $this->info("2. Log out and log back in");
        
        return 0;
    }
}
