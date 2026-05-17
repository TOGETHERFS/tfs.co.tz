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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants');
            $table->foreignId('client_id')->constrained('clients');
            $table->foreignId('product_id')->constrained('loan_products');
            $table->foreignId('user_id')->constrained('users'); // loan officer
            $table->string('loan_number', 50)->unique();
            $table->decimal('principal', 12, 2);
            $table->decimal('interest_rate', 5, 2);
            $table->integer('term'); // months
            $table->decimal('monthly_payment', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('processing_fee', 12, 2)->default(0);
            $table->date('disbursed_at')->nullable();
            $table->date('first_payment_date')->nullable();
            $table->enum('status', ['pending', 'approved', 'disbursed', 'active', 'completed', 'defaulted', 'written_off'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'client_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};