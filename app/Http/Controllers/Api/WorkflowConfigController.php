<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkflowConfig;
use App\Models\WorkflowStep;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WorkflowConfigController extends Controller
{
    /**
     * List all workflow configurations for tenant
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;
        
        $workflows = WorkflowConfig::where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)
                  ->orWhere('is_global', true);
            })
            ->with(['steps.role:id,name,slug'])
            ->withCount('steps')
            ->orderBy('is_global', 'desc')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $workflows,
        ]);
    }

    /**
     * Get global (system default) workflow
     */
    public function getGlobal(): JsonResponse
    {
        $workflow = WorkflowConfig::getGlobalWorkflow();
        
        if (!$workflow) {
            return response()->json([
                'success' => false,
                'message' => 'No global workflow configured.',
            ], 404);
        }

        $workflow->load(['steps.role:id,name,slug']);

        return response()->json([
            'success' => true,
            'data' => $workflow,
        ]);
    }

    /**
     * Get active workflow for current tenant
     */
    public function getActive(Request $request): JsonResponse
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;
        $loanAmount = $request->get('loan_amount');
        
        $workflow = WorkflowConfig::getWorkflowForTenant($tenantId, $loanAmount);
        
        if (!$workflow) {
            return response()->json([
                'success' => false,
                'message' => 'No active workflow found.',
            ], 404);
        }

        $workflow->load(['steps.role:id,name,slug']);

        return response()->json([
            'success' => true,
            'data' => $workflow,
            'is_custom' => !$workflow->is_global,
        ]);
    }

    /**
     * Show specific workflow configuration
     */
    public function show(WorkflowConfig $workflowConfig): JsonResponse
    {
        $this->authorizeAccess($workflowConfig);
        
        $workflowConfig->load(['steps.role:id,name,slug', 'tenant:id,name']);

        return response()->json([
            'success' => true,
            'data' => $workflowConfig,
        ]);
    }

    /**
     * Create new workflow configuration (tenant-specific)
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;
        
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'use_custom_workflow' => 'boolean',
            'allow_separation_override' => 'boolean',
            'min_loan_amount' => 'numeric|min:0',
            'max_loan_amount' => 'nullable|numeric|min:0',
            'steps' => 'required|array|min:1',
            'steps.*.role_id' => 'required|exists:roles,id',
            'steps.*.step_order' => 'required|integer|min:1',
            'steps.*.action_type' => ['required', Rule::in(['APPROVAL', 'DISBURSEMENT'])],
            'steps.*.min_amount' => 'numeric|min:0',
            'steps.*.max_amount' => 'nullable|numeric|min:0',
            'steps.*.step_name' => 'required|string|max:100',
            'steps.*.description' => 'nullable|string',
            'steps.*.require_comment' => 'boolean',
            'steps.*.can_skip' => 'boolean',
            'steps.*.timeout_hours' => 'nullable|integer|min:1',
        ]);

        // Validate that last step is DISBURSEMENT
        $steps = collect($validated['steps'])->sortBy('step_order');
        $lastStep = $steps->last();
        if ($lastStep['action_type'] !== 'DISBURSEMENT') {
            return response()->json([
                'success' => false,
                'message' => 'Final workflow step must be DISBURSEMENT type.',
            ], 422);
        }

        // Validate step order is sequential
        $stepOrders = $steps->pluck('step_order')->toArray();
        $expectedOrders = range(1, count($stepOrders));
        if ($stepOrders !== $expectedOrders) {
            return response()->json([
                'success' => false,
                'message' => 'Step orders must be sequential starting from 1.',
            ], 422);
        }

        $workflow = DB::transaction(function () use ($validated, $tenantId) {
            $workflow = WorkflowConfig::create([
                'tenant_id' => $tenantId,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_global' => false,
                'is_active' => $validated['is_active'] ?? true,
                'use_custom_workflow' => $validated['use_custom_workflow'] ?? true,
                'allow_separation_override' => $validated['allow_separation_override'] ?? false,
                'min_loan_amount' => $validated['min_loan_amount'] ?? 0,
                'max_loan_amount' => $validated['max_loan_amount'] ?? null,
            ]);

            foreach ($validated['steps'] as $step) {
                WorkflowStep::create([
                    'workflow_config_id' => $workflow->id,
                    'role_id' => $step['role_id'],
                    'step_order' => $step['step_order'],
                    'action_type' => $step['action_type'],
                    'min_amount' => $step['min_amount'] ?? 0,
                    'max_amount' => $step['max_amount'] ?? null,
                    'step_name' => $step['step_name'],
                    'description' => $step['description'] ?? null,
                    'is_active' => true,
                    'require_comment' => $step['require_comment'] ?? false,
                    'can_skip' => $step['can_skip'] ?? false,
                    'timeout_hours' => $step['timeout_hours'] ?? null,
                ]);
            }

            return $workflow;
        });

        $workflow->load(['steps.role:id,name,slug']);

        return response()->json([
            'success' => true,
            'message' => 'Workflow configuration created successfully.',
            'data' => $workflow,
        ], 201);
    }

    /**
     * Update workflow configuration
     */
    public function update(Request $request, WorkflowConfig $workflowConfig): JsonResponse
    {
        $this->authorizeAccess($workflowConfig);
        
        // Cannot modify global workflow unless super admin
        if ($workflowConfig->is_global && !auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only super admin can modify global workflow.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'use_custom_workflow' => 'boolean',
            'allow_separation_override' => 'boolean',
            'min_loan_amount' => 'numeric|min:0',
            'max_loan_amount' => 'nullable|numeric|min:0',
        ]);

        $workflowConfig->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Workflow configuration updated successfully.',
            'data' => $workflowConfig->fresh(['steps.role:id,name,slug']),
        ]);
    }

    /**
     * Toggle custom workflow for tenant
     */
    public function toggleCustomWorkflow(Request $request): JsonResponse
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;
        
        $validated = $request->validate([
            'use_custom_workflow' => 'required|boolean',
        ]);

        $workflow = WorkflowConfig::where('tenant_id', $tenantId)
            ->where('is_global', false)
            ->first();

        if (!$workflow) {
            return response()->json([
                'success' => false,
                'message' => 'No custom workflow configured for this tenant.',
            ], 404);
        }

        $workflow->update(['use_custom_workflow' => $validated['use_custom_workflow']]);

        return response()->json([
            'success' => true,
            'message' => $validated['use_custom_workflow'] 
                ? 'Custom workflow enabled.' 
                : 'Switched to global workflow.',
            'data' => $workflow,
        ]);
    }

    /**
     * Delete workflow configuration
     */
    public function destroy(WorkflowConfig $workflowConfig): JsonResponse
    {
        $this->authorizeAccess($workflowConfig);
        
        // Cannot delete global workflow
        if ($workflowConfig->is_global) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete global workflow.',
            ], 403);
        }

        // Check if workflow is in use
        $inUse = $workflowConfig->loanWorkflowStates()
            ->whereNotIn('status', ['DISBURSED', 'REJECTED', 'CANCELLED'])
            ->exists();

        if ($inUse) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete workflow with active loans.',
            ], 422);
        }

        $workflowConfig->delete();

        return response()->json([
            'success' => true,
            'message' => 'Workflow configuration deleted successfully.',
        ]);
    }

    /**
     * Create/Update global workflow (super admin only)
     */
    public function upsertGlobal(Request $request): JsonResponse
    {
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only super admin can manage global workflow.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'allow_separation_override' => 'boolean',
            'steps' => 'required|array|min:1',
            'steps.*.role_id' => 'required|exists:roles,id',
            'steps.*.step_order' => 'required|integer|min:1',
            'steps.*.action_type' => ['required', Rule::in(['APPROVAL', 'DISBURSEMENT'])],
            'steps.*.min_amount' => 'numeric|min:0',
            'steps.*.max_amount' => 'nullable|numeric|min:0',
            'steps.*.step_name' => 'required|string|max:100',
            'steps.*.description' => 'nullable|string',
            'steps.*.require_comment' => 'boolean',
            'steps.*.can_skip' => 'boolean',
            'steps.*.timeout_hours' => 'nullable|integer|min:1',
        ]);

        // Validate that last step is DISBURSEMENT
        $steps = collect($validated['steps'])->sortBy('step_order');
        $lastStep = $steps->last();
        if ($lastStep['action_type'] !== 'DISBURSEMENT') {
            return response()->json([
                'success' => false,
                'message' => 'Final workflow step must be DISBURSEMENT type.',
            ], 422);
        }

        $workflow = DB::transaction(function () use ($validated) {
            $workflow = WorkflowConfig::updateOrCreate(
                ['is_global' => true, 'tenant_id' => null],
                [
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'is_active' => true,
                    'allow_separation_override' => $validated['allow_separation_override'] ?? false,
                ]
            );

            // Delete existing steps and recreate
            $workflow->steps()->delete();

            foreach ($validated['steps'] as $step) {
                WorkflowStep::create([
                    'workflow_config_id' => $workflow->id,
                    'role_id' => $step['role_id'],
                    'step_order' => $step['step_order'],
                    'action_type' => $step['action_type'],
                    'min_amount' => $step['min_amount'] ?? 0,
                    'max_amount' => $step['max_amount'] ?? null,
                    'step_name' => $step['step_name'],
                    'description' => $step['description'] ?? null,
                    'is_active' => true,
                    'require_comment' => $step['require_comment'] ?? false,
                    'can_skip' => $step['can_skip'] ?? false,
                    'timeout_hours' => $step['timeout_hours'] ?? null,
                ]);
            }

            return $workflow;
        });

        $workflow->load(['steps.role:id,name,slug']);

        return response()->json([
            'success' => true,
            'message' => 'Global workflow saved successfully.',
            'data' => $workflow,
        ]);
    }

    /**
     * Get available roles for workflow steps
     */
    public function getAvailableRoles(): JsonResponse
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;
        
        $roles = Role::where('tenant_id', $tenantId)
            ->orWhereNull('tenant_id')
            ->select('id', 'name', 'slug', 'category')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $roles,
        ]);
    }

    /**
     * Authorize access to workflow config
     */
    protected function authorizeAccess(WorkflowConfig $workflowConfig): void
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;
        
        // Super admin can access all
        if (auth()->user()->isSuperAdmin()) {
            return;
        }

        // Check tenant ownership or global
        if (!$workflowConfig->is_global && $workflowConfig->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized access to workflow configuration.');
        }
    }
}
