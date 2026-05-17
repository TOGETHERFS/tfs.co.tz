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
        Schema::create('sms_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 'beem_africa', 'route_africa'
            $table->string('display_name'); // 'Beem Africa', 'Route Africa'
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false); // Primary provider for routing
            $table->json('config'); // Store API keys, endpoints, etc.
            $table->decimal('balance', 15, 2)->default(0); // Provider balance
            $table->decimal('cost_per_sms', 8, 4)->default(0); // Cost per SMS
            $table->integer('priority')->default(1); // Routing priority (1 = highest)
            $table->timestamps();
            
            $table->unique('name');
            $table->index(['is_active', 'priority']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_providers');
    }
};
