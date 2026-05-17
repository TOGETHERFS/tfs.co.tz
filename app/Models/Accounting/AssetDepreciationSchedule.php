<?php

namespace App\Models\Accounting;

use App\Models\BaseModel;

class AssetDepreciationSchedule extends BaseModel
{
    protected $table = 'asset_depreciation_schedules';

    protected $fillable = [
        'tenant_id',
        'asset_id',
        'period_id',
        'journal_entry_id',
        'depreciation_date',
        'depreciation_amount',
        'accumulated_depreciation',
        'book_value',
        'is_posted',
    ];

    protected $casts = [
        'depreciation_date' => 'date',
        'depreciation_amount' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'book_value' => 'decimal:2',
        'is_posted' => 'boolean',
    ];

    public function asset()
    {
        return $this->belongsTo(FixedAsset::class, 'asset_id');
    }

    public function period()
    {
        return $this->belongsTo(AccountingPeriod::class, 'period_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function scopePending($query)
    {
        return $query->where('is_posted', false);
    }

    public function scopePosted($query)
    {
        return $query->where('is_posted', true);
    }
}
