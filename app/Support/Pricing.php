<?php

namespace App\Support;

use App\Models\Plan;

class Pricing
{
    /**
     * Default price per additional staff member beyond plan limit (TZS)
     * This is only used as fallback if database value is not set
     */
    public const EXTRA_STAFF_PRICE = 30000;
    public const EXTRA_BRANCH_PRICE = 50000;

    /**
     * Get all available subscription plans from database
     */
    public static function getPlans(): array
    {
        $dbPlans = Plan::where('is_active', true)
            ->whereIn('code', ['starter', 'growth', 'enterprise', 'free_trial'])
            ->get()
            ->keyBy('code');

        $plans = [];
        
        // Starter Plan
        $starter = $dbPlans->get('starter');
        $plans['starter'] = [
            'slug' => 'starter',
            'name' => $starter->name ?? __('messages.plan_starter'),
            'description' => __('messages.plan_starter_desc'),
            'price' => (int) ($starter->price ?? 30000),
            'price_formatted' => 'TZS ' . number_format($starter->price ?? 30000),
            'price_per_staff' => (int) ($starter->price_per_staff ?? 0),
            'price_per_branch' => (int) ($starter->price_per_branch ?? 0),
            'billing_period' => 'monthly',
            'max_staff' => (int) ($starter->staff_limit ?? 5),
            'max_branches' => (int) ($starter->branch_limit ?? 1),
            'features' => [
                __('messages.feature_staff_1'),
                __('messages.feature_unlimited_clients'),
                __('messages.feature_basic_loan'),
                __('messages.feature_payment_tracking'),
                __('messages.feature_basic_reporting'),
                __('messages.feature_email_support'),
                __('messages.feature_mobile_interface'),
                __('messages.feature_data_backup')
            ],
            'popular' => false,
            'color' => 'blue'
        ];

        // Growth Plan
        $growth = $dbPlans->get('growth');
        $plans['growth'] = [
            'slug' => 'growth',
            'name' => $growth->name ?? __('messages.plan_growth'),
            'description' => __('messages.plan_growth_desc'),
            'price' => (int) ($growth->price ?? 80000),
            'price_formatted' => 'TZS ' . number_format($growth->price ?? 80000),
            'price_per_staff' => (int) ($growth->price_per_staff ?? 0),
            'price_per_branch' => (int) ($growth->price_per_branch ?? 0),
            'billing_period' => 'monthly',
            'max_staff' => (int) ($growth->staff_limit ?? 10),
            'max_branches' => (int) ($growth->branch_limit ?? 3),
            'features' => [
                __('messages.feature_staff_4'),
                __('messages.feature_unlimited_clients'),
                __('messages.feature_advanced_loan'),
                __('messages.feature_payment_automation'),
                __('messages.feature_advanced_reporting'),
                __('messages.feature_priority_email'),
                __('messages.feature_mobile_interface'),
                __('messages.feature_daily_backup'),
                __('messages.feature_multi_currency'),
                __('messages.feature_custom_products')
            ],
            'popular' => true,
            'color' => 'green'
        ];

        // Enterprise Plan
        $enterprise = $dbPlans->get('enterprise');
        $plans['enterprise'] = [
            'slug' => 'enterprise',
            'name' => $enterprise->name ?? __('messages.plan_enterprise'),
            'description' => __('messages.plan_enterprise_desc'),
            'price' => (int) ($enterprise->price ?? 100000),
            'price_formatted' => 'TZS ' . number_format($enterprise->price ?? 100000),
            'price_per_staff' => (int) ($enterprise->price_per_staff ?? 0),
            'price_per_branch' => (int) ($enterprise->price_per_branch ?? 0),
            'billing_period' => 'monthly',
            'max_staff' => (int) ($enterprise->staff_limit ?? 20),
            'max_branches' => (int) ($enterprise->branch_limit ?? 10),
            'features' => [
                __('messages.feature_staff_10'),
                __('messages.feature_unlimited_clients'),
                __('messages.feature_full_loan_suite'),
                __('messages.feature_automated_payment'),
                __('messages.feature_comprehensive_reporting'),
                __('messages.feature_priority_phone_email'),
                __('messages.feature_mobile_interface'),
                __('messages.feature_realtime_backup'),
                __('messages.feature_multi_currency'),
                __('messages.feature_custom_products'),
                __('messages.feature_whitelabel'),
                __('messages.feature_account_manager'),
                __('messages.feature_custom_integrations')
            ],
            'popular' => false,
            'color' => 'purple'
        ];

        // Free Trial Plan
        $trial = $dbPlans->get('free_trial');
        $plans['free_trial'] = [
            'slug' => 'free_trial',
            'name' => $trial->name ?? 'Free Trial',
            'description' => 'Trial plan with basic features',
            'price' => 0,
            'price_formatted' => 'Free',
            'price_per_staff' => 0,
            'price_per_branch' => 0,
            'billing_period' => 'monthly',
            'max_staff' => (int) ($trial->staff_limit ?? 2),
            'max_branches' => (int) ($trial->branch_limit ?? 3),
            'features' => [],
            'popular' => false,
            'color' => 'gray'
        ];

        return $plans;
    }

    /**
     * Get a specific plan by slug
     */
    public static function getPlan(string $slug): ?array
    {
        $plans = self::getPlans();
        return $plans[$slug] ?? null;
    }

    /**
     * Get all plan slugs
     */
    public static function getPlanSlugs(): array
    {
        return array_keys(self::getPlans());
    }

    /**
     * Check if a plan slug is valid
     */
    public static function isValidPlan(string $slug): bool
    {
        return in_array($slug, self::getPlanSlugs());
    }

    /**
     * Get the maximum staff allowed for a plan
     */
    public static function getMaxStaff(string $slug): int
    {
        $plan = self::getPlan($slug);
        return $plan ? $plan['max_staff'] : 0;
    }

    /**
     * Get the maximum branches allowed for a plan
     */
    public static function getMaxBranches(string $slug): int
    {
        $plan = self::getPlan($slug);
        return $plan ? ($plan['max_branches'] ?? 1) : 1;
    }

    /**
     * Get plan price
     */
    public static function getPlanPrice(string $slug): int
    {
        $plan = self::getPlan($slug);
        return $plan ? $plan['price'] : 0;
    }

    /**
     * Get formatted plan price
     */
    public static function getFormattedPrice(string $slug): string
    {
        $plan = self::getPlan($slug);
        return $plan ? $plan['price_formatted'] : 'N/A';
    }

    /**
     * Get the default plan (starter)
     */
    public static function getDefaultPlan(): array
    {
        return self::getPlan('starter');
    }

    /**
     * Get plans formatted for select options
     */
    public static function getPlansForSelect(): array
    {
        $plans = self::getPlans();
        $options = [];
        
        foreach ($plans as $slug => $plan) {
            $staffLabel = $plan['max_staff'] . ' staff member' . ($plan['max_staff'] > 1 ? 's' : '');
            $options[$slug] = $plan['name'] . ' - ' . $plan['price_formatted'] . ' (' . $staffLabel . ')';
        }
        
        return $options;
    }

    /**
     * Calculate next renewal date
     */
    public static function getNextRenewalDate(): \Carbon\Carbon
    {
        return now()->addMonth();
    }

    /**
     * Calculate next renewal date for a specific plan
     */
    public static function calculateNextRenewal(string $planSlug): \Carbon\Carbon
    {
        $plan = self::getPlan($planSlug);
        
        if (!$plan) {
            return now()->addMonth();
        }
        
        // For now, all plans are monthly
        return now()->addMonth();
    }

    /**
     * Check if a tenant can add more staff (always true, extra staff charged separately)
     */
    public static function canAddStaff(string $planSlug, int $currentStaffCount): bool
    {
        return true; // Always allow, extra staff will be charged
    }

    /**
     * Calculate extra staff count beyond plan limit
     */
    public static function getExtraStaffCount(string $planSlug, int $currentStaffCount): int
    {
        $maxStaff = self::getMaxStaff($planSlug);
        return max(0, $currentStaffCount - $maxStaff);
    }

    /**
     * Calculate extra staff charges using plan's price_per_staff
     */
    public static function calculateExtraStaffCharge(string $planSlug, int $currentStaffCount): int
    {
        $extraStaff = self::getExtraStaffCount($planSlug, $currentStaffCount);
        $plan = self::getPlan($planSlug);
        $pricePerStaff = $plan['price_per_staff'] ?? self::EXTRA_STAFF_PRICE;
        return $extraStaff * $pricePerStaff;
    }

    /**
     * Calculate extra branch count beyond plan limit
     */
    public static function getExtraBranchCount(string $planSlug, int $currentBranchCount): int
    {
        $maxBranches = self::getMaxBranches($planSlug);
        return max(0, $currentBranchCount - $maxBranches);
    }

    /**
     * Calculate extra branch charges using plan's price_per_branch
     */
    public static function calculateExtraBranchCharge(string $planSlug, int $currentBranchCount): int
    {
        $extraBranches = self::getExtraBranchCount($planSlug, $currentBranchCount);
        $plan = self::getPlan($planSlug);
        $pricePerBranch = $plan['price_per_branch'] ?? self::EXTRA_BRANCH_PRICE;
        return $extraBranches * $pricePerBranch;
    }

    /**
     * Get formatted extra staff price for a specific plan
     */
    public static function getExtraStaffPriceFormatted(string $planSlug = null): string
    {
        if ($planSlug) {
            $plan = self::getPlan($planSlug);
            $price = $plan['price_per_staff'] ?? self::EXTRA_STAFF_PRICE;
        } else {
            $price = self::EXTRA_STAFF_PRICE;
        }
        return 'TZS ' . number_format($price);
    }

    /**
     * Get formatted extra branch price for a specific plan
     */
    public static function getExtraBranchPriceFormatted(string $planSlug = null): string
    {
        if ($planSlug) {
            $plan = self::getPlan($planSlug);
            $price = $plan['price_per_branch'] ?? self::EXTRA_BRANCH_PRICE;
        } else {
            $price = self::EXTRA_BRANCH_PRICE;
        }
        return 'TZS ' . number_format($price);
    }

    /**
     * Calculate total monthly cost including extra staff and branches
     */
    public static function calculateFullMonthlyCost(string $planSlug, int $staffCount, int $branchCount): int
    {
        $basePlan = self::getPlanPrice($planSlug);
        $extraStaffCharge = self::calculateExtraStaffCharge($planSlug, $staffCount);
        $extraBranchCharge = self::calculateExtraBranchCharge($planSlug, $branchCount);
        return $basePlan + $extraStaffCharge + $extraBranchCharge;
    }

    /**
     * Get upgrade suggestions for a plan
     */
    public static function getUpgradeSuggestions(string $currentPlan): array
    {
        $plans = self::getPlans();
        $suggestions = [];
        
        $planOrder = ['starter', 'growth', 'enterprise'];
        $currentIndex = array_search($currentPlan, $planOrder);
        
        if ($currentIndex !== false && $currentIndex < count($planOrder) - 1) {
            for ($i = $currentIndex + 1; $i < count($planOrder); $i++) {
                $suggestions[] = $plans[$planOrder[$i]];
            }
        }
        
        return $suggestions;
    }
}