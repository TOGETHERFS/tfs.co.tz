<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Branch;
use App\Models\LoanProduct;
use App\Services\Accounting\ChartOfAccountsService;
use Illuminate\Support\Facades\Log;

class TenantOnboardingService
{
    /**
     * Seed all required default data for a new tenant.
     * This ensures new tenants can immediately create borrowers and loans.
     */
    public static function seedDefaults(Tenant $tenant): array
    {
        $seeded = [];

        try {
            // 1. Seed RBAC (roles and permissions)
            $rbac = RbacService::seedDefaultsForTenant($tenant);
            $seeded['roles'] = $rbac['roles'] ?? [];
            $seeded['permissions'] = $rbac['permissions'] ?? [];

            // 2. Create default branch (required for borrower creation)
            $branch = self::seedDefaultBranch($tenant);
            $seeded['branch'] = $branch;

            // 3. Seed default loan products
            $products = self::seedDefaultLoanProducts($tenant);
            $seeded['loan_products'] = $products;

            // 4. Seed default Chart of Accounts
            self::seedDefaultChartOfAccounts($tenant);
            $seeded['chart_of_accounts'] = true;

            Log::info('Tenant onboarding completed', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'branches_created' => $branch ? 1 : 0,
                'products_created' => count($products),
                'roles_created' => count($seeded['roles']),
                'chart_of_accounts' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('Tenant onboarding failed', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        return $seeded;
    }

    /**
     * Seed default Chart of Accounts for a tenant (idempotent - skips existing).
     */
    public static function seedDefaultChartOfAccounts(Tenant $tenant): void
    {
        try {
            $service = new ChartOfAccountsService();
            $service->setupDefaultAccounts($tenant->id);
        } catch (\Exception $e) {
            Log::warning('Chart of accounts seeding failed for tenant', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create a default headquarters branch for the tenant.
     */
    public static function seedDefaultBranch(Tenant $tenant): ?Branch
    {
        // Check if tenant already has any branch
        $existingBranch = Branch::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->first();
        
        if ($existingBranch) {
            return $existingBranch;
        }

        // Use tenant-specific code since 'code' has global unique constraint
        $code = 'HQ-' . $tenant->id;
        
        return Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Head Office',
            'code' => $code,
            'address' => null,
            'phone' => $tenant->phone,
            'email' => $tenant->contact_email,
            'is_active' => true,
        ]);
    }

    /**
     * Seed default loan products for the tenant.
     */
    public static function seedDefaultLoanProducts(Tenant $tenant): array
    {
        $products = [];
        $now = now();

        $defaultProducts = [
            [
                'name' => 'Group Loan',
                'description' => 'Loan tailored for borrower groups.',
                'min_amount' => 5000,
                'max_amount' => 50000000,
                'interest_rate' => 12.0,
                'interest_type' => 'flat',
                'min_term' => 1,
                'max_term' => 24,
                'processing_fee' => 0,
                'processing_fee_type' => 'percentage',
                'is_active' => true,
            ],
            [
                'name' => 'Other Loan Product',
                'description' => 'General purpose loan product.',
                'min_amount' => 5000,
                'max_amount' => 20000000,
                'interest_rate' => 12.0,
                'interest_type' => 'flat',
                'min_term' => 1,
                'max_term' => 12,
                'processing_fee' => 0,
                'processing_fee_type' => 'percentage',
                'is_active' => true,
            ],
            [
                'name' => 'Staff Loan',
                'description' => 'Special loan for staff members.',
                'min_amount' => 5000,
                'max_amount' => 10000000,
                'interest_rate' => 8.0,
                'interest_type' => 'flat',
                'min_term' => 1,
                'max_term' => 24,
                'processing_fee' => 0,
                'processing_fee_type' => 'percentage',
                'is_active' => true,
            ],
            [
                'name' => 'Agricultural Loan',
                'description' => 'Loan for agricultural activities and farming.',
                'min_amount' => 5000,
                'max_amount' => 50000000,
                'interest_rate' => 15.0,
                'interest_type' => 'flat',
                'min_term' => 1,
                'max_term' => 36,
                'processing_fee' => 0,
                'processing_fee_type' => 'percentage',
                'is_active' => true,
            ],
            [
                'name' => 'Business Loan',
                'description' => 'Loan for business expansion and working capital.',
                'min_amount' => 5000,
                'max_amount' => 100000000,
                'interest_rate' => 15.0,
                'interest_type' => 'reducing_balance',
                'min_term' => 1,
                'max_term' => 36,
                'processing_fee' => 0,
                'processing_fee_type' => 'percentage',
                'is_active' => true,
            ],
            [
                'name' => 'Individual Loan',
                'description' => 'Standard loan for individual borrowers.',
                'min_amount' => 5000,
                'max_amount' => 20000000,
                'interest_rate' => 10.0,
                'interest_type' => 'flat',
                'min_term' => 1,
                'max_term' => 12,
                'processing_fee' => 0,
                'processing_fee_type' => 'percentage',
                'is_active' => true,
            ],
            [
                'name' => 'Asset Loan',
                'description' => 'Loan for purchasing assets like vehicles, equipment.',
                'min_amount' => 5000,
                'max_amount' => 200000000,
                'interest_rate' => 16.0,
                'interest_type' => 'reducing_balance',
                'min_term' => 1,
                'max_term' => 60,
                'processing_fee' => 0,
                'processing_fee_type' => 'percentage',
                'is_active' => true,
            ],
            [
                'name' => 'Emergency Loan',
                'description' => 'Short-term loan for emergencies.',
                'min_amount' => 5000,
                'max_amount' => 2000000,
                'interest_rate' => 10.0,
                'interest_type' => 'flat',
                'min_term' => 1,
                'max_term' => 6,
                'processing_fee' => 0,
                'processing_fee_type' => 'percentage',
                'is_active' => true,
            ],
            [
                'name' => 'Transport Loan',
                'description' => 'Loan for purchasing or repairing vehicles and transport equipment.',
                'min_amount' => 5000,
                'max_amount' => 100000000,
                'interest_rate' => 15.0,
                'interest_type' => 'reducing_balance',
                'min_term' => 1,
                'max_term' => 48,
                'processing_fee' => 0,
                'processing_fee_type' => 'percentage',
                'is_active' => true,
            ],
            [
                'name' => 'Vacation Loan',
                'description' => 'Loan to finance travel and holiday expenses.',
                'min_amount' => 5000,
                'max_amount' => 10000000,
                'interest_rate' => 12.0,
                'interest_type' => 'flat',
                'min_term' => 1,
                'max_term' => 12,
                'processing_fee' => 0,
                'processing_fee_type' => 'percentage',
                'is_active' => true,
            ],
            [
                'name' => 'Funeral Loan',
                'description' => 'Short-term loan to cover funeral and burial expenses.',
                'min_amount' => 5000,
                'max_amount' => 5000000,
                'interest_rate' => 10.0,
                'interest_type' => 'flat',
                'min_term' => 1,
                'max_term' => 6,
                'processing_fee' => 0,
                'processing_fee_type' => 'percentage',
                'is_active' => true,
            ],
            [
                'name' => 'Business Start-Up Loan',
                'description' => 'Loan to help entrepreneurs launch new businesses.',
                'min_amount' => 5000,
                'max_amount' => 50000000,
                'interest_rate' => 15.0,
                'interest_type' => 'flat',
                'repayment_type' => 'amortized',
                'min_term' => 1,
                'max_term' => 36,
                'processing_fee' => 0,
                'processing_fee_type' => 'percentage',
                'is_active' => true,
            ],
            [
                'name' => 'Special Loan',
                'description' => 'Interest-only repayments each period; full principal repaid on the final installment.',
                'min_amount' => 5000,
                'max_amount' => 500000000,
                'interest_rate' => 15.0,
                'interest_type' => 'flat',
                'repayment_type' => 'interest_only',
                'min_term' => 1,
                'max_term' => 36,
                'processing_fee' => 0,
                'processing_fee_type' => 'percentage',
                'is_active' => true,
            ],
            [
                'name' => 'Agriculture Loan',
                'description' => 'Interest-only seasonal loan — borrower pays interest each period and principal on the final installment.',
                'min_amount' => 5000,
                'max_amount' => 500000000,
                'interest_rate' => 12.0,
                'interest_type' => 'flat',
                'repayment_type' => 'interest_only',
                'min_term' => 1,
                'max_term' => 36,
                'processing_fee' => 0,
                'processing_fee_type' => 'percentage',
                'is_active' => true,
            ],
        ];

        foreach ($defaultProducts as $productData) {
            $product = LoanProduct::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name' => $productData['name'],
                ],
                array_merge($productData, [
                    'tenant_id' => $tenant->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
            $products[] = $product;
        }

        return $products;
    }

    /**
     * Check if a tenant has the minimum required data to operate.
     */
    public static function validateTenantSetup(Tenant $tenant): array
    {
        $issues = [];

        // Check for branches
        $branchCount = Branch::where('tenant_id', $tenant->id)->where('is_active', true)->count();
        if ($branchCount === 0) {
            $issues[] = 'No active branches configured';
        }

        // Check for loan products
        $productCount = LoanProduct::where('tenant_id', $tenant->id)->where('is_active', true)->count();
        if ($productCount === 0) {
            $issues[] = 'No active loan products configured';
        }

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'branch_count' => $branchCount,
            'product_count' => $productCount,
        ];
    }

    /**
     * Repair a tenant's missing data by seeding defaults.
     */
    public static function repairTenant(Tenant $tenant): array
    {
        $repairs = [];

        // Check and create branch if missing
        $branchCount = Branch::where('tenant_id', $tenant->id)->count();
        if ($branchCount === 0) {
            $branch = self::seedDefaultBranch($tenant);
            $repairs['branch_created'] = $branch->id;
        }

        // Check and create loan products if missing
        $productCount = LoanProduct::where('tenant_id', $tenant->id)->count();
        if ($productCount === 0) {
            $products = self::seedDefaultLoanProducts($tenant);
            $repairs['products_created'] = count($products);
        }

        // Check and create roles if missing
        $roleCount = \App\Models\Role::where('tenant_id', $tenant->id)->count();
        if ($roleCount === 0) {
            $rbac = RbacService::seedDefaultsForTenant($tenant);
            $repairs['roles_created'] = count($rbac['roles'] ?? []);
        }

        Log::info('Tenant repair completed', [
            'tenant_id' => $tenant->id,
            'repairs' => $repairs,
        ]);

        return $repairs;
    }
}
