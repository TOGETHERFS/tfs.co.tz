<?php

namespace App\Models\Accounting;

use App\Models\BaseModel;

class FixedAssetCategory extends BaseModel
{
    protected $table = 'fixed_asset_categories';

    protected $fillable = [
        'tenant_id',
        'asset_account_id',
        'depreciation_account_id',
        'accum_depr_account_id',
        'name',
        'code',
        'description',
        'depreciation_method',
        'useful_life_years',
        'salvage_value_percentage',
        'is_active',
    ];

    protected $casts = [
        'useful_life_years' => 'decimal:2',
        'salvage_value_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function assetAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'asset_account_id');
    }

    public function depreciationAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'depreciation_account_id');
    }

    public function accumulatedDepreciationAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'accum_depr_account_id');
    }

    public function assets()
    {
        return $this->hasMany(FixedAsset::class, 'category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function getDepreciationMethods(): array
    {
        return [
            'straight_line' => 'Straight Line',
            'declining_balance' => 'Declining Balance',
            'none' => 'No Depreciation',
        ];
    }
}
