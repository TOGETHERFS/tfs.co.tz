<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApprovalPipelineService
{
    /**
     * Full ordered list of all possible approval stages and their required roles.
     */
    private static array $allStages = [
        'cso_review'           => ['officer', 'credit_officer'],
        'loan_officer_review'  => ['loan_officer', 'credit_officer'],
        'manager_review'       => ['manager'],
        'gm_approval'          => ['gm'],
    ];

    /**
     * Get the approval stages that apply for a given tenant,
     * based on which roles actually have staff assigned.
     *
     * @return string[] Ordered stage slugs the tenant should use.
     */
    public static function getStagesForTenant(int $tenantId): array
    {
        // Get all distinct role values for users in this tenant
        $staffRoles = User::where('tenant_id', $tenantId)
            ->whereNotNull('role')
            ->pluck('role')
            ->map(fn($r) => strtolower(trim($r)))
            ->unique()
            ->toArray();

        // Also check RBAC pivot roles (slug) for this tenant
        $rbacSlugs = DB::table('user_role')
            ->join('roles', 'roles.id', '=', 'user_role.role_id')
            ->where('user_role.tenant_id', $tenantId)
            ->pluck('roles.slug')
            ->map(fn($r) => strtolower(trim($r)))
            ->unique()
            ->toArray();

        $allRoles = array_unique(array_merge($staffRoles, $rbacSlugs));

        $stages = [];
        foreach (self::$allStages as $stage => $requiredRoles) {
            // Include this stage if the tenant has at least one user with a matching role
            foreach ($requiredRoles as $role) {
                if (in_array($role, $allRoles, true)) {
                    $stages[] = $stage;
                    break;
                }
            }
        }

        // If no stages matched at all (e.g. only admin staff), return empty
        // so the loan can be approved directly by admin
        return $stages;
    }

    /**
     * Get the first approval stage for a tenant.
     * Returns null if no stages apply (admin can approve directly).
     */
    public static function getFirstStage(int $tenantId): ?string
    {
        $stages = self::getStagesForTenant($tenantId);
        return $stages[0] ?? null;
    }

    /**
     * Get the next stage after the given one for a tenant.
     * Skips stages that don't apply to this tenant.
     * Returns null if there is no next stage.
     */
    public static function getNextStage(int $tenantId, ?string $currentStage): ?string
    {
        if (!$currentStage) return null;

        $stages = self::getStagesForTenant($tenantId);
        $idx = array_search($currentStage, $stages, true);

        if ($idx === false) return null;

        return $stages[$idx + 1] ?? null;
    }

    /**
     * Get the full ordered list of all possible stages (for display/reference).
     */
    public static function getAllStages(): array
    {
        return array_keys(self::$allStages);
    }

    /**
     * Get the role-to-stage mapping.
     */
    public static function getRoleStageMap(): array
    {
        return self::$allStages;
    }

    /**
     * Get tenant-specific role-to-stage mapping based on actual staff roles.
     * Returns which roles can approve each stage for this tenant.
     */
    public static function getTenantRoleStageMap(int $tenantId): array
    {
        // Get all staff roles for this tenant
        $staffRoles = \App\Models\User::where('tenant_id', $tenantId)
            ->whereNotNull('role')
            ->pluck('role')
            ->map(fn($r) => strtolower(trim($r)))
            ->unique()
            ->toArray();

        // Get RBAC role slugs
        $rbacSlugs = \App\Models\User::where('tenant_id', $tenantId)
            ->with('roles')
            ->get()
            ->pluck('roles')
            ->flatten()
            ->pluck('slug')
            ->map(fn($r) => strtolower(trim($r)))
            ->unique()
            ->toArray();

        $allRoles = array_unique(array_merge($staffRoles, $rbacSlugs));

        // Build tenant-specific map: stage => [actual roles that can approve]
        $tenantMap = [];
        foreach (self::$allStages as $stage => $requiredRoles) {
            $matchingRoles = [];
            foreach ($requiredRoles as $role) {
                if (in_array($role, $allRoles, true)) {
                    $matchingRoles[] = $role;
                }
            }
            // Only include stages that have at least one matching role
            if (!empty($matchingRoles)) {
                $tenantMap[$stage] = $matchingRoles;
            }
        }

        return $tenantMap;
    }
}
