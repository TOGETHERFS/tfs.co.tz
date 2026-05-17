<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SMS Packages - defined by Super Admin for reselling
        Schema::create('sms_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('sms_count');
            $table->decimal('price', 15, 2);
            $table->string('currency', 10)->default('TZS');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Tenant SMS Balances
        Schema::create('sms_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->integer('balance')->default(0);
            $table->integer('total_purchased')->default(0);
            $table->integer('total_used')->default(0);
            $table->integer('total_failed')->default(0);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique('tenant_id');
        });

        // Sender ID Requests
        Schema::create('sms_sender_id_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('requested_by');
            $table->string('sender_id', 11);
            $table->string('company_name');
            $table->text('purpose')->nullable();
            $table->text('sample_message')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'submitted_to_provider'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('provider_status')->nullable();
            $table->string('provider_sender_id')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->index(['tenant_id', 'status']);
        });

        // Approved Sender IDs (mapped from Beem Africa)
        Schema::create('sms_sender_ids', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('sender_id', 11);
            $table->string('provider_id')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'sender_id']);
        });

        // SMS Transactions (purchases, credits, debits)
        Schema::create('sms_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('type', ['purchase', 'manual_credit', 'manual_debit', 'usage', 'refund']);
            $table->integer('amount');
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->unsignedBigInteger('package_id')->nullable();
            $table->decimal('payment_amount', 15, 2)->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('payment_method')->nullable();
            $table->enum('payment_status', ['pending', 'completed', 'failed'])->default('pending');
            $table->text('description')->nullable();
            $table->text('admin_reason')->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('package_id')->references('id')->on('sms_packages')->onDelete('set null');
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['tenant_id', 'type']);
            $table->index('payment_reference');
        });

        // SMS Messages Log
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->string('sender_id', 11);
            $table->string('recipient');
            $table->text('message');
            $table->integer('sms_count')->default(1);
            $table->enum('status', ['queued', 'sent', 'delivered', 'failed', 'rejected'])->default('queued');
            $table->string('provider_message_id')->nullable();
            $table->string('provider_status')->nullable();
            $table->text('provider_response')->nullable();
            $table->text('failure_reason')->nullable();
            $table->enum('message_type', ['single', 'bulk', 'notification', 'reminder', 'marketing'])->default('single');
            $table->string('batch_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('loan_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('set null');
            $table->foreign('loan_id')->references('id')->on('loans')->onDelete('set null');
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
            $table->index('batch_id');
            $table->index('provider_message_id');
        });

        // SMS Templates
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('name');
            $table->string('category');
            $table->text('content');
            $table->json('variables')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'category']);
        });

        // Beem Africa Settings (Super Admin only)
        Schema::create('sms_provider_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('beem_africa');
            $table->text('api_key')->nullable();
            $table->text('secret_key')->nullable();
            $table->string('default_sender_id')->nullable();
            $table->decimal('cost_per_sms', 10, 4)->default(0);
            $table->decimal('selling_price_per_sms', 10, 4)->default(0);
            $table->integer('provider_balance')->default(0);
            $table->timestamp('balance_synced_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        // SMS Purchase Requests (for Selcom integration)
        Schema::create('sms_purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('package_id');
            $table->integer('sms_count');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('TZS');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('selcom_order_id')->nullable();
            $table->string('selcom_transaction_id')->nullable();
            $table->json('payment_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('package_id')->references('id')->on('sms_packages')->onDelete('cascade');
            $table->index(['tenant_id', 'status']);
            $table->index('selcom_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_purchase_requests');
        Schema::dropIfExists('sms_provider_settings');
        Schema::dropIfExists('sms_templates');
        Schema::dropIfExists('sms_messages');
        Schema::dropIfExists('sms_transactions');
        Schema::dropIfExists('sms_sender_ids');
        Schema::dropIfExists('sms_sender_id_requests');
        Schema::dropIfExists('sms_balances');
        Schema::dropIfExists('sms_packages');
    }
};
