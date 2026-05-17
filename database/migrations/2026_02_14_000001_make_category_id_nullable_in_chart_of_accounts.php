<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the foreign key constraint first (ignore if doesn't exist)
        try {
            Schema::table('chart_of_accounts', function (Blueprint $table) {
                $table->dropForeign(['category_id']);
            });
        } catch (\Exception $e) {}

        // Use CHANGE instead of MODIFY for MariaDB compatibility
        DB::statement('ALTER TABLE chart_of_accounts CHANGE category_id category_id BIGINT UNSIGNED NULL');

        // Recreate the foreign key constraint
        try {
            Schema::table('chart_of_accounts', function (Blueprint $table) {
                $table->foreign('category_id')
                      ->references('id')
                      ->on('account_categories')
                      ->onDelete('cascade');
            });
        } catch (\Exception $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            Schema::table('chart_of_accounts', function (Blueprint $table) {
                $table->dropForeign(['category_id']);
            });
        } catch (\Exception $e) {}

        DB::statement('ALTER TABLE chart_of_accounts CHANGE category_id category_id BIGINT UNSIGNED NOT NULL');

        try {
            Schema::table('chart_of_accounts', function (Blueprint $table) {
                $table->foreign('category_id')
                      ->references('id')
                      ->on('account_categories')
                      ->onDelete('cascade');
            });
        } catch (\Exception $e) {}
    }
};
