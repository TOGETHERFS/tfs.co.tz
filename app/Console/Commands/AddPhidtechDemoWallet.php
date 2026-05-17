<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\SmsWallet;

class AddPhidtechDemoWallet extends Command
{
    protected $signature = 'sms:add-phidtech-wallet';
    protected $description = 'Add SMS wallet for phidtech-demo tenant';

    public function handle()
    {
        // Find phidtech-demo tenant
        $tenant = Tenant::where('slug', 'phidtech-demo')->first();
        
        if (!$tenant) {
            $this->error('phidtech-demo tenant not found!');
            return 1;
        }

        $this->info("Found tenant: {$tenant->name} (ID: {$tenant->id})");

        // Check if wallet already exists
        $wallet = SmsWallet::where('tenant_id', $tenant->id)->first();

        if ($wallet) {
            $this->info("SMS wallet already exists for this tenant.");
            $this->info("Current balance: {$wallet->balance} SMS");
            return 0;
        }

        // Create SMS wallet
        $wallet = SmsWallet::create([
            'tenant_id' => $tenant->id,
            'balance' => 0,
            'total_purchased' => 0,
            'total_used' => 0,
            'total_failed' => 0,
        ]);

        $this->info('✓ SMS wallet created successfully!');
        $this->info("Wallet ID: {$wallet->id}");
        $this->info("Initial balance: {$wallet->balance} SMS");

        return 0;
    }
}
