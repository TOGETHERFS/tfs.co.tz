<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->enum('penalty_type', ['none', 'percentage', 'fixed'])->default('none')->after('processing_fee');
            $table->decimal('penalty_value', 10, 2)->default(0)->after('penalty_type');
            $table->decimal('penalty_frequency', 5, 2)->nullable()->after('penalty_value')
                ->comment('Penalty applies per: 1=daily, 7=weekly, 14=biweekly, 30=monthly (days overdue interval)');
        });

        Schema::table('loan_schedules', function (Blueprint $table) {
            $table->decimal('penalty_amount', 12, 2)->default(0)->after('paid_amount');
            $table->decimal('total_penalties_paid', 12, 2)->default(0)->after('penalty_amount');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['penalty_type', 'penalty_value', 'penalty_frequency']);
        });
        Schema::table('loan_schedules', function (Blueprint $table) {
            $table->dropColumn(['penalty_amount', 'total_penalties_paid']);
        });
    }
};
