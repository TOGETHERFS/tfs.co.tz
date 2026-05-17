<?php

namespace App\Models\Accounting;

use App\Models\BaseModel;
use App\Models\Branch;

class GeneralLedger extends BaseModel
{
    protected $table = 'general_ledger';

    protected $fillable = [
        'tenant_id',
        'account_id',
        'journal_entry_id',
        'journal_line_id',
        'fiscal_year_id',
        'period_id',
        'transaction_date',
        'entry_number',
        'description',
        'debit_amount',
        'credit_amount',
        'running_balance',
        'reference_type',
        'reference_id',
        'branch_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'running_balance' => 'decimal:2',
    ];

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function journalLine()
    {
        return $this->belongsTo(JournalEntryLine::class, 'journal_line_id');
    }

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function period()
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeForPeriod($query, int $periodId)
    {
        return $query->where('period_id', $periodId);
    }

    public function getNetAmountAttribute(): float
    {
        return $this->debit_amount - $this->credit_amount;
    }
}
