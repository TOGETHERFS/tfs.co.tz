<?php

namespace App\Models\Accounting;

use App\Models\BaseModel;
use App\Models\Client;
use App\Models\Branch;
use Illuminate\Database\Eloquent\SoftDeletes;

class SavingsAccount extends BaseModel
{
    use SoftDeletes;

    protected $table = 'savings_accounts';

    protected $fillable = [
        'tenant_id',
        'client_id',
        'liability_account_id',
        'account_number',
        'account_type',
        'current_balance',
        'interest_rate',
        'opened_date',
        'status',
        'closed_date',
        'branch_id',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'opened_date' => 'date',
        'closed_date' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function liabilityAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'liability_account_id');
    }

    public function transactions()
    {
        return $this->hasMany(SavingsTransaction::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public static function generateAccountNumber(): string
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;
        
        $lastAccount = self::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastAccount) {
            $lastNumber = intval(substr($lastAccount->account_number, 3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return 'SAV' . str_pad($newNumber, 8, '0', STR_PAD_LEFT);
    }

    public static function getStatuses(): array
    {
        return [
            'active' => 'Active',
            'dormant' => 'Dormant',
            'closed' => 'Closed',
        ];
    }

    public static function getAccountTypes(): array
    {
        return [
            'regular' => 'Regular Savings',
            'fixed' => 'Fixed Deposit',
            'compulsory' => 'Compulsory Savings',
        ];
    }
}
