<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanSchedule extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'loan_id',
        'installment_number',
        'due_date',
        'principal_amount',
        'interest_amount',
        'total_amount',
        'paid_amount',
        'balance',
        'status',
        'paid_date',
    ];

    protected $casts = [
        'due_date' => 'date',
        'principal_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'paid_date' => 'date',
    ];

    /**
     * Get the loan that owns the schedule.
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Get the repayments for the schedule.
     */
    public function repayments()
    {
        return $this->hasMany(Repayment::class, 'schedule_id');
    }

    /**
     * Get the latest repayment for the schedule.
     */
    public function latestRepayment()
    {
        return $this->hasOne(Repayment::class, 'schedule_id')->latestOfMany();
    }

    /**
     * Scope a query to only include pending schedules.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include paid schedules.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope a query to only include overdue schedules.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
                    ->where('due_date', '<', now());
    }

    /**
     * Get remaining amount to be paid.
     */
    public function getRemainingAmountAttribute()
    {
        return $this->total_amount - $this->paid_amount;
    }

    /**
     * Check if schedule is overdue.
     */
    public function isOverdue()
    {
        return $this->status === 'pending' && $this->due_date->isPast();
    }

    /**
     * Check if schedule is fully paid.
     */
    public function isFullyPaid()
    {
        return $this->paid_amount >= $this->total_amount;
    }

    /**
     * Mark schedule as paid.
     */
    public function markAsPaid($amount = null)
    {
        $amount = $amount ?? $this->remaining_amount;
        $newPaidAmount = $this->paid_amount + $amount;
        $fullyPaid = $newPaidAmount >= $this->total_amount;

        $this->update([
            'paid_amount' => $newPaidAmount,
            'balance' => max(0, $this->total_amount - $newPaidAmount),
            'status' => $fullyPaid ? 'paid' : 'partial',
            'paid_date' => $fullyPaid ? now()->toDateString() : $this->paid_date,
        ]);
    }
}