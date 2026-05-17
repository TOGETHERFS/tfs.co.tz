<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Tenant;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

class RbacService
{
    /**
     * Seed default roles and permissions for a single tenant.
     * Returns an array with ['roles' => Role[], 'permissions' => Permission[]].
     */
    public static function seedDefaultsForTenant(Tenant $tenant): array
    {
        // Define default roles
        $roles = [
            ['slug' => 'admin',         'name' => 'Administrator',   'category' => 'system', 'is_system' => true],
            ['slug' => 'superadmin',    'name' => 'Super Admin',     'category' => 'system', 'is_system' => true],
            ['slug' => 'officer',       'name' => 'Client Officer',  'category' => 'loan',   'is_system' => true],
            ['slug' => 'loan_officer',  'name' => 'Loan Officer',    'category' => 'loan',   'is_system' => true],
            ['slug' => 'manager',       'name' => 'Manager',         'category' => 'loan',   'is_system' => true],
            ['slug' => 'gm',            'name' => 'General Manager', 'category' => 'loan',   'is_system' => true],
            ['slug' => 'accountant',    'name' => 'Accountant',      'category' => 'finance','is_system' => true],
        ];

        // Define default permissions (comprehensive list for all modules)
        $permissions = [
            // Dashboard
            ['slug' => 'dashboard.view', 'name' => 'View Dashboard', 'description' => 'Access to dashboard'],

            // Borrowers/Clients
            ['slug' => 'borrowers.view', 'name' => 'View Borrowers', 'description' => 'View borrowers/clients list'],
            ['slug' => 'borrowers.create', 'name' => 'Create Borrowers', 'description' => 'Add new borrowers/clients'],
            ['slug' => 'borrowers.edit', 'name' => 'Edit Borrowers', 'description' => 'Edit borrower information'],
            ['slug' => 'borrowers.delete', 'name' => 'Delete Borrowers', 'description' => 'Delete borrowers'],

            // Loans
            ['slug' => 'loans.view', 'name' => 'View Loans', 'description' => 'View loans within tenant'],
            ['slug' => 'loans.create', 'name' => 'Create Loans', 'description' => 'Create new loan applications'],
            ['slug' => 'loans.edit', 'name' => 'Edit Loans', 'description' => 'Edit loan details'],
            ['slug' => 'loans.delete', 'name' => 'Delete Loans', 'description' => 'Delete loans'],
            ['slug' => 'loan.stage.decide', 'name' => 'Decide Loan Stage', 'description' => 'Approve or reject current loan stage'],
            ['slug' => 'loan.disburse', 'name' => 'Disburse Loan', 'description' => 'Disburse approved loans to clients'],

            // Loan Products
            ['slug' => 'loan-products.view', 'name' => 'View Loan Products', 'description' => 'View loan products'],
            ['slug' => 'loan-products.manage', 'name' => 'Manage Loan Products', 'description' => 'Create and edit loan products'],

            // Repayments
            ['slug' => 'repayments.view', 'name' => 'View Repayments', 'description' => 'View repayment records'],
            ['slug' => 'repayments.create', 'name' => 'Record Repayments', 'description' => 'Record loan repayments'],
            ['slug' => 'repayments.edit', 'name' => 'Edit Repayments', 'description' => 'Edit repayment records'],
            ['slug' => 'repayments.delete', 'name' => 'Delete Repayments', 'description' => 'Delete repayment records'],

            // Reports
            ['slug' => 'reports.view', 'name' => 'View Reports', 'description' => 'Access reports section'],
            ['slug' => 'reports.export', 'name' => 'Export Reports', 'description' => 'Export reports to PDF/Excel'],

            // Staff & Roles
            ['slug' => 'staff.view', 'name' => 'View Staff', 'description' => 'View staff members'],
            ['slug' => 'staff.create', 'name' => 'Create Staff', 'description' => 'Add new staff members'],
            ['slug' => 'staff.edit', 'name' => 'Edit Staff', 'description' => 'Edit staff information'],
            ['slug' => 'staff.delete', 'name' => 'Delete Staff', 'description' => 'Delete staff members'],
            ['slug' => 'roles.manage', 'name' => 'Manage Roles', 'description' => 'Create and manage roles'],

            // Branches
            ['slug' => 'branches.view', 'name' => 'View Branches', 'description' => 'View branches'],
            ['slug' => 'branches.create', 'name' => 'Create Branches', 'description' => 'Create new branches'],
            ['slug' => 'branches.edit', 'name' => 'Edit Branches', 'description' => 'Edit branch information'],
            ['slug' => 'branches.delete', 'name' => 'Delete Branches', 'description' => 'Delete branches'],

            // Billing & Subscription
            ['slug' => 'billing.view', 'name' => 'View Billing', 'description' => 'View billing and subscription'],
            ['slug' => 'billing.manage', 'name' => 'Manage Billing', 'description' => 'Manage subscription and payments'],

            // Settings
            ['slug' => 'settings.view', 'name' => 'View Settings', 'description' => 'View organization settings'],
            ['slug' => 'settings.manage', 'name' => 'Manage Settings', 'description' => 'Access system settings'],

            // Messages/SMS
            ['slug' => 'messages.view', 'name' => 'View Messages', 'description' => 'View sent messages'],
            ['slug' => 'messages.send', 'name' => 'Send SMS', 'description' => 'Send SMS messages'],
            ['slug' => 'messages.buy-credits', 'name' => 'Buy SMS Credits', 'description' => 'Purchase SMS credits'],

            // Accounts
            ['slug' => 'accounts.view', 'name' => 'View Accounts', 'description' => 'View financial accounts'],
            ['slug' => 'accounts.manage', 'name' => 'Manage Accounts', 'description' => 'Manage financial accounts'],

            // Groups
            ['slug' => 'groups.view', 'name' => 'View Groups', 'description' => 'View borrower groups'],
            ['slug' => 'groups.manage', 'name' => 'Manage Groups', 'description' => 'Create and manage groups'],

            // Admin (Full Access)
            ['slug' => 'admin', 'name' => 'Admin', 'description' => 'Full administrative access'],
        ];

        $roleMap = [];
        foreach ($roles as $r) {
            $role = Role::firstOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => $r['slug']],
                [
                    'tenant_id' => $tenant->id,
                    'name' => $r['name'],
                    'category' => $r['category'],
                    'is_system' => $r['is_system'],
                ]
            );
            $roleMap[$r['slug']] = $role;
        }

        $permMap = [];
        foreach ($permissions as $p) {
            $perm = Permission::firstOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => $p['slug']],
                [
                    'tenant_id' => $tenant->id,
                    'name' => $p['name'],
                    'description' => $p['description'],
                ]
            );
            $permMap[$p['slug']] = $perm;
        }

        // Role -> Permission assignments (using correct permission slugs)
        $assignments = [
            'admin' => array_keys($permMap), // admin gets all
            'officer' => [
                'dashboard.view', 'borrowers.view', 'borrowers.create', 'borrowers.edit',
                'loans.view', 'loans.create', 'loan.stage.decide',
                'repayments.view', 'repayments.create',
            ],
            'loan_officer' => [
                'dashboard.view', 'borrowers.view', 'borrowers.create', 'borrowers.edit',
                'loans.view', 'loans.create', 'loans.edit', 'loan.stage.decide', 'loan.disburse',
                'repayments.view', 'repayments.create', 'repayments.edit',
                'reports.view',
            ],
            'manager' => [
                'dashboard.view', 'borrowers.view', 'borrowers.create', 'borrowers.edit', 'borrowers.delete',
                'loans.view', 'loans.create', 'loans.edit', 'loans.delete', 'loan.stage.decide', 'loan.disburse',
                'loan-products.view', 'loan-products.manage',
                'repayments.view', 'repayments.create', 'repayments.edit', 'repayments.delete',
                'reports.view', 'reports.export',
                'staff.view', 'staff.create', 'staff.edit', 'staff.delete', 'roles.manage',
                'branches.view', 'branches.create', 'branches.edit', 'branches.delete',
                'billing.view', 'billing.manage',
                'settings.view', 'settings.manage',
                'messages.view', 'messages.send', 'messages.buy-credits',
                'accounts.view', 'accounts.manage',
                'groups.view', 'groups.manage',
            ],
            'gm' => [
                'dashboard.view', 'borrowers.view', 'borrowers.create', 'borrowers.edit', 'borrowers.delete',
                'loans.view', 'loans.create', 'loans.edit', 'loans.delete', 'loan.stage.decide', 'loan.disburse',
                'loan-products.view', 'loan-products.manage',
                'repayments.view', 'repayments.create', 'repayments.edit', 'repayments.delete',
                'reports.view', 'reports.export',
                'staff.view', 'staff.create', 'staff.edit',
                'branches.view', 'branches.create', 'branches.edit',
                'billing.view', 'billing.manage',
                'settings.view',
                'messages.view', 'messages.send',
                'accounts.view', 'accounts.manage',
                'groups.view', 'groups.manage',
            ],
            'accountant' => [
                'dashboard.view',
                'loans.view', 'loan.disburse',
                'repayments.view', 'repayments.create', 'repayments.edit',
                'reports.view', 'reports.export',
                'billing.view', 'billing.manage',
                'accounts.view', 'accounts.manage',
            ],
            // Super Admin implicitly has all permissions; we also explicitly grant for consistency
            'superadmin' => array_keys($permMap),
        ];

        foreach ($assignments as $roleSlug => $permSlugs) {
            $role = $roleMap[$roleSlug] ?? null;
            if (!$role) continue;
            foreach ($permSlugs as $ps) {
                $permission = $permMap[$ps] ?? null;
                if (!$permission) continue;
                DB::table('role_permission')->updateOrInsert(
                    [
                        'tenant_id' => $tenant->id,
                        'role_id' => $role->id,
                        'permission_id' => $permission->id,
                    ],
                    [
                        'tenant_id' => $tenant->id,
                        'role_id' => $role->id,
                        'permission_id' => $permission->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        return ['roles' => $roleMap, 'permissions' => $permMap];
    }

    /**
     * Attach a role pivot for a user within the tenant (idempotent).
     */
    public static function attachUserRole(User $user, Role $role): void
    {
        DB::table('user_role')->updateOrInsert(
            [
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'role_id' => $role->id,
            ],
            [
                'tenant_id' => $user->tenant_id,
                'user_id' => $user->id,
                'role_id' => $role->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}