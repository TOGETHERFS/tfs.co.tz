<?php

namespace App\Models\Accounting;

use App\Models\BaseModel;
use App\Models\User;

class AccountingPeriod extends BaseModel
{
    protected $table = 'accounting_periods';

    protected $fillable = [
        'tenant_id',
        'fiscal_year_id',
        'name',
        'start_date',
        'end_date',
        'period_number',
        'is_closed',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'period_number' => 'integer',
        'is_closed' => 'boolean',
        'closed_at' => 'datetime',
    ];

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class, 'period_id');
    }

    public function closedByUser()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function scopeOpen($query)
    {
        return $query->where('is_closed', false);
    }

    public function scopeCurrent($query)
    {
        $today = now()->format('Y-m-d');
        return $query->where('start_date', '<=', $today)
                     ->where('end_date', '>=', $today);
    }

    public function containsDate($date): bool
    {
        $date = is_string($date) ? \Carbon\Carbon::parse($date) : $date;
        return $date->between($this->start_date, $this->end_date);
    }

    public function close(int $userId): void
    {
        $this->update([
            'is_closed' => true,
            'closed_at' => now(),
            'closed_by' => $userId,
        ]);
    }

    public function canPostEntry(): bool
    {
        return !$this->is_closed && !$this->fiscalYear->is_closed;
    }
}
