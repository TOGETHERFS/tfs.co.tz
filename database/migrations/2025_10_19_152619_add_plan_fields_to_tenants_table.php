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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('plan_slug')->default('starter')->after('messaging_enabled');
            $table->timestamp('plan_renews_at')->nullable()->after('plan_slug');
            
            $table->index('plan_slug');
            $table->index('plan_renews_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['plan_slug']);
            $table->dropIndex(['plan_renews_at']);
            $table->dropColumn(['plan_slug', 'plan_renews_at']);
        });
    }
};
