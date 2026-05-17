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
            $table->string('branch_name')->nullable()->after('status');
            $table->string('loan_officer')->nullable()->after('branch_name');
            $table->string('photo_path')->nullable()->after('loan_officer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['branch_name', 'loan_officer', 'photo_path']);
        });
    }
};
