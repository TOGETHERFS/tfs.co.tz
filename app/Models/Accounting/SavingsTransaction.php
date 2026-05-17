<?php

namespace App\Models\Accounting;

use App\Models\BaseModel;
use App\Models\User;

class SavingsTransaction extends BaseModel
{
    protected $table = 'savings_transactions';

    protected $fillable = [
        'tenant_id',
        'savings_account_id',
        'journal_entry_id',
        'transaction_number',
        'transaction_date',
        'transaction_type',
        'amount',
        'running_balance',
        'payment_method',
        'reference',
        'description',
        'processed_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'running_balance' => 'decimal:2',
    ];

    public function savingsAccount()
    {
        return $this->belongsTo(SavingsAccount::class);
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopeDeposits($query)
    {
        return $query->where('transaction_type', 'deposit');
    }

    public function scopeWithdrawals($query)
    {
        return $query->where('transaction_type', 'withdrawal');
    }

    public static function generateTransactionNumber(): string
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;
        $date = now()->format('Ymd');
        
        $lastTransaction = self::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('transaction_number', 'like', 'STX' . $date . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastTransaction) {
            $lastNumber = intval(substr($lastTransaction->transaction_number, -6));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return 'STX' . $date . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    public static function getTransactionTypes(): array
    {
        return [
            'deposit' => 'Deposit',
            'withdrawal' => 'Withdrawal',
            'interest' => 'Interest',
            'fee' => 'Fee',
            'transfer' => 'Transfer',
        ];
    }
}
