<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

class RbacSeeder extends Seeder
{
    /**
     * Seed default roles and permissions per tenant.
     */
    public function run(): void
    {
        // Resolve target tenant(s). For demo, use the first active tenant.
        $tenants = Tenant::where('status', 'active')->get();
        if ($tenants->isEmpty()) {
            $this->command?->warn('No active tenants found; skipping RBAC seeding.');
            return;
        }

        // Define default roles
        $roles = [
            ['slug' => 'admin',         'name' => 'Administrator',   'category' => 'system', 'is_system' => true],
            // Added Super Admin role for system-wide administration
            ['slug' => 'superadmin',    'name' => 'Super Admin',     'category' => 'system', 'is_system' => true],
            ['slug' => 'officer',       'name' => 'Client Officer',   'category' => 'loan',   'is_system' => true],
            ['slug' => 'loan_officer',  'name' => 'Loan Officer',     'category' => 'loan',   'is_system' => true],
            ['slug' => 'manager',       'name' => 'Manager',          'category' => 'loan',   'is_system' => true],
            ['slug' => 'gm',            'name' => 'General Manager',  'category' => 'loan',   'is_system' => true],
            ['slug' => 'accountant',    'name' => 'Accountant',       'category' => 'finance','is_system' => true],
            ['slug' => 'teller',        'name' => 'Teller',           'category' => 'finance','is_system' => true],
            ['slug' => 'cashier',       'name' => 'Cashier',          'category' => 'finance','is_system' => true],
        ];

        // Define default permissions (match gates and policy requirements)
        $permissions = [
            ['slug' => 'loan.view',              'name' => 'Loans',                      'description' => 'Manage loans'],
            ['slug' => 'loan.update',            'name' => 'Update Loans',               'description' => 'Update loan details'],
            ['slug' => 'loan.stage.decide',      'name' => 'Approve Loan',               'description' => 'Approve or reject loan stages'],
            ['slug' => 'loan.disburse',          'name' => 'Disburse Loan',              'description' => 'Disburse approved loans to clients'],
            ['slug' => 'manage-settings',        'name' => 'Settings',                   'description' => 'Access organization settings'],
            ['slug' => 'view-billing',           'name' => 'Billing',                    'description' => 'Access billing and subscription'],
            ['slug' => 'manage-billing',         'name' => 'Manage Billing',             'description' => 'Manage billing and subscriptions'],
            ['slug' => 'manage-loan-products',   'name' => 'Loan Products',              'description' => 'Manage loan products'],
            ['slug' => 'manage-borrowers',       'name' => 'Borrowers',                  'description' => 'Manage borrowers/clients'],
            ['slug' => 'manage-repayments',      'name' => 'Repayments',                 'description' => 'Manage repayments'],
            ['slug' => 'view-reports',           'name' => 'Reports',                    'description' => 'Access reports'],
            ['slug' => 'manage-messages',        'name' => 'Messages',                   'description' => 'Access messaging/SMS features'],
            ['slug' => 'manage-staff',           'name' => 'Staff & Roles',              'description' => 'Manage staff and roles'],
            ['slug' => 'manage-branches',        'name' => 'Branches',                   'description' => 'Manage branches'],
            ['slug' => 'manage-accounts',        'name' => 'Accounts',                   'description' => 'Manage financial accounts'],
            ['slug' => 'access-dashboard',       'name' => 'Dashboard',                  'description' => 'Access to dashboard'],
            ['slug' => 'admin-access',           'name' => 'Admin',                      'description' => 'Full administrative access'],
        ];

        foreach ($tenants as $tenant) {
            // Seed roles
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

            // Seed permissions
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

            // Role -> Permission assignments
            $assignments = [
                'admin' => array_keys($permMap), // admin gets all
                'officer' => ['loan.view', 'loan.stage.decide'],
                'loan_officer' => ['loan.view', 'loan.stage.decide'],
                'manager' => ['loan.view', 'loan.stage.decide', 'loan.update', 'manage-loan-products', 'view-billing', 'manage-billing'],
                'gm' => ['loan.view', 'loan.stage.decide', 'loan.update', 'view-billing', 'manage-billing'],
                'accountant' => ['view-billing', 'manage-billing', 'loan.view', 'loan.disburse'],
                'teller' => ['loan.view', 'loan.disburse'],
                'cashier' => ['loan.view', 'loan.disburse'],
                // Super Admin implicitly has all permissions via middleware/user checks; optional explicit grant
                'superadmin' => array_keys($permMap),
            ];

            foreach ($assignments as $roleSlug => $permSlugs) {
                $role = $roleMap[$roleSlug] ?? null;
                if (!$role) continue;
                foreach ($permSlugs as $ps) {
                    $permission = $permMap[$ps] ?? null;
                    if (!$permission) continue;
                    // Attach with tenant_id in pivot
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

            // Attach existing users to matching roles based on legacy role string
            $users = User::where('tenant_id', $tenant->id)->get();
            foreach ($users as $user) {
                $legacyRole = $user->role; // e.g., 'admin', 'officer', etc.
                $role = $roleMap[$legacyRole] ?? null;
                if ($role) {
                    DB::table('user_role')->updateOrInsert(
                        [
                            'tenant_id' => $tenant->id,
                            'user_id' => $user->id,
                            'role_id' => $role->id,
                        ],
                        [
                            'tenant_id' => $tenant->id,
                            'user_id' => $user->id,
                            'role_id' => $role->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }

                // Ensure admins have all permissions effectively (already covered via role assignments).
            }
        }

        $this->command?->info('RBAC roles and permissions seeded for active tenants.');
    }
}