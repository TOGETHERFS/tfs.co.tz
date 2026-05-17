<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->string('provider')->nullable()->after('provider_request_id'); // 'beem_africa', 'route_africa'
            $table->decimal('cost', 8, 4)->default(0)->after('provider'); // Cost per SMS
            $table->foreignId('campaign_id')->nullable()->constrained('sms_campaigns')->onDelete('set null')->after('cost');
            $table->string('message_type')->default('single')->after('campaign_id'); // 'single', 'bulk', 'campaign'
            $table->integer('recipient_count')->default(1)->after('message_type');
            $table->json('delivery_reports')->nullable()->after('delivered_at'); // Store DLR data
            $table->timestamp('failed_at')->nullable()->after('delivery_reports');
            $table->integer('retry_count')->default(0)->after('failed_at');
            $table->timestamp('scheduled_at')->nullable()->after('retry_count');
            
            $table->index(['provider', 'status']);
            $table->index('scheduled_at');
            $table->index('message_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_logs', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropIndex(['provider', 'status']);
            $table->dropIndex(['scheduled_at']);
            $table->dropIndex(['message_type']);
            $table->dropColumn([
                'provider', 'cost', 'campaign_id', 'message_type', 
                'recipient_count', 'delivery_reports', 'failed_at', 
                'retry_count', 'scheduled_at'
            ]);
        });
    }
};
