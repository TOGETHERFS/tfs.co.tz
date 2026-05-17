<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Services\RbacService;

class SyncTenantPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:sync-permissions 
                            {--tenant= : Sync only a specific tenant by ID}
                            {--dry-run : Show what would be synced without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync default roles and permissions across all tenants to ensure consistency';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->option('tenant');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in DRY-RUN mode. No changes will be made.');
        }

        // Get tenants to process
        $query = Tenant::query();
        if ($tenantId) {
            $query->where('id', $tenantId);
        }
        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found.');
            return 1;
        }

        $this->info("Found {$tenants->count()} tenant(s) to process.");
        $this->newLine();

        $bar = $this->output->createProgressBar($tenants->count());
        $bar->start();

        $results = [];

        foreach ($tenants as $tenant) {
            $beforeRoles = \App\Models\Role::where('tenant_id', $tenant->id)->count();
            $beforePerms = \App\Models\Permission::where('tenant_id', $tenant->id)->count();

            if (!$dryRun) {
                try {
                    $seeded = RbacService::seedDefaultsForTenant($tenant);
                    $afterRoles = count($seeded['roles'] ?? []);
                    $afterPerms = count($seeded['permissions'] ?? []);

                    $results[] = [
                        'tenant_id' => $tenant->id,
                        'tenant_name' => $tenant->name,
                        'roles_before' => $beforeRoles,
                        'roles_after' => $afterRoles,
                        'perms_before' => $beforePerms,
                        'perms_after' => $afterPerms,
                        'status' => 'OK',
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'tenant_id' => $tenant->id,
                        'tenant_name' => $tenant->name,
                        'roles_before' => $beforeRoles,
                        'roles_after' => '-',
                        'perms_before' => $beforePerms,
                        'perms_after' => '-',
                        'status' => 'ERROR: ' . $e->getMessage(),
                    ];
                }
            } else {
                $results[] = [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'roles_before' => $beforeRoles,
                    'roles_after' => '(would sync)',
                    'perms_before' => $beforePerms,
                    'perms_after' => '(would sync)',
                    'status' => 'DRY-RUN',
                ];
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Display results table
        $this->table(
            ['Tenant ID', 'Name', 'Roles Before', 'Roles After', 'Perms Before', 'Perms After', 'Status'],
            array_map(function ($r) {
                return [
                    $r['tenant_id'],
                    \Illuminate\Support\Str::limit($r['tenant_name'], 20),
                    $r['roles_before'],
                    $r['roles_after'],
                    $r['perms_before'],
                    $r['perms_after'],
                    $r['status'],
                ];
            }, $results)
        );

        $successCount = count(array_filter($results, fn($r) => $r['status'] === 'OK'));
        $errorCount = count(array_filter($results, fn($r) => str_starts_with($r['status'], 'ERROR')));

        $this->newLine();
        if ($dryRun) {
            $this->info("Dry run complete. Run without --dry-run to apply changes.");
        } else {
            $this->info("Sync complete. Success: {$successCount}, Errors: {$errorCount}");
        }

        return $errorCount > 0 ? 1 : 0;
    }
}
