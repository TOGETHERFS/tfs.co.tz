<?php

namespace App\Models\Accounting;

use App\Models\BaseModel;
use App\Models\User;

class FiscalYear extends BaseModel
{
    protected $table = 'fiscal_years';

    protected $fillable = [
        'tenant_id',
        'name',
        'start_date',
        'end_date',
        'is_active',
        'is_closed',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'is_closed' => 'boolean',
        'closed_at' => 'datetime',
    ];

    public function periods()
    {
        return $this->hasMany(AccountingPeriod::class);
    }

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function closedByUser()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
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

        $this->periods()->where('is_closed', false)->update([
            'is_closed' => true,
            'closed_at' => now(),
            'closed_by' => $userId,
        ]);
    }

    public function generatePeriods(): void
    {
        $startDate = $this->start_date->copy();
        $endDate = $this->end_date;
        $periodNumber = 1;

        while ($startDate->lte($endDate)) {
            $periodEnd = $startDate->copy()->endOfMonth();
            if ($periodEnd->gt($endDate)) {
                $periodEnd = $endDate;
            }

            AccountingPeriod::create([
                'tenant_id' => $this->tenant_id,
                'fiscal_year_id' => $this->id,
                'name' => $startDate->format('F Y'),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $periodEnd->format('Y-m-d'),
                'period_number' => $periodNumber,
            ]);

            $startDate = $periodEnd->copy()->addDay();
            $periodNumber++;
        }
    }
}
