<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Tenant;

class UpdateSuperadminTenant extends Command
{
    protected $signature = 'user:update-superadmin-tenant';
    protected $description = 'Update superadmin user to use PHIDTECH T LIMITED tenant';

    public function handle()
    {
        // Find PHIDTECH T LIMITED tenant
        $tenant = Tenant::where('name', 'PHIDTECH T LIMITED')->first();
        
        if (!$tenant) {
            $this->error('PHIDTECH T LIMITED tenant not found!');
            $this->info('Please run: php artisan tenant:add-phidtech first');
            return 1;
        }

        // Find superadmin user
        $user = User::where('email', 'phidtechnology@gmail.com')->first();
        
        if (!$user) {
            $this->error('Superadmin user (phidtechnology@gmail.com) not found!');
            return 1;
        }

        $this->info("Found user: {$user->name} ({$user->email})");
        $this->info("Current tenant_id: {$user->tenant_id}");
        $this->info("New tenant: {$tenant->name} (ID: {$tenant->id})");

        // Update user's tenant_id
        $user->tenant_id = $tenant->id;
        $user->save();

        $this->info('✓ Superadmin user updated successfully!');
        $this->info("User {$user->email} now linked to tenant: {$tenant->name}");
        $this->info("\nThe superadmin can now use the SMS balance from PHIDTECH T LIMITED.");

        return 0;
    }
}
