<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;
use App\Services\RbacService;

class FixTenantOwnerRoles extends Command
{
    protected $signature = 'tenants:fix-owner-roles 
                            {--dry-run : Show what would be changed without making changes}';

    protected $description = 'Ensure all tenant owners (first user per tenant) have admin role with full permissions';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $tenants = Tenant::all();
        $this->info("Processing {$tenants->count()} tenant(s)...\n");

        $fixed = 0;
        $alreadyAdmin = 0;
        $errors = 0;

        foreach ($tenants as $tenant) {
            // Find the tenant owner (first user created for this tenant, or user with contact_email)
            $owner = User::where('tenant_id', $tenant->id)
                ->where(function ($q) use ($tenant) {
                    $q->where('email', $tenant->contact_email)
                      ->orWhere('id', User::where('tenant_id', $tenant->id)->min('id'));
                })
                ->first();

            if (!$owner) {
                $this->warn("  Tenant '{$tenant->name}' (ID: {$tenant->id}) has no users - skipping");
                continue;
            }

            // Check if owner is already admin
            $isAdmin = $owner->role === 'admin' || $owner->hasRole('admin');
            
            if ($isAdmin) {
                $alreadyAdmin++;
                $this->line("  ✓ Tenant '{$tenant->name}' - Owner '{$owner->name}' already admin");
                continue;
            }

            $this->info("  Fixing Tenant '{$tenant->name}' - Owner '{$owner->name}' (current role: {$owner->role})");

            if (!$dryRun) {
                try {
                    // Update user's role column to admin
                    $owner->update(['role' => 'admin']);

                    // Find or create admin role for this tenant
                    $adminRole = Role::where('tenant_id', $tenant->id)
                        ->where('slug', 'admin')
                        ->first();

                    if (!$adminRole) {
                        // Seed defaults if admin role doesn't exist
                        $seed = RbacService::seedDefaultsForTenant($tenant);
                        $adminRole = $seed['roles']['admin'] ?? null;
                    }

                    if ($adminRole) {
                        // Attach admin role to user
                        RbacService::attachUserRole($owner, $adminRole);
                        $this->line("    → Assigned admin role with all permissions");
                    }

                    $fixed++;
                } catch (\Exception $e) {
                    $this->error("    ✗ Error: {$e->getMessage()}");
                    $errors++;
                }
            } else {
                $this->line("    → Would assign admin role");
                $fixed++;
            }
        }

        $this->newLine();
        $this->info("Summary:");
        $this->line("  Already admin: {$alreadyAdmin}");
        $this->line("  " . ($dryRun ? "Would fix" : "Fixed") . ": {$fixed}");
        if ($errors > 0) {
            $this->error("  Errors: {$errors}");
        }

        return $errors > 0 ? 1 : 0;
    }
}
