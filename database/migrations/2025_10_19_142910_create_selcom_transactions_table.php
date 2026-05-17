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
        Schema::create('selcom_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->string('reference')->index();
            $table->string('till_number')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('TZS');
            $table->string('status')->default('pending'); // pending, completed, failed, cancelled
            $table->string('payment_method')->nullable(); // till, wallet, qr, mobile_money
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->text('description')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->json('callback_data')->nullable();
            $table->string('selcom_order_id')->nullable();
            $table->string('selcom_transaction_id')->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->timestamp('callback_received_at')->nullable();
            $table->string('failure_reason')->nullable();
            $table->integer('retry_count')->default(0);
            
            // Foreign key relationships
            $table->unsignedBigInteger('repayment_id')->nullable();
            $table->unsignedBigInteger('loan_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['status', 'created_at']);
            $table->index(['till_number', 'created_at']);
            $table->index(['customer_phone', 'created_at']);
            $table->index(['payment_date']);
            
            // Foreign key constraints
            $table->foreign('repayment_id')->references('id')->on('repayments')->onDelete('set null');
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('selcom_transactions');
    }
};
