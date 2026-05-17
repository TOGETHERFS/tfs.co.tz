<?php

namespace App\Models\Accounting;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends BaseModel
{
    use SoftDeletes;

    protected $table = 'journal_entries';

    protected $fillable = [
        'tenant_id',
        'fiscal_year_id',
        'period_id',
        'entry_number',
        'entry_date',
        'entry_type',
        'reference_type',
        'reference_id',
        'description',
        'total_debit',
        'total_credit',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'posted_by',
        'posted_at',
        'reversed_by',
        'reversed_at',
        'reversal_entry_id',
        'rejection_reason',
        'is_auto_generated',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
        'reversed_at' => 'datetime',
        'is_auto_generated' => 'boolean',
    ];

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function period()
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }

    public function lines()
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reversedBy()
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function reversalEntry()
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_entry_id');
    }

    public function originalEntry()
    {
        return $this->hasOne(JournalEntry::class, 'reversal_entry_id');
    }

    public function ledgerEntries()
    {
        return $this->hasMany(GeneralLedger::class);
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('entry_type', $type);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('entry_date', [$startDate, $endDate]);
    }

    public function isBalanced(): bool
    {
        return bccomp($this->total_debit, $this->total_credit, 2) === 0;
    }

    public function calculateTotals(): void
    {
        $this->total_debit = $this->lines()->sum('debit_amount');
        $this->total_credit = $this->lines()->sum('credit_amount');
        $this->save();
    }

    public function approve(int $userId): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function reject(int $userId, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $userId,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function post(int $userId): void
    {
        if (!$this->isBalanced()) {
            throw new \Exception('Journal entry must be balanced before posting.');
        }

        $this->update([
            'status' => 'posted',
            'posted_by' => $userId,
            'posted_at' => now(),
        ]);

        $this->postToLedger();
        $this->updateAccountBalances();
    }

    protected function postToLedger(): void
    {
        foreach ($this->lines as $line) {
            GeneralLedger::create([
                'tenant_id' => $this->tenant_id,
                'account_id' => $line->account_id,
                'journal_entry_id' => $this->id,
                'journal_line_id' => $line->id,
                'fiscal_year_id' => $this->fiscal_year_id,
                'period_id' => $this->period_id,
                'transaction_date' => $this->entry_date,
                'entry_number' => $this->entry_number,
                'description' => $line->description ?? $this->description,
                'debit_amount' => $line->debit_amount,
                'credit_amount' => $line->credit_amount,
                'reference_type' => $line->reference_type ?? $this->reference_type,
                'reference_id' => $line->reference_id ?? $this->reference_id,
                'branch_id' => $line->branch_id,
            ]);
        }
    }

    protected function updateAccountBalances(): void
    {
        foreach ($this->lines as $line) {
            $line->account->updateBalance($line->debit_amount, $line->credit_amount);
        }
    }

    public function reverse(int $userId, string $reason = null): JournalEntry
    {
        $reversalEntry = self::create([
            'tenant_id' => $this->tenant_id,
            'fiscal_year_id' => $this->fiscal_year_id,
            'period_id' => $this->period_id,
            'entry_number' => $this->generateEntryNumber('REV'),
            'entry_date' => now(),
            'entry_type' => 'reversal',
            'reference_type' => get_class($this),
            'reference_id' => $this->id,
            'description' => 'Reversal of ' . $this->entry_number . ($reason ? ': ' . $reason : ''),
            'status' => 'draft',
            'created_by' => $userId,
            'is_auto_generated' => true,
        ]);

        foreach ($this->lines as $line) {
            $reversalEntry->lines()->create([
                'tenant_id' => $this->tenant_id,
                'account_id' => $line->account_id,
                'description' => 'Reversal: ' . ($line->description ?? ''),
                'debit_amount' => $line->credit_amount,
                'credit_amount' => $line->debit_amount,
                'branch_id' => $line->branch_id,
            ]);
        }

        $reversalEntry->calculateTotals();

        $this->update([
            'status' => 'reversed',
            'reversed_by' => $userId,
            'reversed_at' => now(),
            'reversal_entry_id' => $reversalEntry->id,
        ]);

        return $reversalEntry;
    }

    public static function generateEntryNumber(string $prefix = 'JE'): string
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;
        $year = now()->format('Y');
        $month = now()->format('m');
        
        $lastEntry = self::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('entry_number', 'like', $prefix . '-' . $year . $month . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastEntry) {
            $lastNumber = intval(substr($lastEntry->entry_number, -6));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . '-' . $year . $month . '-' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    public static function getEntryTypes(): array
    {
        return [
            'manual' => 'Manual Entry',
            'loan_disbursement' => 'Loan Disbursement',
            'loan_repayment' => 'Loan Repayment',
            'savings_deposit' => 'Savings Deposit',
            'savings_withdrawal' => 'Savings Withdrawal',
            'fee_income' => 'Fee Income',
            'penalty_income' => 'Penalty Income',
            'expense' => 'Expense',
            'asset_purchase' => 'Asset Purchase',
            'asset_depreciation' => 'Asset Depreciation',
            'asset_disposal' => 'Asset Disposal',
            'adjustment' => 'Adjustment',
            'opening_balance' => 'Opening Balance',
            'closing_entry' => 'Closing Entry',
            'reversal' => 'Reversal',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            'draft' => 'Draft',
            'pending_approval' => 'Pending Approval',
            'approved' => 'Approved',
            'posted' => 'Posted',
            'rejected' => 'Rejected',
            'reversed' => 'Reversed',
        ];
    }
}
