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
            $table->string('lga_officer_name')->nullable()->after('guarantor_region');
            $table->string('lga_position')->nullable()->after('lga_officer_name');
            $table->string('lga_phone')->nullable()->after('lga_position');
            $table->string('lga_street')->nullable()->after('lga_phone');
            $table->string('lga_ward')->nullable()->after('lga_street');
            $table->string('lga_district')->nullable()->after('lga_ward');
            $table->string('lga_region')->nullable()->after('lga_district');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn([
                'lga_officer_name',
                'lga_position',
                'lga_phone',
                'lga_street',
                'lga_ward',
                'lga_district',
                'lga_region',
            ]);
        });
    }
};
