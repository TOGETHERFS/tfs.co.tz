<?php

namespace App\Services\Accounting;

use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Accounting\FiscalYear;
use App\Models\Accounting\AccountingPeriod;
use App\Models\Accounting\AccountingAuditTrail;
use Illuminate\Support\Facades\DB;

class JournalEntryService
{
    public function createEntry(array $data, array $lines): JournalEntry
    {
        return DB::transaction(function () use ($data, $lines) {
            $fiscalYear = $this->getFiscalYearForDate($data['entry_date']);
            $period = $this->getPeriodForDate($data['entry_date'], $fiscalYear->id);

            if ($period && $period->is_closed) {
                throw new \Exception('Cannot post to a closed accounting period.');
            }

            $entry = JournalEntry::create([
                'tenant_id' => session('tenant_id') ?? auth()->user()->tenant_id,
                'fiscal_year_id' => $fiscalYear->id,
                'period_id' => $period?->id,
                'entry_number' => $data['entry_number'] ?? JournalEntry::generateEntryNumber($data['prefix'] ?? 'JE'),
                'entry_date' => $data['entry_date'],
                'entry_type' => $data['entry_type'] ?? 'manual',
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'description' => $data['description'],
                'status' => $data['status'] ?? 'draft',
                'created_by' => auth()->id(),
                'is_auto_generated' => $data['is_auto_generated'] ?? false,
            ]);

            foreach ($lines as $line) {
                $entry->lines()->create([
                    'tenant_id' => $entry->tenant_id,
                    'account_id' => $line['account_id'],
                    'description' => $line['description'] ?? null,
                    'debit_amount' => $line['debit_amount'] ?? 0,
                    'credit_amount' => $line['credit_amount'] ?? 0,
                    'reference_type' => $line['reference_type'] ?? null,
                    'reference_id' => $line['reference_id'] ?? null,
                    'branch_id' => $line['branch_id'] ?? null,
                ]);
            }

            $entry->calculateTotals();

            AccountingAuditTrail::log('create', JournalEntry::class, $entry->id, null, $entry->toArray());

            return $entry->fresh(['lines']);
        });
    }

    public function postEntry(JournalEntry $entry, int $userId): void
    {
        if (!$entry->isBalanced()) {
            throw new \Exception('Journal entry must be balanced (debits = credits) before posting.');
        }

        if ($entry->period && $entry->period->is_closed) {
            throw new \Exception('Cannot post to a closed accounting period.');
        }

        $entry->post($userId);

        AccountingAuditTrail::log('post', JournalEntry::class, $entry->id, ['status' => 'approved'], ['status' => 'posted']);
    }

    public function approveEntry(JournalEntry $entry, int $userId): void
    {
        if (!$entry->isBalanced()) {
            throw new \Exception('Journal entry must be balanced before approval.');
        }

        $entry->approve($userId);

        AccountingAuditTrail::log('approve', JournalEntry::class, $entry->id, ['status' => 'pending_approval'], ['status' => 'approved']);
    }

    public function rejectEntry(JournalEntry $entry, int $userId, string $reason): void
    {
        $entry->reject($userId, $reason);

        AccountingAuditTrail::log('reject', JournalEntry::class, $entry->id, null, ['rejection_reason' => $reason]);
    }

    public function reverseEntry(JournalEntry $entry, int $userId, string $reason = null): JournalEntry
    {
        if ($entry->status !== 'posted') {
            throw new \Exception('Only posted entries can be reversed.');
        }

        $reversalEntry = $entry->reverse($userId, $reason);

        AccountingAuditTrail::log('reverse', JournalEntry::class, $entry->id, null, ['reversal_entry_id' => $reversalEntry->id]);

        return $reversalEntry;
    }

    protected function getFiscalYearForDate($date): FiscalYear
    {
        $tenantId = session('tenant_id') ?? (auth()->check() ? auth()->user()->tenant_id : null);

        $query = FiscalYear::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->where('is_closed', false);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $fiscalYear = $query->first();

        if (!$fiscalYear) {
            $fiscalYear = $this->createFiscalYear($date);
        }

        return $fiscalYear;
    }

    protected function getPeriodForDate($date, int $fiscalYearId): ?AccountingPeriod
    {
        return AccountingPeriod::where('fiscal_year_id', $fiscalYearId)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }

    protected function createFiscalYear($date): FiscalYear
    {
        $date = is_string($date) ? \Carbon\Carbon::parse($date) : $date;
        $startDate = $date->copy()->startOfYear();
        $endDate = $date->copy()->endOfYear();

        $fiscalYear = FiscalYear::create([
            'tenant_id' => session('tenant_id') ?? (auth()->check() ? auth()->user()->tenant_id : null),
            'name' => 'FY ' . $date->format('Y'),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => true,
        ]);

        $fiscalYear->generatePeriods();

        return $fiscalYear;
    }

    public function getAccountCode(string $purpose): ?int
    {
        $accountMap = [
            'cash_on_hand' => '1100',
            'cash_at_bank' => '1200',
            'mobile_money' => '1300',
            'loan_portfolio' => '1400',
            'interest_receivable' => '1500',
            'fees_receivable' => '1600',
            'client_savings' => '2100',
            'interest_income' => '4100',
            'processing_fee_income' => '4200',
            'penalty_income' => '4300',
            'loan_loss_provision' => '5400',
        ];

        $code = $accountMap[$purpose] ?? null;
        if (!$code) return null;

        $account = ChartOfAccount::where('account_code', $code)->first();
        return $account?->id;
    }
}
