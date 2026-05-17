<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteKwareData extends Command
{
    protected $signature = 'delete:kware-data {--force : Force deletion without confirmation}';
    protected $description = 'Delete all KWARE MICROFINANCE data from the system';

    public function handle()
    {
        if (!$this->option('force')) {
            $this->warn('This will permanently delete ALL KWARE MICROFINANCE data from the system!');
            $this->warn('This action cannot be undone.');
            $this->line('');
            
            if (!$this->confirm('Do you want to continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Starting KWARE MICROFINANCE data deletion...');
        $this->line('');

        // Find the KWARE MICROFINANCE tenant
        $tenant = DB::table('tenants')
            ->where('name', 'LIKE', '%KWARE%')
            ->orWhere('name', 'LIKE', '%MICROFINANCE%')
            ->first();

        if (!$tenant) {
            $this->error('KWARE MICROFINANCE tenant not found!');
            return 1;
        }

        $this->info('Found KWARE MICROFINANCE tenant:');
        $this->line('ID: ' . $tenant->id);
        $this->line('Name: ' . $tenant->name);
        $this->line('Slug: ' . $tenant->slug);
        $this->line('');

        $tenantId = $tenant->id;
        $deletedCount = 0;

        try {
            DB::beginTransaction();

            // Delete in proper order to respect foreign key constraints
            
            // 1. Delete repayments
            $repayments = DB::table('repayments')
                ->join('loans', 'repayments.loan_id', '=', 'loans.id')
                ->where('loans.tenant_id', $tenantId)
                ->count();
            DB::table('repayments')
                ->join('loans', 'repayments.loan_id', '=', 'loans.id')
                ->where('loans.tenant_id', $tenantId)
                ->delete();
            $deletedCount += $repayments;
            $this->info("Deleted {$repayments} repayments");

            // 2. Delete loan schedules
            $schedules = DB::table('loan_schedules')
                ->join('loans', 'loan_schedules.loan_id', '=', 'loans.id')
                ->where('loans.tenant_id', $tenantId)
                ->count();
            DB::table('loan_schedules')
                ->join('loans', 'loan_schedules.loan_id', '=', 'loans.id')
                ->where('loans.tenant_id', $tenantId)
                ->delete();
            $deletedCount += $schedules;
            $this->info("Deleted {$schedules} loan schedules");

            // 3. Delete loans
            $loans = DB::table('loans')
                ->where('tenant_id', $tenantId)
                ->count();
            DB::table('loans')
                ->where('tenant_id', $tenantId)
                ->delete();
            $deletedCount += $loans;
            $this->info("Deleted {$loans} loans");

            // 4. Delete clients
            $clients = DB::table('clients')
                ->where('tenant_id', $tenantId)
                ->count();
            DB::table('clients')
                ->where('tenant_id', $tenantId)
                ->delete();
            $deletedCount += $clients;
            $this->info("Deleted {$clients} clients");

            // 5. Delete users (excluding super admin)
            $users = DB::table('users')
                ->where('tenant_id', $tenantId)
                ->where('email', '!=', 'phidtechnology@gmail.com')
                ->count();
            DB::table('users')
                ->where('tenant_id', $tenantId)
                ->where('email', '!=', 'phidtechnology@gmail.com')
                ->delete();
            $deletedCount += $users;
            $this->info("Deleted {$users} users");

            // 6. Delete branches
            $branches = DB::table('branches')
                ->where('tenant_id', $tenantId)
                ->count();
            DB::table('branches')
                ->where('tenant_id', $tenantId)
                ->delete();
            $deletedCount += $branches;
            $this->info("Deleted {$branches} branches");

            // 7. Delete tenant roles
            $roles = DB::table('roles')
                ->where('tenant_id', $tenantId)
                ->count();
            DB::table('roles')
                ->where('tenant_id', $tenantId)
                ->delete();
            $deletedCount += $roles;
            $this->info("Deleted {$roles} roles");

            // 8. Delete permissions
            $permissions = DB::table('permissions')
                ->where('tenant_id', $tenantId)
                ->count();
            DB::table('permissions')
                ->where('tenant_id', $tenantId)
                ->delete();
            $deletedCount += $permissions;
            $this->info("Deleted {$permissions} permissions");

            // 9. Delete subscriptions
            $subscriptions = DB::table('subscriptions')
                ->where('tenant_id', $tenantId)
                ->count();
            DB::table('subscriptions')
                ->where('tenant_id', $tenantId)
                ->delete();
            $deletedCount += $subscriptions;
            $this->info("Deleted {$subscriptions} subscriptions");

            // 10. Delete the tenant itself
            DB::table('tenants')
                ->where('id', $tenantId)
                ->delete();
            $deletedCount += 1;
            $this->info("Deleted tenant record");

            DB::commit();

            $this->line('');
            $this->info('✅ KWARE MICROFINANCE data deletion completed successfully!');
            $this->info("Total records deleted: {$deletedCount}");
            $this->info('Tenant ID: ' . $tenantId . ' has been completely removed from the system.');

            // Log the deletion
            Log::warning('KWARE MICROFINANCE data deleted', [
                'tenant_id' => $tenantId,
                'tenant_name' => $tenant->name,
                'deleted_records' => $deletedCount,
                'deleted_by' => auth()->user() ? auth()->user()->email : 'console',
                'deleted_at' => now(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Error during deletion: ' . $e->getMessage());
            Log::error('KWARE MICROFINANCE deletion failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }

        return 0;
    }
}
