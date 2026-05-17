<?php

namespace App\Models\Accounting;

use App\Models\BaseModel;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAsset extends BaseModel
{
    use SoftDeletes;

    protected $table = 'fixed_assets';

    protected $fillable = [
        'tenant_id',
        'category_id',
        'asset_code',
        'asset_name',
        'description',
        'serial_number',
        'location',
        'purchase_date',
        'purchase_price',
        'salvage_value',
        'useful_life_years',
        'depreciation_method',
        'accumulated_depreciation',
        'current_value',
        'last_depreciation_date',
        'status',
        'disposal_date',
        'disposal_amount',
        'disposal_notes',
        'purchase_journal_id',
        'disposal_journal_id',
        'branch_id',
        'created_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'purchase_price' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'useful_life_years' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'current_value' => 'decimal:2',
        'last_depreciation_date' => 'date',
        'disposal_date' => 'date',
        'disposal_amount' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(FixedAssetCategory::class, 'category_id');
    }

    public function depreciationSchedules()
    {
        return $this->hasMany(AssetDepreciationSchedule::class, 'asset_id');
    }

    public function purchaseJournal()
    {
        return $this->belongsTo(JournalEntry::class, 'purchase_journal_id');
    }

    public function disposalJournal()
    {
        return $this->belongsTo(JournalEntry::class, 'disposal_journal_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getDepreciableAmountAttribute(): float
    {
        return $this->purchase_price - $this->salvage_value;
    }

    public function getMonthlyDepreciationAttribute(): float
    {
        if ($this->depreciation_method === 'none') {
            return 0;
        }

        $depreciableAmount = $this->depreciable_amount;
        $totalMonths = $this->useful_life_years * 12;

        if ($this->depreciation_method === 'straight_line') {
            return $totalMonths > 0 ? $depreciableAmount / $totalMonths : 0;
        }

        if ($this->depreciation_method === 'declining_balance') {
            $rate = 2 / $this->useful_life_years;
            return $this->current_value * ($rate / 12);
        }

        return 0;
    }

    public function calculateDepreciation(\DateTime $asOfDate = null): float
    {
        $asOfDate = $asOfDate ?? now();
        
        if ($this->depreciation_method === 'none') {
            return 0;
        }

        $startDate = $this->last_depreciation_date ?? $this->purchase_date;
        $monthsDiff = $startDate->diffInMonths($asOfDate);

        if ($monthsDiff <= 0) {
            return 0;
        }

        $remainingValue = $this->current_value - $this->salvage_value;
        
        if ($remainingValue <= 0) {
            return 0;
        }

        $depreciation = $this->monthly_depreciation * $monthsDiff;
        
        return min($depreciation, $remainingValue);
    }

    public static function generateAssetCode(): string
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;
        
        $lastAsset = self::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastAsset) {
            $lastNumber = intval(substr($lastAsset->asset_code, 3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return 'FA-' . str_pad($newNumber, 6, '0', STR_PAD_LEFT);
    }

    public static function getStatuses(): array
    {
        return [
            'active' => 'Active',
            'disposed' => 'Disposed',
            'sold' => 'Sold',
            'written_off' => 'Written Off',
        ];
    }
}
