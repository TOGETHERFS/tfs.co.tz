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
        Schema::table('loans', function (Blueprint $table) {
            $table->string('approval_stage', 50)->default('cso_review')->after('status');
            $table->enum('approval_stage_status', ['pending', 'approved', 'rejected'])->default('pending')->after('approval_stage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['approval_stage', 'approval_stage_status']);
        });
    }
};