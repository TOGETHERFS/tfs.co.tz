<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanWorkflowState extends Model
{
    protected $fillable = [
        'loan_id',
        'workflow_config_id',
        'current_step_id',
        'status',
        'completed_steps',
        'total_steps',
        'is_locked',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'completed_steps' => 'integer',
        'total_steps' => 'integer',
        'is_locked' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'PENDING';
    const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    const STATUS_APPROVED = 'APPROVED';
    const STATUS_DISBURSEMENT_READY = 'DISBURSEMENT_READY';
    const STATUS_DISBURSED = 'DISBURSED';
    const STATUS_REJECTED = 'REJECTED';
    const STATUS_CANCELLED = 'CANCELLED';
    const STATUS_ON_HOLD = 'ON_HOLD';

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function workflowConfig(): BelongsTo
    {
        return $this->belongsTo(WorkflowConfig::class);
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LoanWorkflowLog::class, 'loan_id', 'loan_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WorkflowStepAssignment::class, 'loan_id', 'loan_id');
    }

    /**
     * Check if loan is editable (not locked)
     */
    public function isEditable(): bool
    {
        return !$this->is_locked && $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if workflow is complete (all approvals done)
     */
    public function isApprovalComplete(): bool
    {
        return in_array($this->status, [
            self::STATUS_APPROVED,
            self::STATUS_DISBURSEMENT_READY,
            self::STATUS_DISBURSED,
        ]);
    }

    /**
     * Check if ready for disbursement
     */
    public function isReadyForDisbursement(): bool
    {
        return $this->status === self::STATUS_DISBURSEMENT_READY;
    }

    /**
     * Check if already disbursed
     */
    public function isDisbursed(): bool
    {
        return $this->status === self::STATUS_DISBURSED;
    }

    /**
     * Check if rejected
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if workflow is active (can be acted upon)
     */
    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_IN_PROGRESS,
            self::STATUS_APPROVED,
            self::STATUS_DISBURSEMENT_READY,
        ]);
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage(): float
    {
        if ($this->total_steps === 0) {
            return 0;
        }
        return round(($this->completed_steps / $this->total_steps) * 100, 1);
    }

    /**
     * Lock the loan for edits
     */
    public function lock(): void
    {
        $this->update(['is_locked' => true]);
    }

    /**
     * Advance to next step
     */
    public function advanceToNextStep(): ?WorkflowStep
    {
        $nextStep = $this->currentStep?->getNextStep();
        
        if ($nextStep) {
            $this->update([
                'current_step_id' => $nextStep->id,
                'completed_steps' => $this->completed_steps + 1,
            ]);
        }
        
        return $nextStep;
    }
}
