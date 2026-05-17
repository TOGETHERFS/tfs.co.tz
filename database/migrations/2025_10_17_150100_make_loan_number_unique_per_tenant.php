<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Drop global unique index on loan_number and enforce per-tenant uniqueness
        Schema::table('loans', function (Blueprint $table) {
            // Attempt to drop the existing unique index. Different DBs name indexes differently.
            try {
                $table->dropUnique('loans_loan_number_unique');
            } catch (\Throwable $e) {
                // Fallback: try dropping by column definition
                try {
                    $table->dropUnique(['loan_number']);
                } catch (\Throwable $e2) {
                    // As a last resort, try raw SQL (MySQL/MariaDB)
                    try {
                        DB::statement('ALTER TABLE `loans` DROP INDEX `loans_loan_number_unique`');
                    } catch (\Throwable $e3) {
                        // If drop fails, continue; adding composite index will still succeed if names differ
                    }
                }
            }
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->unique(['tenant_id', 'loan_number'], 'loans_tenant_loan_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            // Remove composite unique constraint
            try {
                $table->dropUnique('loans_tenant_loan_number_unique');
            } catch (\Throwable $e) {
                try {
                    $table->dropUnique(['tenant_id', 'loan_number']);
                } catch (\Throwable $e2) {
                    try {
                        DB::statement('ALTER TABLE `loans` DROP INDEX `loans_tenant_loan_number_unique`');
                    } catch (\Throwable $e3) {
                        // Ignore failures
                    }
                }
            }
        });

        // Restore global uniqueness on loan_number
        Schema::table('loans', function (Blueprint $table) {
            $table->unique('loan_number');
        });
    }
};