<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class CleanupAdminStaffTenants extends Command
{
    protected $signature = 'admin:cleanup-staff-tenants';
    protected $description = 'Remove tenant_id from admin staff members to prevent them from accessing tenant dashboards';

    public function handle()
    {
        $this->info('Cleaning up admin staff tenant associations...');
        
        // Find all users with admin_role_id set
        $adminStaff = User::whereNotNull('admin_role_id')->get();
        
        if ($adminStaff->isEmpty()) {
            $this->info('No admin staff members found.');
            return 0;
        }
        
        $updated = 0;
        foreach ($adminStaff as $staff) {
            if ($staff->tenant_id !== null) {
                $this->info("Removing tenant_id from {$staff->name} ({$staff->email})");
                $staff->tenant_id = null;
                $staff->save();
                $updated++;
            }
        }
        
        if ($updated > 0) {
            $this->info("✓ Successfully removed tenant_id from {$updated} admin staff member(s)");
            $this->info("✓ Admin staff will now be redirected to admin dashboard on login");
        } else {
            $this->info('✓ All admin staff members are already properly configured');
        }
        
        return 0;
    }
}
