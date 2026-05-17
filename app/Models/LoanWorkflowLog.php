<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanWorkflowLog extends Model
{
    protected $fillable = [
        'loan_id',
        'workflow_step_id',
        'user_id',
        'tenant_id',
        'action',
        'previous_status',
        'new_status',
        'comment',
        'metadata',
        'loan_amount',
        'is_override',
        'override_reason',
        'action_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'loan_amount' => 'decimal:2',
        'is_override' => 'boolean',
        'action_at' => 'datetime',
    ];

    const ACTION_SUBMITTED = 'SUBMITTED';
    const ACTION_APPROVED = 'APPROVED';
    const ACTION_REJECTED = 'REJECTED';
    const ACTION_RETURNED = 'RETURNED';
    const ACTION_ESCALATED = 'ESCALATED';
    const ACTION_DISBURSED = 'DISBURSED';
    const ACTION_COMMENTED = 'COMMENTED';
    const ACTION_SKIPPED = 'SKIPPED';
    const ACTION_OVERRIDDEN = 'OVERRIDDEN';

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Create an audit log entry
     */
    public static function createLog(
        Loan $loan,
        WorkflowStep $step,
        User $user,
        string $action,
        ?string $previousStatus,
        string $newStatus,
        ?string $comment = null,
        bool $isOverride = false,
        ?string $overrideReason = null,
        ?array $metadata = null
    ): self {
        return static::create([
            'loan_id' => $loan->id,
            'workflow_step_id' => $step->id,
            'user_id' => $user->id,
            'tenant_id' => $loan->tenant_id,
            'action' => $action,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'comment' => $comment,
            'metadata' => array_merge($metadata ?? [], [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp' => now()->toIso8601String(),
            ]),
            'loan_amount' => $loan->principal_amount ?? $loan->amount ?? 0,
            'is_override' => $isOverride,
            'override_reason' => $overrideReason,
            'action_at' => now(),
        ]);
    }

    /**
     * Get formatted action label
     */
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            self::ACTION_SUBMITTED => 'Submitted',
            self::ACTION_APPROVED => 'Approved',
            self::ACTION_REJECTED => 'Rejected',
            self::ACTION_RETURNED => 'Returned for Corrections',
            self::ACTION_ESCALATED => 'Escalated',
            self::ACTION_DISBURSED => 'Disbursed',
            self::ACTION_COMMENTED => 'Commented',
            self::ACTION_SKIPPED => 'Skipped',
            self::ACTION_OVERRIDDEN => 'Overridden',
            default => $this->action,
        };
    }
}
