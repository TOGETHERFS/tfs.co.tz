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
        Schema::create('sms_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 15, 2)->default(0); // SMS credits balance
            $table->json('ledger')->nullable(); // Transaction history
            $table->decimal('low_balance_threshold', 15, 2)->default(100); // Alert threshold
            $table->boolean('auto_topup_enabled')->default(false);
            $table->decimal('auto_topup_amount', 15, 2)->nullable();
            $table->decimal('auto_topup_threshold', 15, 2)->nullable();
            $table->timestamps();
            
            $table->unique('tenant_id');
            $table->index('balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_wallets');
    }
};
