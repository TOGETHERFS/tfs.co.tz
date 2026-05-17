<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Loan;
use App\Models\LoanApproval;
use App\Services\ApprovalPipelineService;

class LoanApprovalController extends Controller
{
    /**
     * Approve current stage for a loan.
     */
    public function approveStage(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        $user->loadMissing('roles');
        $loan = Loan::find($id);
        if (!$loan) {
            return response()->json(['success' => false, 'message' => 'Loan not found'], 404);
        }

        // Only staged approvals on pending loans
        if ($loan->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Staged approval applies only on pending loans'], 400);
        }

        // Stage must be undecided
        if (($loan->approval_stage_status ?? 'pending') !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Current stage already decided'], 400);
        }

        // Authorization: role column OR any RBAC slug must match stage (admins can act anywhere)
        if (!$this->userCanActOnStage($user, $loan->approval_stage)) {
            return response()->json(['success' => false, 'message' => 'Not allowed to approve this stage'], 403);
        }

        $validated = $request->validate([
            'comment' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            // Log decision
            LoanApproval::create([
                'loan_id' => $loan->id,
                'stage' => $loan->approval_stage,
                'status' => 'approved',
                'user_id' => $user->id,
                'comment' => $validated['comment'] ?? null,
                'decided_at' => now(),
            ]);

            // Move to next stage or finalize
            $next = $this->nextStage($loan->approval_stage);
            if ($next) {
                $loan->update([
                    'approval_stage_status' => 'approved',
                    'approval_stage' => $next,
                    'approval_stage_status' => 'pending',
                ]);
            } else {
                // Final approval
                $loan->update([
                    'approval_stage_status' => 'approved',
                    'status' => 'approved',
                ]);
            }

            // Activity log (if spatie/activitylog installed)
            try {
                activity()->causedBy($user)->performedOn($loan)->withProperties([
                    'stage' => $loan->approval_stage,
                    'action' => 'approve',
                ])->log('Loan stage approved');
            } catch (\Throwable $e) {}

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Stage approved successfully',
                'data' => $loan->fresh(['client', 'product']),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to approve stage'], 500);
        }
    }

    /**
     * Reject current stage for a loan.
     */
    public function rejectStage(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        $user->loadMissing('roles');
        $loan = Loan::find($id);
        if (!$loan) {
            return response()->json(['success' => false, 'message' => 'Loan not found'], 404);
        }

        if ($loan->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Staged rejection applies only on pending loans'], 400);
        }

        if (($loan->approval_stage_status ?? 'pending') !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Current stage already decided'], 400);
        }

        if (!$this->userCanActOnStage($user, $loan->approval_stage)) {
            return response()->json(['success' => false, 'message' => 'Not allowed to reject this stage'], 403);
        }

        $validated = $request->validate([
            'comment' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            LoanApproval::create([
                'loan_id' => $loan->id,
                'stage' => $loan->approval_stage,
                'status' => 'rejected',
                'user_id' => $user->id,
                'comment' => $validated['comment'] ?? null,
                'decided_at' => now(),
            ]);

            // Mark stage rejected; keep overall status pending to avoid enum conflicts
            $loan->update([
                'approval_stage_status' => 'rejected',
            ]);

            try {
                activity()->causedBy($user)->performedOn($loan)->withProperties([
                    'stage' => $loan->approval_stage,
                    'action' => 'reject',
                ])->log('Loan stage rejected');
            } catch (\Throwable $e) {}

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Stage rejected successfully',
                'data' => $loan->fresh(['client', 'product']),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to reject stage'], 500);
        }
    }

    /**
     * Approval history for a loan.
     */
    public function history(Request $request, $id)
    {
        $loan = Loan::find($id);
        if (!$loan) {
            return response()->json(['success' => false, 'message' => 'Loan not found'], 404);
        }

        $history = LoanApproval::where('loan_id', $loan->id)
            ->with('user')
            ->orderBy('decided_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Check if a user can act on the given stage, checking both role column and RBAC slugs.
     */
    private function userCanActOnStage($user, string $stage): bool
    {
        $adminAliases = ['admin', 'administrator'];

        // Check role column
        $roleColumn = strtolower(trim((string) $user->role));
        if (in_array($roleColumn, $adminAliases, true)) return true;

        // Check RBAC slugs
        $rbacSlugs = $user->roles->pluck('slug')->map(fn($s) => strtolower(trim($s)))->toArray();
        if (array_intersect($adminAliases, $rbacSlugs)) return true;

        $map = ApprovalPipelineService::getRoleStageMap();
        $allowedRoles = array_map('strtolower', $map[$stage] ?? []);

        if (in_array($roleColumn, $allowedRoles, true)) return true;
        if (array_intersect($allowedRoles, $rbacSlugs)) return true;

        return false;
    }

    /**
     * Role-to-stage authorization (kept for reference).
     */
    private function canActOnStage(string $role, string $stage): bool
    {
        $adminAliases = ['admin', 'administrator'];
        if (in_array($role, $adminAliases, true)) return true;

        $map = ApprovalPipelineService::getRoleStageMap();
        return in_array($role, $map[$stage] ?? [], true);
    }

    /**
     * Compute next stage in pipeline (dynamic per tenant).
     */
    private function nextStage(?string $current): ?string
    {
        if (!$current) return null;
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id ?? null;
        if ($tenantId) {
            return ApprovalPipelineService::getNextStage($tenantId, $current);
        }
        return null;
    }
}