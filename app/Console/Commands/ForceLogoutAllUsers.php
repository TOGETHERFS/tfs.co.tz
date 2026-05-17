<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ForceLogoutAllUsers extends Command
{
    protected $signature = 'admin:force-logout-all';
    protected $description = 'Force logout all users by clearing sessions table';

    public function handle()
    {
        $this->info('Forcing logout for all users...');
        
        try {
            // Clear all sessions
            $count = DB::table('sessions')->count();
            DB::table('sessions')->truncate();
            
            $this->info("✓ Cleared {$count} active session(s)");
            $this->info("✓ All users will need to log in again");
            $this->info("✓ This ensures fresh redirect logic is applied");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to clear sessions: " . $e->getMessage());
            return 1;
        }
    }
}
