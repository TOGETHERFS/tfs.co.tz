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
            $table->string('region', 100)->nullable()->after('address');
            $table->string('district', 100)->nullable()->after('region');
            $table->string('ward', 100)->nullable()->after('district');
            $table->string('street', 255)->nullable()->after('ward');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['region', 'district', 'ward', 'street']);
        });
    }
};
