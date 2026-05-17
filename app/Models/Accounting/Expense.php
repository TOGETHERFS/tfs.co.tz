<?php

namespace App\Models\Accounting;

use App\Models\BaseModel;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends BaseModel
{
    use SoftDeletes;

    protected $table = 'expenses';

    protected $fillable = [
        'tenant_id',
        'category_id',
        'account_id',
        'payment_account_id',
        'journal_entry_id',
        'expense_number',
        'expense_date',
        'payee',
        'description',
        'amount',
        'payment_method',
        'payment_reference',
        'receipt_number',
        'attachment',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'branch_id',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function paymentAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'payment_account_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending_approval');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }

    public static function generateExpenseNumber(): string
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;
        $year = now()->format('Y');
        $month = now()->format('m');
        
        $lastExpense = self::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('expense_number', 'like', 'EXP-' . $year . $month . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastExpense) {
            $lastNumber = intval(substr($lastExpense->expense_number, -6));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return 'EXP-' . $year . $month . '-' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    public static function getStatuses(): array
    {
        return [
            'draft' => 'Draft',
            'pending_approval' => 'Pending Approval',
            'approved' => 'Approved',
            'paid' => 'Paid',
            'rejected' => 'Rejected',
        ];
    }

    public static function getPaymentMethods(): array
    {
        return [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'mobile_money' => 'Mobile Money',
            'cheque' => 'Cheque',
        ];
    }
}
