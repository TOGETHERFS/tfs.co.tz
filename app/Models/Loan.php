<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Loan extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'client_id',
        'group_id',
        'product_id',
        'user_id',
        'loan_number',
        'application_date',
        'principal',
        'interest_rate',
        'interest_type',
        'term',
        'repayment_schedule',
        'monthly_payment',
        'total_amount',
        'processing_fee',
        'disbursed_at',
        'first_payment_date',
        'status',
        'notes',
        'approval_stage',
        'approval_stage_status',
        'collateral_type',
        'collateral_value',
        'collateral_buying_price',
        'collateral_selling_price',
        'collateral_description',
        'guarantor_required',
        'guarantor_type',
        'guarantor_name',
        'guarantor_phone',
        'guarantor_email',
        'guarantor_street',
        'guarantor_ward',
        'guarantor_district',
        'guarantor_region',
        'lga_officer_name',
        'lga_position',
        'lga_phone',
        'lga_street',
        'lga_ward',
        'lga_district',
        'lga_region',
        'disbursed_by',
        'created_by',
        'approved_by',
        'approved_at',
        'penalty_type',
        'penalty_value',
        'penalty_frequency',
    ];

    protected $appends = ['outstanding_balance', 'total_paid'];

    protected $casts = [
        'principal' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'term' => 'integer',
        'monthly_payment' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'processing_fee' => 'decimal:2',
        'penalty_value' => 'decimal:2',
        'penalty_frequency' => 'decimal:2',
        'application_date' => 'date',
        'disbursed_at' => 'datetime',
        'first_payment_date' => 'date',
    ];

    /**
     * Get the client that owns the loan.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the product that owns the loan.
     */
    public function product()
    {
        return $this->belongsTo(LoanProduct::class, 'product_id');
    }

    /**
     * Get the user that created the loan.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the loan schedules for the loan.
     */
    public function schedules()
    {
        return $this->hasMany(LoanSchedule::class);
    }

    /**
     * Get the repayments for the loan.
     */
    public function repayments()
    {
        return $this->hasMany(Repayment::class);
    }

    /**
     * Get the documents attached to the loan.
     */
    public function documents()
    {
        return $this->hasMany(LoanDocument::class);
    }

    /**
     * Scope a query to only include active loans.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['approved', 'disbursed', 'active']);
    }

    /**
     * Scope a query to only include overdue loans.
     */
    public function scopeOverdue($query)
    {
        return $query->whereIn('status', ['disbursed', 'active', 'partially_paid'])
            ->whereHas('schedules', function ($q) {
                $q->whereIn('status', ['pending', 'partial'])
                  ->where('due_date', '<', now());
            });
    }

    /**
     * Get total paid amount.
     */
    public function getTotalPaidAttribute()
    {
        if ($this->relationLoaded('repayments')) {
            return $this->repayments->sum('amount');
        }
        return $this->repayments()->sum('amount');
    }

    /**
     * Get outstanding balance.
     * Uses schedule-based calculation when paid_amount is synced.
     * Falls back to (total_amount - repayments) when schedule paid_amount
     * is unsynced (e.g. legacy imported data where paid_amount = 0).
     */
    public function getOutstandingBalanceAttribute()
    {
        $totalRepaid = (float) $this->total_paid;
        $loanTotal   = (float) ($this->attributes['total_amount'] ?? 0);

        if ($this->relationLoaded('schedules')) {
            $schedules = $this->schedules;
            if ($schedules->isNotEmpty()) {
                $schedPaid = (float) $schedules->sum('paid_amount');
                // If schedules have no paid_amount but repayments exist, fall back
                if ($schedPaid == 0 && $totalRepaid > 0) {
                    return max(0, $loanTotal - $totalRepaid);
                }
                return max(0, $schedules->whereNotIn('status', ['paid'])->sum(function ($s) {
                    return max(0, (float)$s->total_amount - (float)$s->paid_amount);
                }));
            }
        } else {
            $count = $this->schedules()->count();
            if ($count > 0) {
                $schedPaid = (float) $this->schedules()->sum('paid_amount');
                // If schedules have no paid_amount but repayments exist, fall back
                if ($schedPaid == 0 && $totalRepaid > 0) {
                    return max(0, $loanTotal - $totalRepaid);
                }
                return max(0, (float) $this->schedules()
                    ->whereNotIn('status', ['paid'])
                    ->selectRaw('SUM(total_amount - paid_amount) as remaining')
                    ->value('remaining') ?? 0);
            }
        }
        return max(0, $loanTotal - $totalRepaid);
    }

    /**
     * Get next payment due date.
     */
    public function getNextPaymentDueDateAttribute()
    {
        return $this->schedules()
                   ->where('status', 'pending')
                   ->orderBy('due_date')
                   ->value('due_date');
    }

    /**
     * Check if loan is overdue.
     */
    public function isOverdue()
    {
        return $this->schedules()
                   ->where('due_date', '<', now())
                   ->whereIn('status', ['pending', 'partial'])
                   ->exists();
    }

    /**
     * Generate loan schedule.
     */
    public function generateSchedule()
    {
        $this->schedules()->delete(); // Clear existing schedule

        $paymentDate = $this->first_payment_date;
        $installmentPayment = $this->monthly_payment;
        $repaymentSchedule = $this->repayment_schedule ?? 'monthly';

        $rMonthly = ($this->interest_rate / 100); // loan's interest_rate as monthly rate
        $product = $this->product ?? \App\Models\LoanProduct::find($this->product_id);
        $repaymentType = optional($product)->repayment_type ?? 'amortized';
        $interestTypeRaw = strtolower((string) ($this->interest_type ?? optional($product)->interest_type ?? 'flat'));
        $isReducing = in_array($interestTypeRaw, ['reducing', 'reducing_balance', 'reducing-balance'], true);
        $principalBalance = $this->principal;

        // Interest-only: pay interest each period, full principal balloon on last installment.
        if ($repaymentType === 'interest_only') {
            $interestPerInstallment = round($this->principal * $rMonthly, 2);
            for ($i = 1; $i <= $this->term; $i++) {
                $isLast         = ($i === $this->term);
                $principalAmt   = $isLast ? round($this->principal, 2) : 0.00;
                $totalPayment   = round($principalAmt + $interestPerInstallment, 2);
                $balanceAfter   = $isLast ? 0.00 : round($this->principal, 2);
                $this->schedules()->create([
                    'tenant_id'          => $this->tenant_id,
                    'installment_number' => $i,
                    'due_date'           => $paymentDate,
                    'principal_amount'   => $principalAmt,
                    'interest_amount'    => $interestPerInstallment,
                    'total_amount'       => $totalPayment,
                    'paid_amount'        => 0,
                    'balance'            => $balanceAfter,
                    'status'             => 'pending',
                ]);
                switch ($repaymentSchedule) {
                    case 'daily':    $paymentDate = $paymentDate->addDay();    break;
                    case 'weekly':   $paymentDate = $paymentDate->addWeek();   break;
                    case 'biweekly': $paymentDate = $paymentDate->addDays(14); break;
                    default:         $paymentDate = $paymentDate->addMonth();  break;
                }
            }
            $actualTotal = round(($interestPerInstallment * $this->term) + $this->principal, 2);
            $this->withoutEvents(function () use ($interestPerInstallment, $actualTotal) {
                $this->update(['monthly_payment' => $interestPerInstallment, 'total_amount' => $actualTotal]);
            });
            return;
        }

        $ratePerInstallment = $rMonthly;
        if ($repaymentSchedule === 'daily') {
            $ratePerInstallment = $rMonthly / 30;
        } elseif ($repaymentSchedule === 'weekly') {
            $ratePerInstallment = $rMonthly / 4;
        } elseif ($repaymentSchedule === 'biweekly') {
            $ratePerInstallment = $rMonthly / 2;
        }

        // For flat interest, calculate total interest based on loan duration in months
        $interestPerInstallment = 0;
        if (!$isReducing) {
            $termInMonths = $this->term;
            if ($repaymentSchedule === 'weekly') {
                $termInMonths = $this->term / 4;
            } elseif ($repaymentSchedule === 'daily') {
                $termInMonths = $this->term / 30;
            } elseif ($repaymentSchedule === 'biweekly') {
                $termInMonths = $this->term / 2;
            }
            $totalInterest = round($this->principal * $rMonthly * $termInMonths, 2);
            $interestPerInstallment = round($totalInterest / $this->term, 2);
        }

        $scheduleTotals = [];

        for ($i = 1; $i <= $this->term; $i++) {
            if ($isReducing && $ratePerInstallment > 0) {
                $interestAmount = round($principalBalance * $ratePerInstallment, 2);
                $principalAmount = round($installmentPayment - $interestAmount, 2);
                if ($principalAmount < 0) {
                    $principalAmount = 0;
                }
            } else {
                $interestAmount = $interestPerInstallment;
                $principalAmount = round($this->principal / $this->term, 2);
            }

            $totalPayment = round($principalAmount + $interestAmount, 2);
            $principalBalance = max(0, round($principalBalance - $principalAmount, 2));

            $scheduleTotals[] = $totalPayment;

            $this->schedules()->create([
                'tenant_id' => $this->tenant_id,
                'installment_number' => $i,
                'due_date' => $paymentDate,
                'principal_amount' => $principalAmount,
                'interest_amount' => $interestAmount,
                'total_amount' => $totalPayment,
                'paid_amount' => 0,
                'balance' => $principalBalance,
                'status' => 'pending',
            ]);

            // Increment payment date based on repayment schedule
            switch ($repaymentSchedule) {
                case 'daily':
                    $paymentDate = $paymentDate->addDay();
                    break;
                case 'weekly':
                    $paymentDate = $paymentDate->addWeek();
                    break;
                case 'monthly':
                default:
                    $paymentDate = $paymentDate->addMonth();
                    break;
            }
        }

        // Sync total_amount and monthly_payment on the loan from the actual schedule
        if (!empty($scheduleTotals)) {
            $actualTotal = round(array_sum($scheduleTotals), 2);
            $actualInstallment = round($scheduleTotals[0], 2);
            $this->withoutEvents(function () use ($actualTotal, $actualInstallment) {
                $this->update([
                    'total_amount'    => $actualTotal,
                    'monthly_payment' => $actualInstallment,
                ]);
            });
        }
    }

    public function approvals()
    {
        return $this->hasMany(\App\Models\LoanApproval::class);
    }

    /**
     * Get the workflow state for this loan.
     */
    public function workflowState()
    {
        return $this->hasOne(\App\Models\LoanWorkflowState::class);
    }

    /**
     * Get workflow logs for this loan.
     */
    public function workflowLogs()
    {
        return $this->hasMany(\App\Models\LoanWorkflowLog::class);
    }

    /**
     * Get workflow step assignments for this loan.
     */
    public function workflowAssignments()
    {
        return $this->hasMany(\App\Models\WorkflowStepAssignment::class);
    }

    /**
     * Check if loan is editable (not locked by workflow).
     */
    public function isEditableByWorkflow(): bool
    {
        $state = $this->workflowState;
        return !$state || $state->isEditable();
    }

    /**
     * Get the principal amount (alias for compatibility).
     */
    public function getPrincipalAmountAttribute()
    {
        return $this->principal;
    }

    /**
     * Get the current stage information as an object.
     */
    public function getCurrentStageAttribute()
    {
        return (object) [
            'name' => $this->approval_stage,
            'status' => $this->approval_stage_status,
            'display_name' => $this->getStageDisplayName($this->approval_stage)
        ];
    }

    /**
     * Get a human-readable display name for the approval stage.
     */
    private function getStageDisplayName($stage)
    {
        $stageNames = [
            'cso_review' => 'CSO Review',
            'loan_officer_review' => 'Loan Officer Review',
            'manager_review' => 'Manager Review',
            'gm_approval' => 'GM Approval',
        ];

        return $stageNames[$stage] ?? ucfirst(str_replace('_', ' ', $stage));
    }
}