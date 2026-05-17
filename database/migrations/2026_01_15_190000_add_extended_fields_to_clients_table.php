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
        Schema::table('clients', function (Blueprint $table) {
            // Employment information
            if (!Schema::hasColumn('clients', 'marital_status')) {
                $table->string('marital_status')->nullable()->after('id_number');
            }
            if (!Schema::hasColumn('clients', 'occupation')) {
                $table->string('occupation')->nullable()->after('marital_status');
            }
            if (!Schema::hasColumn('clients', 'monthly_income')) {
                $table->decimal('monthly_income', 15, 2)->nullable()->after('occupation');
            }
            if (!Schema::hasColumn('clients', 'employer')) {
                $table->string('employer')->nullable()->after('monthly_income');
            }
            if (!Schema::hasColumn('clients', 'employment_type')) {
                $table->string('employment_type')->nullable()->after('employer');
            }
            
            // Emergency contact
            if (!Schema::hasColumn('clients', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable()->after('photo_path');
            }
            if (!Schema::hasColumn('clients', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone', 20)->nullable()->after('emergency_contact_name');
            }
            if (!Schema::hasColumn('clients', 'emergency_contact_relationship')) {
                $table->string('emergency_contact_relationship')->nullable()->after('emergency_contact_phone');
            }
            
            // Branch and officer relationships
            if (!Schema::hasColumn('clients', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('loan_officer')->constrained('branches')->nullOnDelete();
            }
            if (!Schema::hasColumn('clients', 'loan_officer_id')) {
                $table->foreignId('loan_officer_id')->nullable()->after('branch_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['loan_officer_id']);
            $table->dropColumn([
                'marital_status',
                'occupation',
                'monthly_income',
                'employer',
                'employment_type',
                'emergency_contact_name',
                'emergency_contact_phone',
                'emergency_contact_relationship',
                'branch_id',
                'loan_officer_id',
            ]);
        });
    }
};
