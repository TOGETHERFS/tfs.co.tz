<?php

namespace App\Models\Accounting;

use App\Models\BaseModel;

class ExpenseCategory extends BaseModel
{
    protected $table = 'expense_categories';

    protected $fillable = [
        'tenant_id',
        'account_id',
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
