<?php

namespace App\Models\Accounting;

use App\Models\BaseModel;

class BankAccount extends BaseModel
{
    protected $table = 'bank_accounts';

    protected $fillable = [
        'tenant_id',
        'account_id',
        'bank_name',
        'account_number',
        'account_holder',
        'branch_name',
        'swift_code',
        'currency',
        'current_balance',
        'is_active',
    ];

    protected $casts = [
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function chartAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getFullNameAttribute(): string
    {
        return $this->bank_name . ' - ' . $this->account_number;
    }
}
