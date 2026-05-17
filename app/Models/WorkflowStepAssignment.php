<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStepAssignment extends Model
{
    protected $fillable = [
        'loan_id',
        'workflow_step_id',
        'assigned_user_id',
        'assigned_by_id',
        'status',
        'assigned_at',
        'due_at',
        'completed_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'PENDING';
    const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_SKIPPED = 'SKIPPED';
    const STATUS_REASSIGNED = 'REASSIGNED';

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }

    /**
     * Check if assignment is overdue
     */
    public function isOverdue(): bool
    {
        return $this->due_at && $this->due_at->isPast() && $this->status === self::STATUS_PENDING;
    }

    /**
     * Mark as completed
     */
    public function markCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Reassign to another user
     */
    public function reassignTo(User $newUser, User $assignedBy): void
    {
        $this->update([
            'status' => self::STATUS_REASSIGNED,
        ]);

        // Create new assignment
        static::create([
            'loan_id' => $this->loan_id,
            'workflow_step_id' => $this->workflow_step_id,
            'assigned_user_id' => $newUser->id,
            'assigned_by_id' => $assignedBy->id,
            'status' => self::STATUS_PENDING,
            'assigned_at' => now(),
            'due_at' => $this->workflowStep?->timeout_hours 
                ? now()->addHours($this->workflowStep->timeout_hours) 
                : null,
        ]);
    }
}
