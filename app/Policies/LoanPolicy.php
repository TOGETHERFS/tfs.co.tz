<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;
use App\Services\ApprovalPipelineService;

class LoanPolicy
{
    public function view(User $user, Loan $loan): bool
    {
        return $user->tenant_id === $loan->tenant_id;
    }

    public function update(User $user, Loan $loan): bool
    {
        return $user->tenant_id === $loan->tenant_id && $user->hasPermission('loan.update');
    }

    /**
     * Determine if user can disburse an approved loan.
     * Admins and any user with loan.disburse permission or assigned disburse roles can disburse.
     */
    public function disburse(User $user, Loan $loan): bool
    {
        if ($user->tenant_id !== $loan->tenant_id) {
            return false;
        }

        // Loan must be approved to disburse
        if ($loan->status !== 'approved') {
            return false;
        }

        // Admins/administrators can always disburse
        if ($user->isAdmin()) {
            return true;
        }

        // Check if user has explicit disburse permission
        if ($user->hasPermission('loan.disburse')) {
            return true;
        }

        // Default: allow teller, accountant, cashier, loan_officer by role
        $defaultDisburseRoles = ['teller', 'accountant', 'cashier', 'loan_officer', 'manager', 'gm'];
        foreach ($defaultDisburseRoles as $roleSlug) {
            if ($user->hasRole($roleSlug) || $user->role === $roleSlug) {
                return true;
            }
        }

        return false;
    }

    public function approveStage(User $user, Loan $loan): bool
    {
        if ($user->tenant_id !== $loan->tenant_id) {
            return false;
        }

        if ($loan->status !== 'pending' || ($loan->approval_stage_status ?? 'pending') !== 'pending') {
            return false;
        }

        // Admins can always approve any stage
        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->hasPermission('loan.stage.decide')) {
            return false;
        }

        return $this->roleMatchesStage($user, $loan->approval_stage);
    }

    public function rejectStage(User $user, Loan $loan): bool
    {
        if ($user->tenant_id !== $loan->tenant_id) {
            return false;
        }

        if ($loan->status !== 'pending' || ($loan->approval_stage_status ?? 'pending') !== 'pending') {
            return false;
        }

        // Admins can always reject any stage
        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->hasPermission('loan.stage.decide')) {
            return false;
        }

        return $this->roleMatchesStage($user, $loan->approval_stage);
    }

    private function roleMatchesStage(User $user, ?string $stage): bool
    {
        if (!$stage) return false;

        // Admins can act on any stage
        if ($user->isAdmin()) return true;

        // Use the centralized role-to-stage map
        $map = ApprovalPipelineService::getRoleStageMap();

        foreach ($map[$stage] ?? [] as $roleSlug) {
            if ($user->hasRole($roleSlug) || $user->role === $roleSlug) {
                return true;
            }
        }

        return false;
    }
}