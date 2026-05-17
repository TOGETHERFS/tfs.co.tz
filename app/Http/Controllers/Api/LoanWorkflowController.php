<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\LoanWorkflowLog;
use App\Services\WorkflowEngine;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class LoanWorkflowController extends Controller
{
    protected WorkflowEngine $workflowEngine;

    public function __construct(WorkflowEngine $workflowEngine)
    {
        $this->workflowEngine = $workflowEngine;
    }

    /**
     * Initialize workflow for a loan
     */
    public function initializeWorkflow(Request $request, Loan $loan): JsonResponse
    {
        try {
            $this->authorizeLoanAccess($loan);

            // Check if workflow already exists
            if ($loan->workflowState) {
                return response()->json([
                    'success' => false,
                    'message' => 'Workflow already initialized for this loan.',
                    'data' => $this->workflowEngine->getWorkflowStatus($loan),
                ], 422);
            }

            $state = $this->workflowEngine->initializeWorkflow($loan, auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'Workflow initialized successfully.',
                'data' => $this->workflowEngine->getWorkflowStatus($loan->fresh()),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get workflow status for a loan
     */
    public function getStatus(Loan $loan): JsonResponse
    {
        $this->authorizeLoanAccess($loan);

        return response()->json([
            'success' => true,
            'data' => $this->workflowEngine->getWorkflowStatus($loan),
        ]);
    }

    /**
     * Approve current workflow step
     */
    public function approve(Request $request, Loan $loan): JsonResponse
    {
        $this->authorizeLoanAccess($loan);

        $validated = $request->validate([
            'comment' => 'nullable|string|max:1000',
            'is_override' => 'boolean',
            'override_reason' => 'required_if:is_override,true|nullable|string|max:500',
        ]);

        try {
            $result = $this->workflowEngine->approve(
                $loan,
                auth()->user(),
                $validated['comment'] ?? null,
                $validated['is_override'] ?? false,
                $validated['override_reason'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'workflow_status' => $result['workflow_status'],
                    'next_step' => $result['next_step'] ?? null,
                    'is_complete' => $result['is_complete'] ?? false,
                    'current_status' => $this->workflowEngine->getWorkflowStatus($loan->fresh()),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reject current workflow step
     */
    public function reject(Request $request, Loan $loan): JsonResponse
    {
        $this->authorizeLoanAccess($loan);

        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
            'is_override' => 'boolean',
            'override_reason' => 'required_if:is_override,true|nullable|string|max:500',
        ]);

        try {
            $result = $this->workflowEngine->reject(
                $loan,
                auth()->user(),
                $validated['comment'],
                $validated['is_override'] ?? false,
                $validated['override_reason'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'workflow_status' => $result['workflow_status'],
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Return loan for corrections
     */
    public function returnForCorrections(Request $request, Loan $loan): JsonResponse
    {
        $this->authorizeLoanAccess($loan);

        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        try {
            $result = $this->workflowEngine->returnForCorrections(
                $loan,
                auth()->user(),
                $validated['comment']
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'workflow_status' => $result['workflow_status'],
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Resubmit loan after corrections
     */
    public function resubmit(Request $request, Loan $loan): JsonResponse
    {
        $this->authorizeLoanAccess($loan);

        try {
            $result = $this->workflowEngine->resubmit($loan, auth()->user());

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'workflow_status' => $result['workflow_status'],
                    'current_status' => $this->workflowEngine->getWorkflowStatus($loan->fresh()),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Disburse loan
     */
    public function disburse(Request $request, Loan $loan): JsonResponse
    {
        $this->authorizeLoanAccess($loan);

        $validated = $request->validate([
            'comment' => 'nullable|string|max:1000',
            'disbursement_method' => 'nullable|string|max:50',
            'disbursement_reference' => 'nullable|string|max:100',
            'disbursement_account' => 'nullable|string|max:100',
        ]);

        try {
            $disbursementData = [
                'method' => $validated['disbursement_method'] ?? null,
                'reference' => $validated['disbursement_reference'] ?? null,
                'account' => $validated['disbursement_account'] ?? null,
            ];

            $result = $this->workflowEngine->disburse(
                $loan,
                auth()->user(),
                $validated['comment'] ?? null,
                $disbursementData
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'workflow_status' => $result['workflow_status'],
                    'disbursement_date' => $result['disbursement_date'],
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get audit trail for loan workflow
     */
    public function getAuditTrail(Loan $loan): JsonResponse
    {
        $this->authorizeLoanAccess($loan);

        return response()->json([
            'success' => true,
            'data' => $this->workflowEngine->getAuditTrail($loan),
        ]);
    }

    /**
     * Get pending approvals for current user
     */
    public function getPendingApprovals(Request $request): JsonResponse
    {
        $user = auth()->user();
        $tenantId = session('tenant_id') ?? $user->tenant_id;

        // Get user's role IDs
        $userRoleIds = $user->roles()->pluck('roles.id')->toArray();

        // Get loans pending approval for user's roles
        $pendingLoans = Loan::where('tenant_id', $tenantId)
            ->whereHas('workflowState', function ($q) use ($userRoleIds, $user) {
                $q->whereIn('status', ['PENDING', 'IN_PROGRESS', 'DISBURSEMENT_READY'])
                  ->whereHas('currentStep', function ($sq) use ($userRoleIds, $user) {
                      $sq->whereIn('role_id', $userRoleIds)
                         ->orWhere(function ($q) use ($user) {
                             // Super admin sees all
                             if ($user->isSuperAdmin()) {
                                 $q->whereNotNull('id');
                             }
                         });
                  });
            })
            ->with([
                'workflowState.currentStep.role:id,name',
                'client:id,first_name,last_name',
                'loanProduct:id,name',
            ])
            ->select('id', 'client_id', 'loan_product_id', 'principal_amount', 'status', 'created_at')
            ->orderBy('created_at')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $pendingLoans,
        ]);
    }

    /**
     * Get workflow statistics for dashboard
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;

        $stats = [
            'pending' => Loan::where('tenant_id', $tenantId)
                ->whereHas('workflowState', fn($q) => $q->where('status', 'PENDING'))
                ->count(),
            'in_progress' => Loan::where('tenant_id', $tenantId)
                ->whereHas('workflowState', fn($q) => $q->where('status', 'IN_PROGRESS'))
                ->count(),
            'ready_for_disbursement' => Loan::where('tenant_id', $tenantId)
                ->whereHas('workflowState', fn($q) => $q->where('status', 'DISBURSEMENT_READY'))
                ->count(),
            'disbursed_today' => Loan::where('tenant_id', $tenantId)
                ->whereHas('workflowState', fn($q) => $q->where('status', 'DISBURSED')
                    ->whereDate('completed_at', today()))
                ->count(),
            'rejected_today' => Loan::where('tenant_id', $tenantId)
                ->whereHas('workflowState', fn($q) => $q->where('status', 'REJECTED')
                    ->whereDate('completed_at', today()))
                ->count(),
            'average_approval_time_hours' => $this->calculateAverageApprovalTime($tenantId),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Calculate average approval time in hours
     */
    protected function calculateAverageApprovalTime(int $tenantId): ?float
    {
        $avgSeconds = \DB::table('loan_workflow_states')
            ->join('loans', 'loans.id', '=', 'loan_workflow_states.loan_id')
            ->where('loans.tenant_id', $tenantId)
            ->whereIn('loan_workflow_states.status', ['APPROVED', 'DISBURSED'])
            ->whereNotNull('loan_workflow_states.completed_at')
            ->whereNotNull('loan_workflow_states.started_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, loan_workflow_states.started_at, loan_workflow_states.completed_at)) as avg_time')
            ->value('avg_time');

        return $avgSeconds ? round($avgSeconds / 3600, 1) : null;
    }

    /**
     * Check if loan can be edited
     */
    public function canEdit(Loan $loan): JsonResponse
    {
        $this->authorizeLoanAccess($loan);

        $state = $loan->workflowState;
        
        return response()->json([
            'success' => true,
            'can_edit' => $state ? $state->isEditable() : true,
            'reason' => $state && !$state->isEditable() 
                ? 'Loan is locked after first approval.' 
                : null,
        ]);
    }

    /**
     * Authorize access to loan
     */
    protected function authorizeLoanAccess(Loan $loan): void
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;
        
        if (!auth()->user()->isSuperAdmin() && $loan->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized access to loan.');
        }
    }
}
