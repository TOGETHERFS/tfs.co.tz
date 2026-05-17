<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (!Schema::hasColumn('loans', 'disbursed_by')) {
                $table->unsignedBigInteger('disbursed_by')->nullable()->after('disbursed_at');
            }
            if (!Schema::hasColumn('loans', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('disbursed_by');
            }
            if (!Schema::hasColumn('loans', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('created_by');
            }
            if (!Schema::hasColumn('loans', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            foreach (['disbursed_by', 'created_by', 'approved_by', 'approved_at'] as $col) {
                if (Schema::hasColumn('loans', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
