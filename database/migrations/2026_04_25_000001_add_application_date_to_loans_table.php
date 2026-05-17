<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->date('application_date')->nullable()->after('loan_number');
        });

        // Backfill existing loans: use DATE(created_at) as the application_date
        DB::statement('UPDATE loans SET application_date = DATE(created_at) WHERE application_date IS NULL');
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('application_date');
        });
    }
};
