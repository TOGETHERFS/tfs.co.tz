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
        Schema::table('groups', function (Blueprint $table) {
            $table->string('branch_name', 150)->nullable()->after('name');
            $table->string('loan_officer', 150)->nullable()->after('branch_name');
            $table->string('meeting_area', 150)->nullable()->after('loan_officer');
            $table->string('bank_account', 120)->nullable()->after('meeting_area');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['branch_name', 'loan_officer', 'meeting_area', 'bank_account']);
        });
    }
};