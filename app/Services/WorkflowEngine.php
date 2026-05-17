<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\User;
use App\Models\WorkflowConfig;
use App\Models\WorkflowStep;
use App\Models\LoanWorkflowState;
use App\Models\LoanWorkflowLog;
use App\Models\WorkflowStepAssignment;
use App\Services\NotificationSmsService;
use App\Services\Accounting\AutomatedAccountingService;
use Illuminate\Support\Facades\DB;
use Exception;

class WorkflowEngine
{
    /**
     * Initialize workflow for a loan
     */
    public function initializeWorkflow(Loan $loan, User $submitter): LoanWorkflowState
    {
        $loanAmount = $loan->principal_amount ?? $loan->amount ?? 0;
        
        // Get appropriate workflow (tenant custom or global fallback)
        $workflow = WorkflowConfig::getWorkflowForTenant($loan->tenant_id, $loanAmount);
        
        if (!$workflow) {
            throw new Exception('No active workflow configuration found for this loan.');
        }

        // Get first applicable step
        $firstStep = $workflow->getFirstStepForAmount($loanAmount);
        
        if (!$firstStep) {
            throw new Exception('No applicable workflow step found for loan amount: ' . $loanAmount);
        }

        return DB::transaction(function () use ($loan, $workflow, $firstStep, $submitter, $loanAmount) {
            // Create workflow state
            $state = LoanWorkflowState::create([
                'loan_id' => $loan->id,
                'workflow_config_id' => $workflow->id,
                'current_step_id' => $firstStep->id,
                'status' => LoanWorkflowState::STATUS_PENDING,
                'completed_steps' => 0,
                'total_steps' => $workflow->activeSteps()
                    ->where(function ($q) use ($loanAmount) {
                        $q->where('min_amount', '<=', $loanAmount)
                          ->where(function ($q2) use ($loanAmount) {
                              $q2->whereNull('max_amount')
                                 ->orWhere('max_amount', '>=', $loanAmount);
                          });
                    })
                    ->count(),
                'is_locked' => false,
                'started_at' => now(),
            ]);

            // Create initial log entry
            LoanWorkflowLog::createLog(
                $loan,
                $firstStep,
                $submitter,
                LoanWorkflowLog::ACTION_SUBMITTED,
                null,
                LoanWorkflowState::STATUS_PENDING,
                'Loan submitted for approval'
            );

            // Create assignment for first step
            $this->createStepAssignment($loan, $firstStep, $submitter);

            return $state;
        });
    }

    /**
     * Process approval action
     */
    public function approve(
        Loan $loan, 
        User $approver, 
        ?string $comment = null,
        bool $isOverride = false,
        ?string $overrideReason = null
    ): array {
        $state = $loan->workflowState;
        
        if (!$state) {
            throw new Exception('Loan has no active workflow.');
        }

        if (!$state->isActive()) {
            throw new Exception('Workflow is not active. Current status: ' . $state->status);
        }

        $currentStep = $state->currentStep;
        
        if (!$currentStep) {
            throw new Exception('No current step found in workflow.');
        }

        // Check authorization
        $this->authorizeAction($loan, $currentStep, $approver, $isOverride);

        // Enforce separation of duties
        $this->enforceSeparationOfDuties($loan, $approver, $isOverride);

        // Check if comment is required
        if ($currentStep->require_comment && empty($comment)) {
            throw new Exception('Comment is required for this approval step.');
        }

        return DB::transaction(function () use ($loan, $state, $currentStep, $approver, $comment, $isOverride, $overrideReason) {
            $previousStatus = $state->status;
            
            // Lock loan after first approval
            if (!$state->is_locked) {
                $state->lock();
            }

            // Mark current assignment as completed
            $this->completeStepAssignment($loan, $currentStep);

            // Get next step
            $nextStep = $currentStep->getNextStep();
            
            // Filter next step by amount
            $loanAmount = $loan->principal_amount ?? $loan->amount ?? 0;
            while ($nextStep && !$nextStep->appliesToAmount($loanAmount)) {
                // Log skipped step
                LoanWorkflowLog::createLog(
                    $loan,
                    $nextStep,
                    $approver,
                    LoanWorkflowLog::ACTION_SKIPPED,
                    $state->status,
                    $state->status,
                    'Step skipped - loan amount outside range'
                );
                $nextStep = $nextStep->getNextStep();
            }

            if ($nextStep) {
                // Advance to next step
                if ($nextStep->isDisbursementStep()) {
                    // All approvals complete, ready for disbursement
                    $newStatus = LoanWorkflowState::STATUS_DISBURSEMENT_READY;
                } else {
                    $newStatus = LoanWorkflowState::STATUS_IN_PROGRESS;
                }

                $state->update([
                    'current_step_id' => $nextStep->id,
                    'completed_steps' => $state->completed_steps + 1,
                    'status' => $newStatus,
                ]);

                // Create assignment for next step
                $this->createStepAssignment($loan, $nextStep, $approver);
            } else {
                // No more steps - workflow complete
                $state->update([
                    'current_step_id' => null,
                    'completed_steps' => $state->completed_steps + 1,
                    'status' => LoanWorkflowState::STATUS_APPROVED,
                    'completed_at' => now(),
                ]);

                // Update loan status
                $loan->update(['status' => 'approved', 'approval_stage' => 'approved']);
            }

            // Create audit log
            LoanWorkflowLog::createLog(
                $loan,
                $currentStep,
                $approver,
                LoanWorkflowLog::ACTION_APPROVED,
                $previousStatus,
                $state->fresh()->status,
                $comment,
                $isOverride,
                $overrideReason
            );

            return [
                'success' => true,
                'message' => 'Loan approved at step: ' . $currentStep->step_name,
                'next_step' => $nextStep?->step_name,
                'workflow_status' => $state->fresh()->status,
                'is_complete' => !$nextStep || $state->fresh()->status === LoanWorkflowState::STATUS_APPROVED,
            ];
        });
    }

    /**
     * Process rejection action
     */
    public function reject(
        Loan $loan, 
        User $rejector, 
        string $comment,
        bool $isOverride = false,
        ?string $overrideReason = null
    ): array {
        $state = $loan->workflowState;
        
        if (!$state) {
            throw new Exception('Loan has no active workflow.');
        }

        if (!$state->isActive()) {
            throw new Exception('Workflow is not active. Current status: ' . $state->status);
        }

        $currentStep = $state->currentStep;
        
        if (!$currentStep) {
            throw new Exception('No current step found in workflow.');
        }

        // Check authorization
        $this->authorizeAction($loan, $currentStep, $rejector, $isOverride);

        if (empty($comment)) {
            throw new Exception('Rejection reason is required.');
        }

        return DB::transaction(function () use ($loan, $state, $currentStep, $rejector, $comment, $isOverride, $overrideReason) {
            $previousStatus = $state->status;
            
            // Update state to rejected
            $state->update([
                'status' => LoanWorkflowState::STATUS_REJECTED,
                'completed_at' => now(),
            ]);

            // Update loan status
            $loan->update(['status' => 'rejected', 'approval_stage' => 'rejected']);

            // Mark assignment as completed
            $this->completeStepAssignment($loan, $currentStep);

            // Create audit log
            LoanWorkflowLog::createLog(
                $loan,
                $currentStep,
                $rejector,
                LoanWorkflowLog::ACTION_REJECTED,
                $previousStatus,
                LoanWorkflowState::STATUS_REJECTED,
                $comment,
                $isOverride,
                $overrideReason
            );

            return [
                'success' => true,
                'message' => 'Loan rejected at step: ' . $currentStep->step_name,
                'workflow_status' => LoanWorkflowState::STATUS_REJECTED,
            ];
        });
    }

    /**
     * Process return for corrections
     */
    public function returnForCorrections(
        Loan $loan, 
        User $returner, 
        string $comment
    ): array {
        $state = $loan->workflowState;
        
        if (!$state) {
            throw new Exception('Loan has no active workflow.');
        }

        $currentStep = $state->currentStep;
        
        if (!$currentStep) {
            throw new Exception('No current step found in workflow.');
        }

        // Check authorization
        $this->authorizeAction($loan, $currentStep, $returner, false);

        return DB::transaction(function () use ($loan, $state, $currentStep, $returner, $comment) {
            $previousStatus = $state->status;
            
            // Unlock loan for edits
            $state->update([
                'is_locked' => false,
                'status' => LoanWorkflowState::STATUS_ON_HOLD,
            ]);

            // Create audit log
            LoanWorkflowLog::createLog(
                $loan,
                $currentStep,
                $returner,
                LoanWorkflowLog::ACTION_RETURNED,
                $previousStatus,
                LoanWorkflowState::STATUS_ON_HOLD,
                $comment
            );

            return [
                'success' => true,
                'message' => 'Loan returned for corrections',
                'workflow_status' => LoanWorkflowState::STATUS_ON_HOLD,
            ];
        });
    }

    /**
     * Process disbursement
     */
    public function disburse(
        Loan $loan, 
        User $disburser, 
        ?string $comment = null,
        ?array $disbursementData = null
    ): array {
        $state = $loan->workflowState;
        
        if (!$state) {
            throw new Exception('Loan has no active workflow.');
        }

        // Check if ready for disbursement
        if (!$state->isReadyForDisbursement() && $state->status !== LoanWorkflowState::STATUS_APPROVED) {
            throw new Exception('Loan is not ready for disbursement. Current status: ' . $state->status);
        }

        $currentStep = $state->currentStep;
        
        // If there's a current step, it should be a disbursement step
        if ($currentStep && !$currentStep->isDisbursementStep()) {
            throw new Exception('Current step is not a disbursement step.');
        }

        // Check authorization for disbursement
        if ($currentStep) {
            $this->authorizeAction($loan, $currentStep, $disburser, false);
        } else {
            // Check if user has disbursement permission
            if (!$disburser->hasPermission('loan.disburse') && !$disburser->isSuperAdmin()) {
                $allowedRoles = ['admin', 'teller', 'accountant', 'cashier'];
                if (!in_array($disburser->role, $allowedRoles)) {
                    throw new Exception('You do not have permission to disburse loans.');
                }
            }
        }

        // Enforce separation of duties for disbursement
        $this->enforceSeparationOfDuties($loan, $disburser, false);

        return DB::transaction(function () use ($loan, $state, $currentStep, $disburser, $comment, $disbursementData) {
            $previousStatus = $state->status;
            
            // Update state to disbursed
            $state->update([
                'status' => LoanWorkflowState::STATUS_DISBURSED,
                'completed_steps' => $state->total_steps,
                'completed_at' => now(),
            ]);

            // Update loan status
            $loan->update([
                'status'       => 'disbursed',
                'disbursed_at' => now()->toDateString(),
                'disbursed_by' => $disburser->id,
            ]);

            // Complete assignment if exists
            if ($currentStep) {
                $this->completeStepAssignment($loan, $currentStep);
            }

            // Create audit log
            $step = $currentStep ?? $state->workflowConfig->getFinalStep();
            if ($step) {
                LoanWorkflowLog::createLog(
                    $loan,
                    $step,
                    $disburser,
                    LoanWorkflowLog::ACTION_DISBURSED,
                    $previousStatus,
                    LoanWorkflowState::STATUS_DISBURSED,
                    $comment,
                    false,
                    null,
                    $disbursementData
                );
            }

            $result = [
                'success' => true,
                'message' => 'Loan disbursed successfully',
                'workflow_status' => LoanWorkflowState::STATUS_DISBURSED,
                'disbursement_date' => now()->toIso8601String(),
            ];

            // Send disbursement SMS to borrower (outside transaction is fine — non-critical)
            try {
                app(NotificationSmsService::class)->sendLoanDisbursedSms($loan->fresh());
            } catch (\Throwable $e) {
                \Log::warning('Disbursement SMS failed silently', ['loan_id' => $loan->id, 'error' => $e->getMessage()]);
            }

            // Record accounting journal entry for disbursement (non-critical)
            try {
                app(AutomatedAccountingService::class)->recordLoanDisbursement($loan->fresh());
            } catch (\Throwable $e) {
                \Log::warning('Accounting entry for disbursement failed silently', ['loan_id' => $loan->id, 'error' => $e->getMessage()]);
            }

            return $result;
        });
    }

    /**
     * Authorize user action on step
     */
    protected function authorizeAction(Loan $loan, WorkflowStep $step, User $user, bool $isOverride): void
    {
        // Super admin can always act
        if ($user->isSuperAdmin()) {
            return;
        }

        // Check if override is allowed
        if ($isOverride) {
            $workflow = $step->workflowConfig;
            if (!$workflow->allow_separation_override) {
                throw new Exception('Override is not allowed for this workflow.');
            }
        }

        // Check if user can act on this step
        if (!$step->canUserAct($user)) {
            throw new Exception(
                'You do not have the required role to act on this step. Required role: ' . 
                ($step->role?->name ?? 'Unknown')
            );
        }

        // Check tenant
        if ($user->tenant_id !== $loan->tenant_id) {
            throw new Exception('You cannot act on loans from other tenants.');
        }
    }

    /**
     * Enforce separation of duties
     */
    protected function enforceSeparationOfDuties(Loan $loan, User $user, bool $isOverride): void
    {
        // Skip for super admin
        if ($user->isSuperAdmin()) {
            return;
        }

        // Check if override is requested
        if ($isOverride) {
            $workflow = $loan->workflowState?->workflowConfig;
            if ($workflow && $workflow->allow_separation_override) {
                return;
            }
        }

        // Check if user has already acted on this loan
        $previousAction = LoanWorkflowLog::where('loan_id', $loan->id)
            ->where('user_id', $user->id)
            ->whereIn('action', [
                LoanWorkflowLog::ACTION_APPROVED,
                LoanWorkflowLog::ACTION_REJECTED,
            ])
            ->exists();

        if ($previousAction) {
            throw new Exception(
                'Separation of duties violation: You have already acted on this loan. ' .
                'Another user must complete this step.'
            );
        }

        // Check if user is the loan applicant/creator
        if ($loan->user_id === $user->id || $loan->created_by === $user->id) {
            throw new Exception(
                'Separation of duties violation: Loan creator cannot approve their own loan.'
            );
        }
    }

    /**
     * Create step assignment
     */
    protected function createStepAssignment(Loan $loan, WorkflowStep $step, User $assignedBy): WorkflowStepAssignment
    {
        return WorkflowStepAssignment::create([
            'loan_id' => $loan->id,
            'workflow_step_id' => $step->id,
            'assigned_user_id' => null, // Can be assigned to specific user or role-based
            'assigned_by_id' => $assignedBy->id,
            'status' => WorkflowStepAssignment::STATUS_PENDING,
            'assigned_at' => now(),
            'due_at' => $step->timeout_hours ? now()->addHours($step->timeout_hours) : null,
        ]);
    }

    /**
     * Complete step assignment
     */
    protected function completeStepAssignment(Loan $loan, WorkflowStep $step): void
    {
        WorkflowStepAssignment::where('loan_id', $loan->id)
            ->where('workflow_step_id', $step->id)
            ->where('status', WorkflowStepAssignment::STATUS_PENDING)
            ->update([
                'status' => WorkflowStepAssignment::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
    }

    /**
     * Get workflow status for a loan
     */
    public function getWorkflowStatus(Loan $loan): array
    {
        $state = $loan->workflowState;
        
        if (!$state) {
            return [
                'has_workflow' => false,
                'message' => 'No workflow initialized for this loan.',
            ];
        }

        $state->load(['currentStep.role', 'workflowConfig']);

        return [
            'has_workflow' => true,
            'status' => $state->status,
            'current_step' => $state->currentStep ? [
                'id' => $state->currentStep->id,
                'name' => $state->currentStep->step_name,
                'action_type' => $state->currentStep->action_type,
                'role' => $state->currentStep->role?->name,
                'require_comment' => $state->currentStep->require_comment,
            ] : null,
            'progress' => [
                'completed' => $state->completed_steps,
                'total' => $state->total_steps,
                'percentage' => $state->getProgressPercentage(),
            ],
            'is_locked' => $state->is_locked,
            'is_editable' => $state->isEditable(),
            'can_disburse' => $state->isReadyForDisbursement(),
            'started_at' => $state->started_at?->toIso8601String(),
            'completed_at' => $state->completed_at?->toIso8601String(),
        ];
    }

    /**
     * Get audit trail for a loan
     */
    public function getAuditTrail(Loan $loan): array
    {
        return LoanWorkflowLog::where('loan_id', $loan->id)
            ->with(['user:id,name,email', 'workflowStep:id,step_name,action_type'])
            ->orderBy('action_at', 'desc')
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'action_label' => $log->action_label,
                    'step_name' => $log->workflowStep?->step_name,
                    'user' => $log->user?->name,
                    'comment' => $log->comment,
                    'previous_status' => $log->previous_status,
                    'new_status' => $log->new_status,
                    'is_override' => $log->is_override,
                    'override_reason' => $log->override_reason,
                    'action_at' => $log->action_at->toIso8601String(),
                ];
            })
            ->toArray();
    }

    /**
     * Resubmit loan after corrections
     */
    public function resubmit(Loan $loan, User $submitter): array
    {
        $state = $loan->workflowState;
        
        if (!$state) {
            throw new Exception('Loan has no active workflow.');
        }

        if ($state->status !== LoanWorkflowState::STATUS_ON_HOLD) {
            throw new Exception('Loan is not on hold. Cannot resubmit.');
        }

        return DB::transaction(function () use ($loan, $state, $submitter) {
            $currentStep = $state->currentStep;
            
            // Lock loan again
            $state->update([
                'is_locked' => true,
                'status' => LoanWorkflowState::STATUS_IN_PROGRESS,
            ]);

            // Create audit log
            if ($currentStep) {
                LoanWorkflowLog::createLog(
                    $loan,
                    $currentStep,
                    $submitter,
                    LoanWorkflowLog::ACTION_SUBMITTED,
                    LoanWorkflowState::STATUS_ON_HOLD,
                    LoanWorkflowState::STATUS_IN_PROGRESS,
                    'Loan resubmitted after corrections'
                );
            }

            return [
                'success' => true,
                'message' => 'Loan resubmitted for approval',
                'workflow_status' => LoanWorkflowState::STATUS_IN_PROGRESS,
            ];
        });
    }
}
