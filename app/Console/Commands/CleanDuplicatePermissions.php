<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDuplicatePermissions extends Command
{
    protected $signature = 'permissions:clean-duplicates';
    protected $description = 'Remove duplicate permissions for each tenant';

    public function handle()
    {
        $this->info('Starting duplicate permission cleanup...');

        $tenants = DB::table('tenants')->pluck('id');
        $totalRemoved = 0;

        foreach ($tenants as $tenantId) {
            $this->info("Processing tenant ID: {$tenantId}");

            // Find duplicate permissions (same slug and tenant_id)
            $duplicates = DB::table('permissions')
                ->select('slug', DB::raw('COUNT(*) as count'), DB::raw('MIN(id) as keep_id'))
                ->where('tenant_id', $tenantId)
                ->groupBy('slug', 'tenant_id')
                ->having('count', '>', 1)
                ->get();

            foreach ($duplicates as $duplicate) {
                $this->warn("  Found {$duplicate->count} duplicates of '{$duplicate->slug}'");

                // Get all IDs for this duplicate slug
                $allIds = DB::table('permissions')
                    ->where('tenant_id', $tenantId)
                    ->where('slug', $duplicate->slug)
                    ->pluck('id')
                    ->toArray();

                // IDs to delete (all except the one we're keeping)
                $idsToDelete = array_diff($allIds, [$duplicate->keep_id]);

                if (!empty($idsToDelete)) {
                    // Update role_permission pivot table to point to the kept permission
                    DB::table('role_permission')
                        ->whereIn('permission_id', $idsToDelete)
                        ->where('tenant_id', $tenantId)
                        ->update(['permission_id' => $duplicate->keep_id]);

                    // Delete duplicate permissions
                    $deleted = DB::table('permissions')
                        ->whereIn('id', $idsToDelete)
                        ->delete();

                    $totalRemoved += $deleted;
                    $this->info("  Removed {$deleted} duplicate(s), kept ID {$duplicate->keep_id}");
                }
            }
        }

        if ($totalRemoved > 0) {
            $this->info("✓ Cleanup complete! Removed {$totalRemoved} duplicate permission(s).");
        } else {
            $this->info("✓ No duplicate permissions found. Database is clean!");
        }

        return 0;
    }
}
