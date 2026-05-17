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
        Schema::table('sms_wallets', function (Blueprint $table) {
            $table->boolean('email_notifications_enabled')->default(true)->after('auto_topup_threshold');
            $table->boolean('low_balance_notifications')->default(true)->after('email_notifications_enabled');
            $table->boolean('topup_notifications')->default(true)->after('low_balance_notifications');
            $table->boolean('sender_id_notifications')->default(true)->after('topup_notifications');
            $table->boolean('daily_usage_notifications')->default(false)->after('sender_id_notifications');
            $table->boolean('weekly_usage_notifications')->default(false)->after('daily_usage_notifications');
            $table->timestamp('last_low_balance_notification')->nullable()->after('weekly_usage_notifications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sms_wallets', function (Blueprint $table) {
            $table->dropColumn([
                'email_notifications_enabled',
                'low_balance_notifications',
                'topup_notifications',
                'sender_id_notifications',
                'daily_usage_notifications',
                'weekly_usage_notifications',
                'last_low_balance_notification'
            ]);
        });
    }
};
