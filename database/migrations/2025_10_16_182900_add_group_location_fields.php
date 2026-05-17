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
            $table->string('region', 120)->nullable()->after('bank_account');
            $table->string('ward', 120)->nullable()->after('region');
            $table->string('village', 120)->nullable()->after('ward');
            $table->string('box_number', 120)->nullable()->after('village');
            $table->string('phone', 50)->nullable()->after('box_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn(['region', 'ward', 'village', 'box_number', 'phone']);
        });
    }
};