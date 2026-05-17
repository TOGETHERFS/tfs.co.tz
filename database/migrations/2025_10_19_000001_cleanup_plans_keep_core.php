<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Deactivate any plans not in the core set
        DB::table('plans')
            ->whereNotIn('code', ['starter','growth','enterprise'])
            ->update(['is_active' => false]);

        // Normalize period to monthly for core plans
        DB::table('plans')
            ->whereIn('code', ['starter','growth','enterprise'])
            ->where('period', '!=', 'monthly')
            ->update(['period' => 'monthly']);
    }

    public function down(): void
    {
        // No-op: cannot reliably restore previous periods or activation states
    }
};