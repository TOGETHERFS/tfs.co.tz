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
        // Change role column from ENUM to VARCHAR to support dynamic roles
        // Using ALTER TABLE CHANGE for MariaDB/MySQL compatibility (no doctrine/dbal needed)
        try {
            DB::statement("ALTER TABLE users CHANGE role role VARCHAR(100) NOT NULL DEFAULT 'officer'");
        } catch (\Exception $e) {
            // Column may already be VARCHAR, skip
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE users CHANGE role role VARCHAR(100) NOT NULL DEFAULT 'officer'");
        } catch (\Exception $e) {
            // Skip
        }
    }
};
