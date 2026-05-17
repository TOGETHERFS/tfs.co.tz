<?php

namespace App\Models\Accounting;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChartOfAccount extends BaseModel
{
    use SoftDeletes;

    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'tenant_id',
        'category_id',
        'parent_id',
        'account_code',
        'account_name',
        'description',
        'account_type',
        'normal_balance',
        'is_active',
        'is_system',
        'allow_manual_entry',
        'is_bank_account',
        'is_cash_account',
        'opening_balance',
        'opening_balance_date',
        'current_balance',
        'currency',
        'level',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
        'allow_manual_entry' => 'boolean',
        'is_bank_account' => 'boolean',
        'is_cash_account' => 'boolean',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'opening_balance_date' => 'date',
        'level' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(AccountCategory::class, 'category_id');
    }

    public function parent()
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    public function journalLines()
    {
        return $this->hasMany(JournalEntryLine::class, 'account_id');
    }

    public function ledgerEntries()
    {
        return $this->hasMany(GeneralLedger::class, 'account_id');
    }

    public function bankAccount()
    {
        return $this->hasOne(BankAccount::class, 'account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('account_type', $type);
    }

    public function scopeAssets($query)
    {
        return $query->where('account_type', 'asset');
    }

    public function scopeLiabilities($query)
    {
        return $query->where('account_type', 'liability');
    }

    public function scopeEquity($query)
    {
        return $query->where('account_type', 'equity');
    }

    public function scopeIncome($query)
    {
        return $query->where('account_type', 'income');
    }

    public function scopeExpenses($query)
    {
        return $query->where('account_type', 'expense');
    }

    public function scopeCashAccounts($query)
    {
        return $query->where('is_cash_account', true);
    }

    public function scopeBankAccounts($query)
    {
        return $query->where('is_bank_account', true);
    }

    public function getFullNameAttribute(): string
    {
        return $this->account_code . ' - ' . $this->account_name;
    }

    public function updateBalance(float $debit, float $credit): void
    {
        if ($this->normal_balance === 'debit') {
            $this->current_balance += ($debit - $credit);
        } else {
            $this->current_balance += ($credit - $debit);
        }
        $this->save();
    }

    public function getBalance(?string $asOfDate = null): float
    {
        $query = $this->ledgerEntries();
        
        if ($asOfDate) {
            $query->where('transaction_date', '<=', $asOfDate);
        }

        $debits = $query->sum('debit_amount');
        $credits = $query->sum('credit_amount');

        if ($this->normal_balance === 'debit') {
            return $this->opening_balance + $debits - $credits;
        }
        
        return $this->opening_balance + $credits - $debits;
    }

    public static function getAccountTypes(): array
    {
        return [
            'asset' => 'Asset',
            'liability' => 'Liability',
            'equity' => 'Equity',
            'income' => 'Income',
            'expense' => 'Expense',
        ];
    }
}
