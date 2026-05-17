<?php

namespace App\Models\Accounting;

use App\Models\BaseModel;
use App\Models\Branch;

class JournalEntryLine extends BaseModel
{
    protected $table = 'journal_entry_lines';

    protected $fillable = [
        'tenant_id',
        'journal_entry_id',
        'account_id',
        'description',
        'debit_amount',
        'credit_amount',
        'reference_type',
        'reference_id',
        'branch_id',
    ];

    protected $casts = [
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
    ];

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function getNetAmountAttribute(): float
    {
        return $this->debit_amount - $this->credit_amount;
    }

    public function isDebit(): bool
    {
        return $this->debit_amount > 0;
    }

    public function isCredit(): bool
    {
        return $this->credit_amount > 0;
    }
}
