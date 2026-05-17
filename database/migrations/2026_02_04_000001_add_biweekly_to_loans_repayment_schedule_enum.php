<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `loans` MODIFY `repayment_schedule` ENUM('daily','weekly','biweekly','monthly') NOT NULL DEFAULT 'monthly'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("UPDATE `loans` SET `repayment_schedule` = 'weekly' WHERE `repayment_schedule` = 'biweekly'");
            DB::statement("ALTER TABLE `loans` MODIFY `repayment_schedule` ENUM('daily','weekly','monthly') NOT NULL DEFAULT 'monthly'");
        }
    }
};
