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
        // Account Categories (Asset, Liability, Equity, Income, Expense)
        Schema::create('account_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name', 100);
            $table->string('code', 20);
            $table->enum('type', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            
            $table->unique(['tenant_id', 'code']);
        });

        // Chart of Accounts
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('account_categories')->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->onDelete('set null');
            $table->string('account_code', 20);
            $table->string('account_name', 150);
            $table->text('description')->nullable();
            $table->enum('account_type', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->boolean('allow_manual_entry')->default(true);
            $table->boolean('is_bank_account')->default(false);
            $table->boolean('is_cash_account')->default(false);
            $table->decimal('opening_balance', 20, 2)->default(0);
            $table->date('opening_balance_date')->nullable();
            $table->decimal('current_balance', 20, 2)->default(0);
            $table->string('currency', 3)->default('TZS');
            $table->integer('level')->default(1);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['tenant_id', 'account_code']);
            $table->index(['tenant_id', 'account_type']);
            $table->index(['tenant_id', 'is_active']);
        });

        // Fiscal Years
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_closed')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['tenant_id', 'name']);
        });

        // Accounting Periods (Monthly)
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->onDelete('cascade');
            $table->string('name', 50);
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('period_number');
            $table->boolean('is_closed')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['tenant_id', 'fiscal_year_id', 'period_number']);
        });

        // Journal Entry Headers
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->onDelete('cascade');
            $table->foreignId('period_id')->nullable()->constrained('accounting_periods')->onDelete('set null');
            $table->string('entry_number', 50);
            $table->date('entry_date');
            $table->enum('entry_type', [
                'manual', 'loan_disbursement', 'loan_repayment', 'savings_deposit', 
                'savings_withdrawal', 'fee_income', 'penalty_income', 'expense', 
                'asset_purchase', 'asset_depreciation', 'asset_disposal', 'adjustment',
                'opening_balance', 'closing_entry', 'reversal'
            ]);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description');
            $table->decimal('total_debit', 20, 2)->default(0);
            $table->decimal('total_credit', 20, 2)->default(0);
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'posted', 'rejected', 'reversed'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_auto_generated')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['tenant_id', 'entry_number']);
            $table->index(['tenant_id', 'entry_date']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'entry_type']);
            $table->index(['reference_type', 'reference_id']);
        });

        // Journal Entry Lines (Double-Entry)
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('chart_of_accounts')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->decimal('debit_amount', 20, 2)->default(0);
            $table->decimal('credit_amount', 20, 2)->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['tenant_id', 'account_id']);
            $table->index(['journal_entry_id']);
        });

        // General Ledger (Denormalized for performance)
        Schema::create('general_ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('chart_of_accounts')->onDelete('cascade');
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->onDelete('cascade');
            $table->foreignId('journal_line_id')->constrained('journal_entry_lines')->onDelete('cascade');
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->onDelete('cascade');
            $table->foreignId('period_id')->nullable()->constrained('accounting_periods')->onDelete('set null');
            $table->date('transaction_date');
            $table->string('entry_number', 50);
            $table->text('description');
            $table->decimal('debit_amount', 20, 2)->default(0);
            $table->decimal('credit_amount', 20, 2)->default(0);
            $table->decimal('running_balance', 20, 2)->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['tenant_id', 'account_id', 'transaction_date']);
            $table->index(['tenant_id', 'transaction_date']);
            $table->index(['tenant_id', 'fiscal_year_id']);
        });

        // Expense Categories
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('account_id')->nullable()->constrained('chart_of_accounts')->onDelete('set null');
            $table->string('name', 100);
            $table->string('code', 20)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['tenant_id', 'name']);
        });

        // Expenses
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('expense_categories')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('chart_of_accounts')->onDelete('cascade');
            $table->foreignId('payment_account_id')->constrained('chart_of_accounts')->onDelete('cascade');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->string('expense_number', 50);
            $table->date('expense_date');
            $table->string('payee', 150)->nullable();
            $table->text('description');
            $table->decimal('amount', 20, 2);
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->string('receipt_number', 100)->nullable();
            $table->string('attachment')->nullable();
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'paid', 'rejected'])->default('draft');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['tenant_id', 'expense_number']);
            $table->index(['tenant_id', 'expense_date']);
            $table->index(['tenant_id', 'category_id']);
        });

        // Fixed Asset Categories
        Schema::create('fixed_asset_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->unsignedBigInteger('asset_account_id')->nullable();
            $table->unsignedBigInteger('depreciation_account_id')->nullable();
            $table->unsignedBigInteger('accum_depr_account_id')->nullable();
            
            $table->foreign('asset_account_id', 'fac_asset_acct_fk')->references('id')->on('chart_of_accounts')->onDelete('set null');
            $table->foreign('depreciation_account_id', 'fac_depr_acct_fk')->references('id')->on('chart_of_accounts')->onDelete('set null');
            $table->foreign('accum_depr_account_id', 'fac_accum_depr_fk')->references('id')->on('chart_of_accounts')->onDelete('set null');
            $table->string('name', 100);
            $table->string('code', 20)->nullable();
            $table->text('description')->nullable();
            $table->enum('depreciation_method', ['straight_line', 'declining_balance', 'none'])->default('straight_line');
            $table->decimal('useful_life_years', 5, 2)->default(5);
            $table->decimal('salvage_value_percentage', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['tenant_id', 'name']);
        });

        // Fixed Assets Register
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('fixed_asset_categories')->onDelete('cascade');
            $table->string('asset_code', 50);
            $table->string('asset_name', 150);
            $table->text('description')->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('location', 150)->nullable();
            $table->date('purchase_date');
            $table->decimal('purchase_price', 20, 2);
            $table->decimal('salvage_value', 20, 2)->default(0);
            $table->decimal('useful_life_years', 5, 2);
            $table->enum('depreciation_method', ['straight_line', 'declining_balance', 'none'])->default('straight_line');
            $table->decimal('accumulated_depreciation', 20, 2)->default(0);
            $table->decimal('current_value', 20, 2)->default(0);
            $table->date('last_depreciation_date')->nullable();
            $table->enum('status', ['active', 'disposed', 'sold', 'written_off'])->default('active');
            $table->date('disposal_date')->nullable();
            $table->decimal('disposal_amount', 20, 2)->nullable();
            $table->text('disposal_notes')->nullable();
            $table->foreignId('purchase_journal_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->foreignId('disposal_journal_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['tenant_id', 'asset_code']);
            $table->index(['tenant_id', 'status']);
        });

        // Asset Depreciation Schedule
        Schema::create('asset_depreciation_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('asset_id')->constrained('fixed_assets')->onDelete('cascade');
            $table->foreignId('period_id')->nullable()->constrained('accounting_periods')->onDelete('set null');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->date('depreciation_date');
            $table->decimal('depreciation_amount', 20, 2);
            $table->decimal('accumulated_depreciation', 20, 2);
            $table->decimal('book_value', 20, 2);
            $table->boolean('is_posted')->default(false);
            $table->timestamps();
            
            $table->index(['tenant_id', 'asset_id', 'depreciation_date'], 'ads_tenant_asset_date_idx');
        });

        // Bank Accounts (Extension of Chart of Accounts for banking)
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('chart_of_accounts')->onDelete('cascade');
            $table->string('bank_name', 150);
            $table->string('account_number', 50);
            $table->string('account_holder', 150)->nullable();
            $table->string('branch_name', 150)->nullable();
            $table->string('swift_code', 20)->nullable();
            $table->string('currency', 3)->default('TZS');
            $table->decimal('current_balance', 20, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['tenant_id', 'account_number']);
        });

        // Accounting Audit Trail
        Schema::create('accounting_audit_trail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('action', 50);
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'model_type', 'model_id']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'user_id']);
        });

        // Budget (Optional future feature)
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('chart_of_accounts')->onDelete('cascade');
            $table->foreignId('period_id')->nullable()->constrained('accounting_periods')->onDelete('set null');
            $table->decimal('budgeted_amount', 20, 2)->default(0);
            $table->decimal('actual_amount', 20, 2)->default(0);
            $table->decimal('variance', 20, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['tenant_id', 'fiscal_year_id', 'account_id', 'period_id']);
        });

        // Client Savings Accounts (for microfinance savings tracking)
        Schema::create('savings_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->foreignId('liability_account_id')->constrained('chart_of_accounts')->onDelete('cascade');
            $table->string('account_number', 50);
            $table->string('account_type', 50)->default('regular');
            $table->decimal('current_balance', 20, 2)->default(0);
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->date('opened_date');
            $table->enum('status', ['active', 'dormant', 'closed'])->default('active');
            $table->date('closed_date')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['tenant_id', 'account_number']);
            $table->index(['tenant_id', 'client_id']);
        });

        // Savings Transactions
        Schema::create('savings_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('savings_account_id')->constrained('savings_accounts')->onDelete('cascade');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->onDelete('set null');
            $table->string('transaction_number', 50);
            $table->date('transaction_date');
            $table->enum('transaction_type', ['deposit', 'withdrawal', 'interest', 'fee', 'transfer']);
            $table->decimal('amount', 20, 2);
            $table->decimal('running_balance', 20, 2);
            $table->string('payment_method', 50)->nullable();
            $table->string('reference', 100)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('processed_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['tenant_id', 'transaction_number']);
            $table->index(['tenant_id', 'savings_account_id', 'transaction_date'], 'st_tenant_acct_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings_transactions');
        Schema::dropIfExists('savings_accounts');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('accounting_audit_trail');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('asset_depreciation_schedules');
        Schema::dropIfExists('fixed_assets');
        Schema::dropIfExists('fixed_asset_categories');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('general_ledger');
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounting_periods');
        Schema::dropIfExists('fiscal_years');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('account_categories');
    }
};
