<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('loans')
            ->where('approval_stage', 'gm_approval')
            ->update(['approval_stage' => 'ceo_approval']);

        DB::table('loan_approvals')
            ->where('stage', 'gm_approval')
            ->update(['stage' => 'ceo_approval']);
    }

    public function down(): void
    {
        DB::table('loans')
            ->where('approval_stage', 'ceo_approval')
            ->update(['approval_stage' => 'gm_approval']);

        DB::table('loan_approvals')
            ->where('stage', 'ceo_approval')
            ->update(['stage' => 'gm_approval']);
    }
};
