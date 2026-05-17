<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Repayment extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'loan_id',
        'schedule_id',
        'user_id',
        'receipt_number',
        'amount',
        'payment_method',
        'reference',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    /**
     * Get the loan that owns the repayment.
     */
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Get the schedule that owns the repayment.
     */
    public function schedule()
    {
        return $this->belongsTo(LoanSchedule::class, 'schedule_id');
    }

    /**
     * Get the user that processed the repayment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the client through the loan.
     */
    public function client()
    {
        return $this->loan->client();
    }

    /**
     * Scope a query for a specific date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    /**
     * Scope a query for a specific payment method.
     */
    public function scopeByPaymentMethod($query, $method)
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Generate receipt number with tenant ID prefix.
     */
    public static function generateReceiptNumber($tenantId = null)
    {
        if (!$tenantId) {
            $tenantId = auth()->check() ? auth()->user()->tenant_id : session('tenant_id');
        }
        
        // Get last receipt number for this tenant only
        $lastRepayment = static::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('receipt_number', 'LIKE', 'RCP-T' . $tenantId . '-%')
            ->latest('id')
            ->first();
        
        if ($lastRepayment) {
            // Extract the number after the last dash
            $parts = explode('-', $lastRepayment->receipt_number);
            $lastNumber = intval(end($parts));
        } else {
            $lastNumber = 0;
        }
        
        return 'RCP-T' . $tenantId . '-' . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($repayment) {
            if (!$repayment->receipt_number) {
                $repayment->receipt_number = static::generateReceiptNumber($repayment->tenant_id);
            }
        });

        static::created(function ($repayment) {
            // Update loan schedule
            if ($repayment->schedule_id) {
                $repayment->schedule->markAsPaid($repayment->amount);
            }
        });
    }
}