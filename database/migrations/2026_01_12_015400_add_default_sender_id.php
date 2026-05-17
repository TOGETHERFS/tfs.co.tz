<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get phidtech-demo tenant ID
        $tenant = DB::table('tenants')->where('slug', 'phidtech-demo')->first();
        
        if ($tenant) {
            // Add default PHIDTECH sender ID if it doesn't exist
            $exists = DB::table('sms_sender_ids')
                ->where('sender_id', 'PHIDTECH')
                ->where('tenant_id', $tenant->id)
                ->exists();
            
            if (!$exists) {
                DB::table('sms_sender_ids')->insert([
                    'tenant_id' => $tenant->id,
                    'sender_id' => 'PHIDTECH',
                    'provider_id' => 'PHIDTECH',
                    'is_default' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('sms_sender_ids')->where('sender_id', 'PHIDTECH')->delete();
    }
};
