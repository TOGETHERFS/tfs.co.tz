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
            $table->string('interest_type', 20)->nullable()->after('interest_rate');
        });

        // Backfill existing loans from their product's interest_type
        DB::statement("
            UPDATE loans l
            JOIN loan_products lp ON l.product_id = lp.id
            SET l.interest_type = lp.interest_type
            WHERE l.interest_type IS NULL
        ");

        // Default remaining nulls to 'flat'
        DB::table('loans')->whereNull('interest_type')->update(['interest_type' => 'flat']);
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('interest_type');
        });
    }
};
