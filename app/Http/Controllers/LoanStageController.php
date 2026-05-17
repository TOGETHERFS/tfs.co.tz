<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Loan;
use App\Models\LoanApproval;
use App\Services\ApprovalPipelineService;

class LoanStageController extends Controller
{
    /**
     * Approve current stage for a loan (session-auth web route).
     */
    public function approve(Request $request, Loan $loan)
    {
        $user = auth()->user();

        $this->authorize('approveStage', $loan);

        // Ensure canonical role compatibility; force-sync user's role via position if needed
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        if ($loan->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Staged approval applies only on pending loans'], 400);
        }

        if (($loan->approval_stage_status ?? 'pending') !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Current stage already decided'], 400);
        }

        $validated = $request->validate([
            'comment' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            LoanApproval::create([
                'loan_id' => $loan->id,
                'stage' => $loan->approval_stage,
                'status' => 'approved',
                'user_id' => $user->id,
                'comment' => $validated['comment'] ?? null,
                'decided_at' => now(),
            ]);

            $loan->update(['approval_stage_status' => 'approved']);

            $next = $this->nextStage($loan->approval_stage);
            if ($next) {
                $loan->update([
                    'approval_stage' => $next,
                    'approval_stage_status' => 'pending',
                ]);
            } else {
                $loan->update([
                    'status' => 'approved',
                    'approval_stage_status' => 'approved',
                ]);
            }

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
            logger()->error('Loan stage approval failed (web)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'loan_id' => $loan->id,
                'tenant_id' => session('tenant_id'),
                'user_id' => $user->id ?? null,
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to approve stage'], 500);
        }
    }

    /**
     * Reject current stage for a loan (session-auth web route).
     */
    public function reject(Request $request, Loan $loan)
    {
        $user = auth()->user();

        $this->authorize('rejectStage', $loan);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        if ($loan->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Staged rejection applies only on pending loans'], 400);
        }

        if (($loan->approval_stage_status ?? 'pending') !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Current stage already decided'], 400);
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

    private function nextStage(?string $current): ?string
    {
        if (!$current) return null;
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id ?? null;
        if ($tenantId) {
            return ApprovalPipelineService::getNextStage($tenantId, $current);
        }
        return null;
    }

    public function history(Request $request, Loan $loan)
    {
        $history = LoanApproval::where('loan_id', $loan->id)
            ->with('user')
            ->orderBy('decided_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }
}