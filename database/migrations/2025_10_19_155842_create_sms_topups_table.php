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
        Schema::create('sms_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2); // Amount in TZS
            $table->integer('units'); // SMS units purchased
            $table->enum('status', ['pending', 'paid', 'failed', 'reversed'])->default('pending');
            $table->string('internal_ref')->unique(); // UUID for internal tracking
            $table->string('selcom_ref')->nullable(); // Selcom transaction reference
            $table->string('currency', 3)->default('TZS');
            $table->json('selcom_payload')->nullable(); // Store Selcom response
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Payment expiry
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'status']);
            $table->index('internal_ref');
            $table->index('selcom_ref');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_topups');
    }
};
