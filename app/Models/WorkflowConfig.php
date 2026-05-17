<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowConfig extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'template_id',
        'name',
        'description',
        'workflow_type',
        'is_global',
        'is_active',
        'use_custom_workflow',
        'allow_separation_override',
        'min_loan_amount',
        'max_loan_amount',
    ];

    protected $casts = [
        'is_global' => 'boolean',
        'is_active' => 'boolean',
        'use_custom_workflow' => 'boolean',
        'allow_separation_override' => 'boolean',
        'min_loan_amount' => 'decimal:2',
        'max_loan_amount' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class, 'template_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('step_order');
    }

    public function activeSteps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class)
            ->where('is_active', true)
            ->orderBy('step_order');
    }

    public function approvalSteps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class)
            ->where('is_active', true)
            ->where('action_type', 'APPROVAL')
            ->orderBy('step_order');
    }

    public function disbursementSteps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class)
            ->where('is_active', true)
            ->where('action_type', 'DISBURSEMENT')
            ->orderBy('step_order');
    }

    public function loanWorkflowStates(): HasMany
    {
        return $this->hasMany(LoanWorkflowState::class);
    }

    /**
     * Get the global (system default) workflow
     */
    public static function getGlobalWorkflow(): ?self
    {
        return static::where('is_global', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get active workflow for a tenant (custom or fallback to global)
     */
    public static function getWorkflowForTenant(int $tenantId, ?float $loanAmount = null): ?self
    {
        // First check if tenant has custom workflow enabled
        $tenantConfig = static::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('use_custom_workflow', true)
            ->first();

        if ($tenantConfig) {
            // Check amount range if specified
            if ($loanAmount !== null) {
                $meetsMin = $tenantConfig->min_loan_amount <= $loanAmount;
                $meetsMax = $tenantConfig->max_loan_amount === null || $tenantConfig->max_loan_amount >= $loanAmount;
                
                if ($meetsMin && $meetsMax) {
                    return $tenantConfig;
                }
            } else {
                return $tenantConfig;
            }
        }

        // Fallback to global workflow
        return static::getGlobalWorkflow();
    }

    /**
     * Get first step for a given loan amount
     */
    public function getFirstStepForAmount(float $amount): ?WorkflowStep
    {
        return $this->activeSteps()
            ->where('min_amount', '<=', $amount)
            ->where(function ($q) use ($amount) {
                $q->whereNull('max_amount')
                  ->orWhere('max_amount', '>=', $amount);
            })
            ->orderBy('step_order')
            ->first();
    }

    /**
     * Get total number of active steps
     */
    public function getTotalStepsAttribute(): int
    {
        return $this->activeSteps()->count();
    }

    /**
     * Check if workflow has disbursement step
     */
    public function hasDisbursementStep(): bool
    {
        return $this->disbursementSteps()->exists();
    }

    /**
     * Get final step (should be disbursement)
     */
    public function getFinalStep(): ?WorkflowStep
    {
        return $this->activeSteps()->orderByDesc('step_order')->first();
    }
}
