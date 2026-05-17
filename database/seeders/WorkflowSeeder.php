<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkflowConfig;
use App\Models\WorkflowStep;
use App\Models\Role;
use App\Models\Tenant;

class WorkflowSeeder extends Seeder
{
    /**
     * Seed the global (system default) workflow and tenant-specific workflows.
     * 
     * This creates a BOT-compliant loan approval workflow with:
     * - CSO Review (initial review)
     * - Loan Officer Review (credit assessment)
     * - Manager Review (for loans above threshold)
     * - GM Approval (for large loans)
     * - Disbursement (final step)
     */
    public function run(): void
    {
        // Create global workflow
        $this->createGlobalWorkflow();

        // Create tenant-specific workflows for active tenants
        $this->createTenantWorkflows();

        $this->command?->info('Workflow configurations seeded successfully.');
    }

    /**
     * Create the global (system default) workflow
     */
    protected function createGlobalWorkflow(): void
    {
        // Get or create system roles (these should exist from RbacSeeder)
        $roles = $this->getOrCreateSystemRoles();

        // Create global workflow config
        $globalWorkflow = WorkflowConfig::updateOrCreate(
            ['is_global' => true, 'tenant_id' => null],
            [
                'name' => 'Default Loan Approval Workflow',
                'description' => 'Standard multi-stage loan approval workflow for microfinance institutions. Compliant with BOT regulations.',
                'is_active' => true,
                'use_custom_workflow' => false,
                'allow_separation_override' => false,
                'min_loan_amount' => 0,
                'max_loan_amount' => null,
            ]
        );

        // Clear existing steps
        $globalWorkflow->steps()->delete();

        // Create workflow steps
        $steps = [
            [
                'step_order' => 1,
                'role_slug' => 'officer',
                'action_type' => 'APPROVAL',
                'min_amount' => 0,
                'max_amount' => null,
                'step_name' => 'CSO Review',
                'description' => 'Initial review by Customer Service Officer. Verify client documents and loan application completeness.',
                'require_comment' => false,
                'can_skip' => false,
                'timeout_hours' => 24,
            ],
            [
                'step_order' => 2,
                'role_slug' => 'loan_officer',
                'action_type' => 'APPROVAL',
                'min_amount' => 0,
                'max_amount' => null,
                'step_name' => 'Loan Officer Review',
                'description' => 'Credit assessment and risk evaluation by Loan Officer.',
                'require_comment' => true,
                'can_skip' => false,
                'timeout_hours' => 48,
            ],
            [
                'step_order' => 3,
                'role_slug' => 'manager',
                'action_type' => 'APPROVAL',
                'min_amount' => 500000, // TZS 500,000+
                'max_amount' => null,
                'step_name' => 'Manager Review',
                'description' => 'Management approval for loans above TZS 500,000.',
                'require_comment' => true,
                'can_skip' => true, // Can be skipped for smaller loans
                'timeout_hours' => 48,
            ],
            [
                'step_order' => 4,
                'role_slug' => 'gm',
                'action_type' => 'APPROVAL',
                'min_amount' => 2000000, // TZS 2,000,000+
                'max_amount' => null,
                'step_name' => 'GM Approval',
                'description' => 'General Manager approval for loans above TZS 2,000,000.',
                'require_comment' => true,
                'can_skip' => true, // Can be skipped for smaller loans
                'timeout_hours' => 72,
            ],
            [
                'step_order' => 5,
                'role_slug' => 'accountant',
                'action_type' => 'DISBURSEMENT',
                'min_amount' => 0,
                'max_amount' => null,
                'step_name' => 'Disbursement',
                'description' => 'Final disbursement of approved loan by Finance department.',
                'require_comment' => false,
                'can_skip' => false,
                'timeout_hours' => 24,
            ],
        ];

        foreach ($steps as $stepData) {
            $role = $roles[$stepData['role_slug']] ?? null;
            if (!$role) {
                $this->command?->warn("Role '{$stepData['role_slug']}' not found. Skipping step.");
                continue;
            }

            WorkflowStep::create([
                'workflow_config_id' => $globalWorkflow->id,
                'role_id' => $role->id,
                'step_order' => $stepData['step_order'],
                'action_type' => $stepData['action_type'],
                'min_amount' => $stepData['min_amount'],
                'max_amount' => $stepData['max_amount'],
                'step_name' => $stepData['step_name'],
                'description' => $stepData['description'],
                'is_active' => true,
                'require_comment' => $stepData['require_comment'],
                'can_skip' => $stepData['can_skip'],
                'timeout_hours' => $stepData['timeout_hours'],
            ]);
        }

        $this->command?->info('Global workflow created with ' . count($steps) . ' steps.');
    }

    /**
     * Create tenant-specific workflow configurations
     */
    protected function createTenantWorkflows(): void
    {
        $tenants = Tenant::where('status', 'active')->get();

        foreach ($tenants as $tenant) {
            // Check if tenant already has a workflow
            $existingWorkflow = WorkflowConfig::where('tenant_id', $tenant->id)
                ->where('is_global', false)
                ->first();

            if ($existingWorkflow) {
                continue; // Skip if tenant already has custom workflow
            }

            // Get tenant roles
            $roles = $this->getTenantRoles($tenant->id);

            if (empty($roles)) {
                $this->command?->warn("No roles found for tenant '{$tenant->name}'. Skipping.");
                continue;
            }

            // Create tenant workflow (initially disabled, using global)
            $workflow = WorkflowConfig::create([
                'tenant_id' => $tenant->id,
                'name' => $tenant->name . ' Custom Workflow',
                'description' => 'Custom loan approval workflow for ' . $tenant->name,
                'is_global' => false,
                'is_active' => true,
                'use_custom_workflow' => false, // Use global by default
                'allow_separation_override' => false,
                'min_loan_amount' => 0,
                'max_loan_amount' => null,
            ]);

            // Create basic steps using tenant roles
            $this->createTenantWorkflowSteps($workflow, $roles);
        }
    }

    /**
     * Create workflow steps for a tenant
     */
    protected function createTenantWorkflowSteps(WorkflowConfig $workflow, array $roles): void
    {
        $stepOrder = 1;

        // CSO/Officer Review
        if (isset($roles['officer']) || isset($roles['cso'])) {
            WorkflowStep::create([
                'workflow_config_id' => $workflow->id,
                'role_id' => ($roles['officer'] ?? $roles['cso'])->id,
                'step_order' => $stepOrder++,
                'action_type' => 'APPROVAL',
                'min_amount' => 0,
                'max_amount' => null,
                'step_name' => 'Initial Review',
                'description' => 'Initial review and document verification.',
                'is_active' => true,
                'require_comment' => false,
                'can_skip' => false,
                'timeout_hours' => 24,
            ]);
        }

        // Loan Officer Review
        if (isset($roles['loan_officer'])) {
            WorkflowStep::create([
                'workflow_config_id' => $workflow->id,
                'role_id' => $roles['loan_officer']->id,
                'step_order' => $stepOrder++,
                'action_type' => 'APPROVAL',
                'min_amount' => 0,
                'max_amount' => null,
                'step_name' => 'Loan Officer Review',
                'description' => 'Credit assessment and approval.',
                'is_active' => true,
                'require_comment' => true,
                'can_skip' => false,
                'timeout_hours' => 48,
            ]);
        }

        // Manager Review (for larger loans)
        if (isset($roles['manager'])) {
            WorkflowStep::create([
                'workflow_config_id' => $workflow->id,
                'role_id' => $roles['manager']->id,
                'step_order' => $stepOrder++,
                'action_type' => 'APPROVAL',
                'min_amount' => 500000,
                'max_amount' => null,
                'step_name' => 'Manager Approval',
                'description' => 'Management approval for larger loans.',
                'is_active' => true,
                'require_comment' => true,
                'can_skip' => true,
                'timeout_hours' => 48,
            ]);
        }

        // Disbursement
        $disburserRole = $roles['accountant'] ?? $roles['teller'] ?? $roles['cashier'] ?? null;
        if ($disburserRole) {
            WorkflowStep::create([
                'workflow_config_id' => $workflow->id,
                'role_id' => $disburserRole->id,
                'step_order' => $stepOrder++,
                'action_type' => 'DISBURSEMENT',
                'min_amount' => 0,
                'max_amount' => null,
                'step_name' => 'Disbursement',
                'description' => 'Loan disbursement by finance.',
                'is_active' => true,
                'require_comment' => false,
                'can_skip' => false,
                'timeout_hours' => 24,
            ]);
        }
    }

    /**
     * Get or create system roles for global workflow
     */
    protected function getOrCreateSystemRoles(): array
    {
        $roleSlugs = ['officer', 'loan_officer', 'manager', 'gm', 'accountant', 'teller', 'cashier'];
        $roles = [];

        // Try to get roles from first active tenant
        $tenant = Tenant::where('status', 'active')->first();
        
        if ($tenant) {
            foreach ($roleSlugs as $slug) {
                $role = Role::where('tenant_id', $tenant->id)
                    ->where('slug', $slug)
                    ->first();
                
                if ($role) {
                    $roles[$slug] = $role;
                }
            }
        }

        // Fallback: create global roles if none found
        if (empty($roles)) {
            $defaultRoles = [
                ['slug' => 'officer', 'name' => 'Client Officer', 'category' => 'loan'],
                ['slug' => 'loan_officer', 'name' => 'Loan Officer', 'category' => 'loan'],
                ['slug' => 'manager', 'name' => 'Manager', 'category' => 'loan'],
                ['slug' => 'gm', 'name' => 'General Manager', 'category' => 'loan'],
                ['slug' => 'accountant', 'name' => 'Accountant', 'category' => 'finance'],
                ['slug' => 'teller', 'name' => 'Teller', 'category' => 'finance'],
                ['slug' => 'cashier', 'name' => 'Cashier', 'category' => 'finance'],
            ];

            foreach ($defaultRoles as $roleData) {
                $role = Role::firstOrCreate(
                    ['tenant_id' => $tenant?->id, 'slug' => $roleData['slug']],
                    ['name' => $roleData['name'], 'category' => $roleData['category'], 'is_system' => true]
                );
                $roles[$roleData['slug']] = $role;
            }
        }

        return $roles;
    }

    /**
     * Get roles for a specific tenant
     */
    protected function getTenantRoles(int $tenantId): array
    {
        $roles = [];
        $roleSlugs = ['officer', 'cso', 'loan_officer', 'manager', 'gm', 'accountant', 'teller', 'cashier'];

        foreach ($roleSlugs as $slug) {
            $role = Role::where('tenant_id', $tenantId)
                ->where('slug', $slug)
                ->first();
            
            if ($role) {
                $roles[$slug] = $role;
            }
        }

        return $roles;
    }
}
