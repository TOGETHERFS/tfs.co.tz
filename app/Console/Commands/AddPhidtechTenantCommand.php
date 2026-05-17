<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\SmsWallet;
use App\Models\SenderId;
use Illuminate\Support\Str;

class AddPhidtechTenantCommand extends Command
{
    protected $signature = 'tenant:add-phidtech';
    protected $description = 'Add PHIDTECH T LIMITED tenant for superadmin SMS';

    public function handle()
    {
        // Check if tenant already exists
        $exists = Tenant::where('name', 'PHIDTECH T LIMITED')->first();
        
        if ($exists) {
            $this->info('PHIDTECH T LIMITED tenant already exists.');
            $this->info("Tenant ID: {$exists->id}");
            $this->info("Slug: {$exists->slug}");
            return 0;
        }

        // Create tenant
        $tenant = Tenant::create([
            'name' => 'PHIDTECH T LIMITED',
            'slug' => 'phidtech-t-limited',
            'contact_email' => 'phidtechnology@gmail.com',
            'phone' => '255682188544',
            'status' => 'active',
            'messaging_enabled' => true,
            'sms_credits' => 0,
        ]);

        $this->info('✓ PHIDTECH T LIMITED tenant created successfully!');
        $this->info("Tenant ID: {$tenant->id}");
        $this->info("Slug: {$tenant->slug}");

        // Create SMS wallet
        $wallet = SmsWallet::create([
            'tenant_id' => $tenant->id,
            'balance' => 0,
            'total_purchased' => 0,
            'total_used' => 0,
            'total_failed' => 0,
        ]);

        $this->info('✓ SMS wallet created!');
        $this->info("Wallet ID: {$wallet->id}");

        // Add PHIDTECH sender ID for this tenant
        $senderId = SenderId::create([
            'tenant_id' => $tenant->id,
            'sender_id' => 'PHIDTECH',
            'business_name' => 'PHIDTECH T LIMITED',
            'business_description' => 'Technology and Financial Services',
            'use_case' => 'System notifications and SMS services',
            'status' => 'approved',
            'is_active' => true,
            'approved_at' => now(),
        ]);

        $this->info('✓ PHIDTECH sender ID added!');
        $this->info("Sender ID: {$senderId->id}");

        $this->info("\n=== Summary ===");
        $this->info("Tenant: PHIDTECH T LIMITED");
        $this->info("Tenant ID: {$tenant->id}");
        $this->info("SMS Wallet: Created with 0 balance");
        $this->info("Sender ID: PHIDTECH (approved)");
        $this->info("\nYou can now add SMS credits to this tenant from the admin panel.");

        return 0;
    }
}
