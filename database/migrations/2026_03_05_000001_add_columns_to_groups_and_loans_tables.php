<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add missing columns to groups table
        Schema::table('groups', function (Blueprint $table) {
            if (!Schema::hasColumn('groups', 'branch_name')) {
                $table->string('branch_name', 150)->nullable()->after('name');
            }
            if (!Schema::hasColumn('groups', 'loan_officer')) {
                $table->string('loan_officer', 150)->nullable()->after('branch_name');
            }
            if (!Schema::hasColumn('groups', 'meeting_area')) {
                $table->string('meeting_area', 150)->nullable()->after('loan_officer');
            }
            if (!Schema::hasColumn('groups', 'bank_account')) {
                $table->string('bank_account', 120)->nullable()->after('meeting_area');
            }
            if (!Schema::hasColumn('groups', 'region')) {
                $table->string('region', 120)->nullable()->after('bank_account');
            }
            if (!Schema::hasColumn('groups', 'ward')) {
                $table->string('ward', 120)->nullable()->after('region');
            }
            if (!Schema::hasColumn('groups', 'village')) {
                $table->string('village', 120)->nullable()->after('ward');
            }
            if (!Schema::hasColumn('groups', 'box_number')) {
                $table->string('box_number', 120)->nullable()->after('village');
            }
            if (!Schema::hasColumn('groups', 'phone')) {
                $table->string('phone', 50)->nullable()->after('box_number');
            }
        });

        // Add group_id to loans table
        Schema::table('loans', function (Blueprint $table) {
            if (!Schema::hasColumn('loans', 'group_id')) {
                $table->foreignId('group_id')->nullable()->after('client_id')
                      ->constrained('groups')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            if (Schema::hasColumn('loans', 'group_id')) {
                $table->dropForeign(['group_id']);
                $table->dropColumn('group_id');
            }
        });

        Schema::table('groups', function (Blueprint $table) {
            $cols = ['branch_name','loan_officer','meeting_area','bank_account','region','ward','village','box_number','phone'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('groups', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
