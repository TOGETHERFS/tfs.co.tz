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
        Schema::create('repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->foreignId('loan_id')->constrained('loans');
            $table->foreignId('schedule_id')->nullable()->constrained('loan_schedules');
            $table->foreignId('user_id')->constrained('users'); // received by
            $table->string('receipt_number', 50)->unique();
            $table->decimal('amount', 12, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money', 'cheque'])->default('cash');
            $table->string('reference', 100)->nullable();
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'loan_id']);
            $table->index(['tenant_id', 'payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repayments');
    }
};