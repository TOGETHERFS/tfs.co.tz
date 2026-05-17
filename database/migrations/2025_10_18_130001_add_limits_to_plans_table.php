<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedInteger('branch_limit')->nullable()->after('price');
            $table->unsignedInteger('staff_limit')->nullable()->after('branch_limit');
            $table->json('features')->nullable()->after('staff_limit');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['branch_limit', 'staff_limit', 'features']);
        });
    }
};