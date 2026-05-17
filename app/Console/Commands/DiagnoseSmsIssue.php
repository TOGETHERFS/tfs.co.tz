<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Tenant;
use App\Models\SmsWallet;

class DiagnoseSmsIssue extends Command
{
    protected $signature = 'sms:diagnose';
    protected $description = 'Diagnose SMS balance issue for superadmin';

    public function handle()
    {
        $this->info('=== SMS Balance Diagnosis ===');
        
        // Check superadmin user
        $user = User::where('email', 'phidtechnology@gmail.com')->first();
        
        if (!$user) {
            $this->error('Superadmin user not found!');
            return 1;
        }
        
        $this->info("\n1. Superadmin User:");
        $this->info("   ID: {$user->id}");
        $this->info("   Name: {$user->name}");
        $this->info("   Email: {$user->email}");
        $this->info("   Tenant ID: " . ($user->tenant_id ?? 'NULL'));
        
        if ($user->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);
            if ($tenant) {
                $this->info("   Tenant Name: {$tenant->name}");
                $this->info("   Tenant Slug: {$tenant->slug}");
            }
        }
        
        // Check PHIDTECH T LIMITED tenant
        $this->info("\n2. PHIDTECH T LIMITED Tenant:");
        $phidtechTenant = Tenant::where('name', 'PHIDTECH T LIMITED')->first();
        
        if ($phidtechTenant) {
            $this->info("   ✓ Tenant exists");
            $this->info("   ID: {$phidtechTenant->id}");
            $this->info("   Slug: {$phidtechTenant->slug}");
            
            $wallet = SmsWallet::where('tenant_id', $phidtechTenant->id)->first();
            if ($wallet) {
                $this->info("   ✓ SMS Wallet exists");
                $this->info("   Balance: {$wallet->balance} SMS");
                $this->info("   Total Purchased: {$wallet->total_purchased}");
                $this->info("   Total Used: {$wallet->total_used}");
            } else {
                $this->error("   ✗ SMS Wallet NOT found");
            }
        } else {
            $this->error("   ✗ Tenant NOT found");
        }
        
        // Check all SMS wallets
        $this->info("\n3. All SMS Wallets:");
        $wallets = SmsWallet::with('tenant')->orderBy('id', 'desc')->limit(5)->get();
        
        foreach ($wallets as $wallet) {
            $this->info("   Wallet ID: {$wallet->id} | Tenant: {$wallet->tenant->name} | Balance: {$wallet->balance}");
        }
        
        // Diagnosis
        $this->info("\n=== Diagnosis ===");
        
        if (!$user->tenant_id) {
            $this->error("PROBLEM: Superadmin user has no tenant_id set!");
            $this->info("SOLUTION: Run 'php artisan user:update-superadmin-tenant'");
        } elseif ($phidtechTenant && $user->tenant_id != $phidtechTenant->id) {
            $this->error("PROBLEM: Superadmin is linked to wrong tenant!");
            $this->info("Current tenant_id: {$user->tenant_id}");
            $this->info("Should be: {$phidtechTenant->id} (PHIDTECH T LIMITED)");
            $this->info("SOLUTION: Run 'php artisan user:update-superadmin-tenant'");
        } elseif (!$phidtechTenant) {
            $this->error("PROBLEM: PHIDTECH T LIMITED tenant doesn't exist!");
            $this->info("SOLUTION: Run 'php artisan tenant:add-phidtech'");
        } else {
            $this->info("✓ User is correctly linked to PHIDTECH T LIMITED");
            
            $wallet = SmsWallet::where('tenant_id', $phidtechTenant->id)->first();
            if (!$wallet) {
                $this->error("PROBLEM: SMS Wallet doesn't exist for PHIDTECH T LIMITED!");
            } elseif ($wallet->balance == 0) {
                $this->error("PROBLEM: SMS Wallet balance is 0!");
                $this->info("Add credits via SMS Credits page in admin panel");
            } else {
                $this->info("✓ Everything looks good! Balance: {$wallet->balance} SMS");
            }
        }
        
        return 0;
    }
}
