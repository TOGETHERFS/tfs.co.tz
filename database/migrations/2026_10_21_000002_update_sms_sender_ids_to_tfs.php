<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Rename PHIDTECH/PHIDLMS sender IDs to TFS
        DB::table('sms_sender_ids')
            ->whereIn('sender_id', ['PHIDTECH', 'PHIDLMS'])
            ->update([
                'sender_id'  => 'TFS',
                'is_default' => true,
            ]);

        // Also update default_sender_id in sms_provider_settings
        DB::table('sms_provider_settings')
            ->whereIn('default_sender_id', ['PHIDTECH', 'PHIDLMS'])
            ->update(['default_sender_id' => 'TFS']);
    }

    public function down(): void
    {
        DB::table('sms_sender_ids')
            ->where('sender_id', 'TFS')
            ->update(['sender_id' => 'PHIDTECH']);
    }
};
