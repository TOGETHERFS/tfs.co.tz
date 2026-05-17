<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStep extends Model
{
    protected $fillable = [
        'workflow_config_id',
        'role_id',
        'step_order',
        'action_type',
        'min_amount',
        'max_amount',
        'step_name',
        'description',
        'is_active',
        'require_comment',
        'require_documents',
        'required_document_types',
        'can_skip',
        'allow_delegation',
        'timeout_hours',
        'escalation_hours',
        'escalation_role_id',
        'notify_on_pending',
        'notify_on_complete',
        'conditions',
        'auto_actions',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'require_comment' => 'boolean',
        'require_documents' => 'boolean',
        'required_document_types' => 'array',
        'can_skip' => 'boolean',
        'allow_delegation' => 'boolean',
        'timeout_hours' => 'integer',
        'escalation_hours' => 'integer',
        'notify_on_pending' => 'boolean',
        'notify_on_complete' => 'boolean',
        'conditions' => 'array',
        'auto_actions' => 'array',
    ];

    public function workflowConfig(): BelongsTo
    {
        return $this->belongsTo(WorkflowConfig::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function workflowLogs(): HasMany
    {
        return $this->hasMany(LoanWorkflowLog::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(WorkflowStepAssignment::class);
    }

    /**
     * Check if this step applies to a given loan amount
     */
    public function appliesToAmount(float $amount): bool
    {
        $meetsMin = $this->min_amount <= $amount;
        $meetsMax = $this->max_amount === null || $this->max_amount >= $amount;
        return $meetsMin && $meetsMax;
    }

    /**
     * Check if this is an approval step
     */
    public function isApprovalStep(): bool
    {
        return $this->action_type === 'APPROVAL';
    }

    /**
     * Check if this is a disbursement step
     */
    public function isDisbursementStep(): bool
    {
        return $this->action_type === 'DISBURSEMENT';
    }

    /**
     * Get the next step in the workflow
     */
    public function getNextStep(): ?self
    {
        return static::where('workflow_config_id', $this->workflow_config_id)
            ->where('is_active', true)
            ->where('step_order', '>', $this->step_order)
            ->orderBy('step_order')
            ->first();
    }

    /**
     * Get the previous step in the workflow
     */
    public function getPreviousStep(): ?self
    {
        return static::where('workflow_config_id', $this->workflow_config_id)
            ->where('is_active', true)
            ->where('step_order', '<', $this->step_order)
            ->orderByDesc('step_order')
            ->first();
    }

    /**
     * Check if this is the first step
     */
    public function isFirstStep(): bool
    {
        return $this->getPreviousStep() === null;
    }

    /**
     * Check if this is the last step
     */
    public function isLastStep(): bool
    {
        return $this->getNextStep() === null;
    }

    /**
     * Check if user with given role can act on this step
     */
    public function canUserAct(User $user): bool
    {
        // Super admin can always act
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check if user has the required role
        return $user->roles()->where('roles.id', $this->role_id)->exists() 
            || $user->role === $this->role?->slug
            || $user->role === $this->role?->name;
    }
}
