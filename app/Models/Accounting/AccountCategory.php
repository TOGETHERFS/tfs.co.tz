<?php

namespace App\Models\Accounting;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountCategory extends BaseModel
{
    protected $table = 'account_categories';

    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'type',
        'normal_balance',
        'description',
        'sort_order',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function accounts()
    {
        return $this->hasMany(ChartOfAccount::class, 'category_id');
    }

    public static function getTypes(): array
    {
        return [
            'asset' => 'Assets',
            'liability' => 'Liabilities',
            'equity' => 'Equity',
            'income' => 'Income',
            'expense' => 'Expenses',
        ];
    }

    public function getTypeDisplayAttribute(): string
    {
        return self::getTypes()[$this->type] ?? ucfirst($this->type);
    }
}
