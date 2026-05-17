<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\SenderId;

class AddPhidtechSenderId extends Command
{
    protected $signature = 'sms:add-phidtech-sender';
    protected $description = 'Add PHIDTECH sender ID for phidtech-demo tenant';

    public function handle()
    {
        // Find phidtech-demo tenant
        $tenant = Tenant::where('slug', 'phidtech-demo')->first();
        
        if (!$tenant) {
            $this->error('phidtech-demo tenant not found!');
            return 1;
        }

        $this->info("Found tenant: {$tenant->name} (ID: {$tenant->id})");

        // Check if sender ID already exists
        $exists = SenderId::where('tenant_id', $tenant->id)
            ->where('sender_id', 'PHIDTECH')
            ->exists();

        if ($exists) {
            $this->info('PHIDTECH sender ID already exists for this tenant.');
            return 0;
        }

        // Create sender ID
        SenderId::create([
            'tenant_id' => $tenant->id,
            'sender_id' => 'PHIDTECH',
            'business_name' => 'PHIDTECH T LIMITED',
            'business_description' => 'Technology and Financial Services',
            'use_case' => 'System notifications and SMS services',
            'status' => 'approved',
            'is_active' => true,
            'approved_at' => now(),
        ]);

        $this->info('✓ PHIDTECH sender ID added successfully!');
        
        // Verify
        $senderId = SenderId::where('tenant_id', $tenant->id)
            ->where('sender_id', 'PHIDTECH')
            ->first();
            
        $this->info("Verified: Sender ID {$senderId->sender_id} (ID: {$senderId->id}) for tenant {$tenant->name}");

        return 0;
    }
}
