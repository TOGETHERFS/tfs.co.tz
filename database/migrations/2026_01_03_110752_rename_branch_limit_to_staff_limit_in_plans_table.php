<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameBranchLimitToStaffLimitInPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('plans', function (Blueprint $table) {
            // Add staff_limit column if it doesn't exist
            if (!Schema::hasColumn('plans', 'staff_limit')) {
                $table->unsignedInteger('staff_limit')->nullable()->after('branch_limit');
            }
        });

        // Copy data from branch_limit to staff_limit and update with new values
        \DB::table('plans')->where('code', 'starter')->update(['staff_limit' => 1, 'price' => 30000]);
        \DB::table('plans')->where('code', 'growth')->update(['staff_limit' => 4, 'price' => 80000]);
        \DB::table('plans')->where('code', 'enterprise')->update(['staff_limit' => 10, 'price' => 100000]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'staff_limit')) {
                $table->dropColumn('staff_limit');
            }
        });

        // Restore original prices
        \DB::table('plans')->where('code', 'starter')->update(['price' => 50000]);
        \DB::table('plans')->where('code', 'growth')->update(['price' => 100000]);
        \DB::table('plans')->where('code', 'enterprise')->update(['price' => 250000]);
    }
}
