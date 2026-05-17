<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\AdminRole;

class AssignSuperadminRole extends Command
{
    protected $signature = 'admin:assign-superadmin-role';
    protected $description = 'Assign CEO role to superadmin user';

    public function handle()
    {
        $this->info('Assigning CEO role to superadmin...');
        
        $user = User::where('email', 'phidtechnology@gmail.com')->first();
        
        if (!$user) {
            $this->error('Superadmin user not found!');
            return 1;
        }
        
        $ceoRole = AdminRole::where('slug', 'ceo')->first();
        
        if (!$ceoRole) {
            $this->error('CEO role not found! Please run the seeder first: php artisan db:seed --class=AdminRolesAndPermissionsSeeder');
            return 1;
        }
        
        $user->admin_role_id = $ceoRole->id;
        $user->save();
        
        $this->info("✓ Successfully assigned CEO role to {$user->name} ({$user->email})");
        $this->info("✓ Superadmin will now appear in the Staff list");
        
        return 0;
    }
}
