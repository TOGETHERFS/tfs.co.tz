<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Loan;
use App\Models\Tenant;
use App\Models\Accounting\JournalEntry;
use App\Services\Accounting\AutomatedAccountingService;
use App\Services\Accounting\JournalEntryService;
use Illuminate\Support\Facades\Auth;

class BackfillLoanDisbursementEntries extends Command
{
    protected $signature   = 'accounting:backfill-disbursements {--tenant= : Specific tenant ID to process}';
    protected $description = 'Backfill missing loan disbursement journal entries for all disbursed/active/partially_paid loans';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');

        $loansQuery = Loan::with('client')
            ->whereIn('status', ['disbursed', 'active', 'partially_paid', 'completed', 'defaulted'])
            ->whereNotNull('disbursed_at');

        if ($tenantId) {
            $loansQuery->where('tenant_id', $tenantId);
        }

        $allLoans = $loansQuery->get();

        // Find which loans already have disbursement journal entries
        $existingEntries = JournalEntry::whereIn('reference_id', $allLoans->pluck('id'))
            ->where('reference_type', Loan::class)
            ->where('entry_type', 'loan_disbursement')
            ->pluck('reference_id')
            ->toArray();

        $missing = $allLoans->filter(fn($loan) => !in_array($loan->id, $existingEntries));

        $this->info("Total loans with disbursed/active status: {$allLoans->count()}");
        $this->info("Already have journal entries: " . count($existingEntries));
        $this->info("Missing journal entries (to backfill): {$missing->count()}");

        if ($missing->isEmpty()) {
            $this->info('Nothing to backfill. All loans already have disbursement entries.');
            return self::SUCCESS;
        }

        if (!$this->confirm("Proceed with backfilling {$missing->count()} loan entries?", true)) {
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($missing->count());
        $bar->start();

        $success = 0;
        $failed  = 0;

        foreach ($missing as $loan) {
            try {
                // Set session context so getAccount() can find tenant-scoped COA
                session(['tenant_id' => $loan->tenant_id]);

                $service = new AutomatedAccountingService(app(JournalEntryService::class));
                $service->recordLoanDisbursement($loan);
                $success++;
            } catch (\Throwable $e) {
                $this->newLine();
                $this->error("Loan #{$loan->loan_number} (tenant {$loan->tenant_id}): {$e->getMessage()}");
                $failed++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done. Backfilled: {$success}  |  Failed: {$failed}");

        if ($failed > 0) {
            $this->warn("Some loans failed. Common cause: no Chart of Accounts for that tenant. Run: php artisan accounts:seed-all-tenants first, then retry.");
        }

        return self::SUCCESS;
    }
}
