<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\Branch;
use App\Models\LoanProduct;
use App\Models\Role;
use App\Models\User;
use App\Models\Subscription;
use App\Services\TenantOnboardingService;
use App\Services\RbacService;

class RepairTenants extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:repair 
                            {--tenant= : Specific tenant ID to repair}
                            {--email= : Find tenant by user email}
                            {--dry-run : Show what would be repaired without making changes}
                            {--extend-trial : Extend trial by 3 days for repaired tenants}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Repair tenants with missing branches, loan products, or roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId   = $this->option('tenant');
        $email      = $this->option('email');
        $dryRun     = $this->option('dry-run');
        $extendTrial = $this->option('extend-trial');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $query = Tenant::query();
        if ($tenantId) {
            $query->where('id', $tenantId);
        } elseif ($email) {
            $user = User::where('email', strtolower(trim($email)))->first();
            if (!$user || !$user->tenant_id) {
                $this->error("No user/tenant found for email: {$email}");
                return 1;
            }
            $query->where('id', $user->tenant_id);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->error('No tenants found.');
            return 1;
        }

        $this->info("Checking {$tenants->count()} tenant(s)...\n");

        $headers = ['ID', 'Name', 'Email', 'Branches', 'Products', 'Roles', 'Trial', 'Status'];
        $rows = [];

        foreach ($tenants as $tenant) {
            $branchCount  = Branch::where('tenant_id', $tenant->id)->count();
            $productCount = LoanProduct::where('tenant_id', $tenant->id)->count();
            $roleCount    = Role::where('tenant_id', $tenant->id)->count();
            $hasTrial     = $tenant->trial_ends_at && $tenant->trial_ends_at->isFuture() ? 'active' : 'expired/none';

            $needsRepair = $branchCount === 0 || $productCount === 0 || $roleCount === 0;
            $status = $needsRepair ? 'NEEDS REPAIR' : 'OK';

            $rows[] = [
                $tenant->id,
                substr($tenant->name, 0, 20),
                $tenant->contact_email,
                $branchCount,
                $productCount,
                $roleCount,
                $hasTrial,
                $status,
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();

        // Repair tenants
        $repairedCount = 0;
        foreach ($tenants as $tenant) {
            $branchCount  = Branch::where('tenant_id', $tenant->id)->count();
            $productCount = LoanProduct::where('tenant_id', $tenant->id)->count();
            $roleCount    = Role::where('tenant_id', $tenant->id)->count();
            $trialExpired = !$tenant->trial_ends_at || $tenant->trial_ends_at->isPast();

            $needsRepair = $branchCount === 0 || $productCount === 0 || $roleCount === 0 || ($extendTrial && $trialExpired);

            if ($needsRepair) {
                $this->info("Repairing tenant: {$tenant->name} (ID: {$tenant->id}) <{$tenant->contact_email}>");

                if (!$dryRun) {
                    try {
                        $repairs = TenantOnboardingService::repairTenant($tenant);

                        if (isset($repairs['branch_created'])) {
                            $this->line("  + Created default branch (ID: {$repairs['branch_created']})");
                        }
                        if (isset($repairs['products_created'])) {
                            $this->line("  + Created {$repairs['products_created']} loan product(s)");
                        }
                        if (isset($repairs['roles_created'])) {
                            $this->line("  + Created {$repairs['roles_created']} role(s)");
                        }

                        // Assign admin role to tenant owner user if missing
                        $adminRole = Role::where('tenant_id', $tenant->id)
                            ->where('slug', 'admin')
                            ->first();
                        $ownerUser = User::where('tenant_id', $tenant->id)
                            ->where('role', 'admin')
                            ->first();
                        if ($adminRole && $ownerUser) {
                            $hasRole = $ownerUser->roles()->where('roles.id', $adminRole->id)->exists();
                            if (!$hasRole) {
                                RbacService::attachUserRole($ownerUser, $adminRole);
                                $this->line("  + Assigned admin role to user: {$ownerUser->email}");
                            }
                        }

                        // Extend trial if requested or expired
                        if ($extendTrial && $trialExpired) {
                            $tenant->update(['trial_ends_at' => now()->addDays(3), 'status' => 'active']);
                            $this->line('  + Extended trial by 3 days');
                        }

                        // Ensure tenant status is active
                        if ($tenant->status !== 'active') {
                            $tenant->update(['status' => 'active']);
                            $this->line('  + Set tenant status to active');
                        }

                        $repairedCount++;
                    } catch (\Exception $e) {
                        $this->error("  x Failed: {$e->getMessage()}");
                    }
                } else {
                    if ($branchCount === 0)  $this->line('  Would create default branch');
                    if ($productCount === 0) $this->line('  Would create loan products');
                    if ($roleCount === 0)    $this->line('  Would create default roles');
                    if ($extendTrial && $trialExpired) $this->line('  Would extend trial by 3 days');
                    $repairedCount++;
                }
                $this->newLine();
            }
        }

        if ($repairedCount === 0) {
            $this->info('All tenants are properly configured. No repairs needed.');
        } else {
            $action = $dryRun ? 'would be repaired' : 'repaired';
            $this->info("{$repairedCount} tenant(s) {$action}.");
        }

        return 0;
    }
}
