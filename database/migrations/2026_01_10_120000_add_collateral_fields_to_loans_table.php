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
            $table->string('collateral_type')->nullable()->after('notes');
            $table->decimal('collateral_value', 15, 2)->nullable()->after('collateral_type');
            $table->decimal('collateral_buying_price', 15, 2)->nullable()->after('collateral_value');
            $table->decimal('collateral_selling_price', 15, 2)->nullable()->after('collateral_buying_price');
            $table->string('collateral_description')->nullable()->after('collateral_selling_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn([
                'collateral_type',
                'collateral_value',
                'collateral_buying_price',
                'collateral_selling_price',
                'collateral_description',
            ]);
        });
    }
};
