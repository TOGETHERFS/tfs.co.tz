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
            $table->boolean('guarantor_required')->default(false)->after('notes');
            $table->string('guarantor_type')->nullable()->after('guarantor_required');
            $table->string('guarantor_name')->nullable()->after('guarantor_type');
            $table->string('guarantor_phone')->nullable()->after('guarantor_name');
            $table->string('guarantor_email')->nullable()->after('guarantor_phone');
            $table->string('guarantor_street')->nullable()->after('guarantor_email');
            $table->string('guarantor_ward')->nullable()->after('guarantor_street');
            $table->string('guarantor_district')->nullable()->after('guarantor_ward');
            $table->string('guarantor_region')->nullable()->after('guarantor_district');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn([
                'guarantor_required',
                'guarantor_type',
                'guarantor_name',
                'guarantor_phone',
                'guarantor_email',
                'guarantor_street',
                'guarantor_ward',
                'guarantor_district',
                'guarantor_region',
            ]);
        });
    }
};
