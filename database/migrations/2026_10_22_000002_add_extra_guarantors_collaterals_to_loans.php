<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->json('additional_guarantors')->nullable()->after('guarantor_region');
            $table->json('additional_collaterals')->nullable()->after('collateral_description');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['additional_guarantors', 'additional_collaterals']);
        });
    }
};
