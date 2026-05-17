<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowTemplate extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'default_steps',
        'is_active',
        'sort_order',
        'icon',
        'features',
    ];

    protected $casts = [
        'default_steps' => 'array',
        'features' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    const CATEGORY_GENERAL = 'general';
    const CATEGORY_MICROFINANCE = 'microfinance';
    const CATEGORY_CORPORATE = 'corporate';
    const CATEGORY_AGRICULTURAL = 'agricultural';
    const CATEGORY_EMERGENCY = 'emergency';

    const ACTION_TYPE_APPROVAL = 'APPROVAL';
    const ACTION_TYPE_VERIFICATION = 'VERIFICATION';
    const ACTION_TYPE_CREDIT_CHECK = 'CREDIT_CHECK';
    const ACTION_TYPE_DOCUMENT_REVIEW = 'DOCUMENT_REVIEW';
    const ACTION_TYPE_COMMITTEE_REVIEW = 'COMMITTEE_REVIEW';
    const ACTION_TYPE_FINAL_APPROVAL = 'FINAL_APPROVAL';
    const ACTION_TYPE_DISBURSEMENT = 'DISBURSEMENT';

    public function workflowConfigs(): HasMany
    {
        return $this->hasMany(WorkflowConfig::class, 'template_id');
    }

    public static function getActiveTemplates(?string $category = null)
    {
        $query = static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($category) {
            $query->where('category', $category);
        }

        return $query->get();
    }

    public static function getByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    public function getStepsForAmount(?float $amount = null): array
    {
        $steps = $this->default_steps ?? [];
        
        if ($amount === null) {
            return $steps;
        }

        return array_filter($steps, function ($step) use ($amount) {
            $minAmount = $step['min_amount'] ?? 0;
            $maxAmount = $step['max_amount'] ?? null;
            
            $meetsMin = $minAmount <= $amount;
            $meetsMax = $maxAmount === null || $maxAmount >= $amount;
            
            return $meetsMin && $meetsMax;
        });
    }

    public function createWorkflowForTenant(int $tenantId, ?string $name = null): WorkflowConfig
    {
        $workflow = WorkflowConfig::create([
            'tenant_id' => $tenantId,
            'template_id' => $this->id,
            'name' => $name ?? $this->name,
            'description' => $this->description,
            'workflow_type' => 'template',
            'is_global' => false,
            'is_active' => true,
            'use_custom_workflow' => true,
        ]);

        foreach ($this->default_steps as $index => $stepData) {
            $roleId = $this->resolveRoleId($stepData['role_slug'] ?? null, $tenantId);
            
            if (!$roleId) {
                continue;
            }

            WorkflowStep::create([
                'workflow_config_id' => $workflow->id,
                'role_id' => $roleId,
                'step_order' => $stepData['step_order'] ?? ($index + 1),
                'action_type' => $stepData['action_type'] ?? 'APPROVAL',
                'min_amount' => $stepData['min_amount'] ?? 0,
                'max_amount' => $stepData['max_amount'] ?? null,
                'step_name' => $stepData['step_name'] ?? 'Step ' . ($index + 1),
                'description' => $stepData['description'] ?? null,
                'is_active' => true,
                'require_comment' => $stepData['require_comment'] ?? false,
                'require_documents' => $stepData['require_documents'] ?? false,
                'can_skip' => $stepData['can_skip'] ?? false,
                'allow_delegation' => $stepData['allow_delegation'] ?? false,
                'timeout_hours' => $stepData['timeout_hours'] ?? null,
                'escalation_hours' => $stepData['escalation_hours'] ?? null,
                'conditions' => $stepData['conditions'] ?? null,
                'auto_actions' => $stepData['auto_actions'] ?? null,
            ]);
        }

        return $workflow->load('steps');
    }

    protected function resolveRoleId(?string $roleSlug, int $tenantId): ?int
    {
        if (!$roleSlug) {
            return null;
        }

        $role = Role::where('slug', $roleSlug)
            ->where(function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)
                  ->orWhereNull('tenant_id');
            })
            ->first();

        return $role?->id;
    }

    public static function getAvailableActionTypes(): array
    {
        return [
            self::ACTION_TYPE_VERIFICATION => [
                'label' => 'Document Verification',
                'description' => 'Verify submitted documents and information',
                'icon' => 'file-check',
            ],
            self::ACTION_TYPE_CREDIT_CHECK => [
                'label' => 'Credit Assessment',
                'description' => 'Review credit history and risk assessment',
                'icon' => 'credit-card',
            ],
            self::ACTION_TYPE_DOCUMENT_REVIEW => [
                'label' => 'Document Review',
                'description' => 'Detailed review of loan documents',
                'icon' => 'file-search',
            ],
            self::ACTION_TYPE_APPROVAL => [
                'label' => 'Approval',
                'description' => 'Standard approval step',
                'icon' => 'check-circle',
            ],
            self::ACTION_TYPE_COMMITTEE_REVIEW => [
                'label' => 'Committee Review',
                'description' => 'Review by loan committee for larger amounts',
                'icon' => 'users',
            ],
            self::ACTION_TYPE_FINAL_APPROVAL => [
                'label' => 'Final Approval',
                'description' => 'Final management approval',
                'icon' => 'shield-check',
            ],
            self::ACTION_TYPE_DISBURSEMENT => [
                'label' => 'Disbursement',
                'description' => 'Release funds to borrower',
                'icon' => 'banknotes',
            ],
        ];
    }

    public static function getCategories(): array
    {
        return [
            self::CATEGORY_GENERAL => 'General Purpose',
            self::CATEGORY_MICROFINANCE => 'Microfinance',
            self::CATEGORY_CORPORATE => 'Corporate/Business',
            self::CATEGORY_AGRICULTURAL => 'Agricultural',
            self::CATEGORY_EMERGENCY => 'Emergency/Quick Loans',
        ];
    }
}
