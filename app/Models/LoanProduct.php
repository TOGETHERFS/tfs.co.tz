<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanProduct extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'min_amount',
        'max_amount',
        'interest_rate',
        'interest_type',
        'min_term',
        'max_term',
        'processing_fee',
        'processing_fee_type',
        'repayment_type',
        'penalty_type',
        'penalty_value',
        'penalty_frequency',
        'is_active',
    ];

    protected $casts = [
        'min_amount' => 'integer',
        'max_amount' => 'integer',
        'interest_rate' => 'decimal:2',
        'min_term' => 'integer',
        'max_term' => 'integer',
        'processing_fee' => 'decimal:2',
        'penalty_value' => 'decimal:2',
        'penalty_frequency' => 'decimal:2',
        'is_active' => 'boolean',
        'repayment_type' => 'string',
    ];

    /**
     * Get the loans for the product.
     */
    public function loans()
    {
        return $this->hasMany(Loan::class, 'product_id');
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Whether this product uses interest-only repayment (balloon principal on last installment).
     */
    public function isInterestOnly(): bool
    {
        return ($this->repayment_type ?? 'amortized') === 'interest_only';
    }

    /**
     * Calculate monthly payment for given amount and term.
     * For interest_only products, returns the interest-only installment amount.
     */
    public function calculateMonthlyPayment($amount, $term)
    {
        if ($this->isInterestOnly()) {
            // Interest-only: each installment = interest only (principal paid on last)
            $monthlyRate = $this->interest_rate / 100;
            return round($amount * $monthlyRate, 2);
        }

        $monthlyRate = $this->interest_rate / 100 / 12;
        
        if ($this->interest_type === 'flat') {
            $interest = ($amount * $this->interest_rate / 100) * ($term / 12);
            return ($amount + $interest) / $term;
        }
        
        // Reducing balance calculation
        if ($monthlyRate == 0) {
            return $amount / $term;
        }
        
        return $amount * ($monthlyRate * pow(1 + $monthlyRate, $term)) / 
               (pow(1 + $monthlyRate, $term) - 1);
    }

    /**
     * Calculate total amount for given principal and term.
     */
    public function calculateTotalAmount($principal, $term)
    {
        if ($this->isInterestOnly()) {
            // Total = (interest per period * term) + principal
            $interestPerPeriod = $this->calculateMonthlyPayment($principal, $term);
            return round(($interestPerPeriod * $term) + $principal, 2);
        }
        $monthlyPayment = $this->calculateMonthlyPayment($principal, $term);
        return $monthlyPayment * $term;
    }

    /**
     * Calculate processing fee for given amount.
     * Supports both percentage and fixed amount based on processing_fee_type.
     */
    public function calculateProcessingFee($amount)
    {
        if ($this->processing_fee_type === 'fixed') {
            return $this->processing_fee;
        }
        
        // Default to percentage
        return ($amount * $this->processing_fee / 100);
    }
}