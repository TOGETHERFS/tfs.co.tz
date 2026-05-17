<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Tenant;
use App\Models\SmsWallet;
use Illuminate\Support\Facades\DB;

class VerifySmsSetup extends Command
{
    protected $signature = 'sms:verify-setup';
    protected $description = 'Verify SMS setup is correct for superadmin';

    public function handle()
    {
        $this->info('=== Verifying SMS Setup ===');
        
        // Get superadmin user with fresh data
        $user = User::where('email', 'phidtechnology@gmail.com')->first();
        
        if (!$user) {
            $this->error('Superadmin user not found!');
            return 1;
        }
        
        $this->info("User ID: {$user->id}");
        $this->info("User Name: {$user->name}");
        $this->info("User Tenant ID: {$user->tenant_id}");
        
        if (!$user->tenant_id) {
            $this->error('User has no tenant_id!');
            return 1;
        }
        
        // Get tenant
        $tenant = Tenant::find($user->tenant_id);
        
        if (!$tenant) {
            $this->error("Tenant with ID {$user->tenant_id} not found!");
            return 1;
        }
        
        $this->info("Tenant Name: {$tenant->name}");
        $this->info("Tenant Slug: {$tenant->slug}");
        
        // Get SMS wallet
        $wallet = SmsWallet::where('tenant_id', $user->tenant_id)->first();
        
        if (!$wallet) {
            $this->error("SMS Wallet not found for tenant {$tenant->name}!");
            return 1;
        }
        
        $this->info("SMS Wallet ID: {$wallet->id}");
        $this->info("SMS Balance: {$wallet->balance}");
        $this->info("Total Purchased: {$wallet->total_purchased}");
        $this->info("Total Used: {$wallet->total_used}");
        
        // Verify the query that the controller uses
        $this->info("\n=== Testing Controller Query ===");
        $testWallet = \App\Models\SmsWallet::where('tenant_id', $user->tenant_id)->first();
        $testBalance = $testWallet ? $testWallet->balance : 0;
        $this->info("Balance from controller query: {$testBalance}");
        
        if ($testBalance > 0) {
            $this->info("\n✓ Everything is set up correctly!");
            $this->info("✓ User is linked to: {$tenant->name}");
            $this->info("✓ SMS Balance: {$testBalance} SMS");
            $this->info("\nIf the Send SMS page still shows 0, try:");
            $this->info("1. Log out and log back in");
            $this->info("2. Clear browser cache (Ctrl+Shift+Delete)");
            $this->info("3. Run: php artisan cache:clear");
            $this->info("4. Run: php artisan config:clear");
        } else {
            $this->error("\n✗ Balance is 0. Add credits via admin panel.");
        }
        
        return 0;
    }
}
