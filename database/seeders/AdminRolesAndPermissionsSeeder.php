<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminRolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Define admin permissions based on sidebar menu items
        $permissions = [
            // Dashboard
            ['name' => 'View Dashboard', 'slug' => 'view-dashboard', 'module' => 'dashboard'],
            
            // User Management
            ['name' => 'View Users', 'slug' => 'view-users', 'module' => 'users'],
            ['name' => 'Create Users', 'slug' => 'create-users', 'module' => 'users'],
            ['name' => 'Edit Users', 'slug' => 'edit-users', 'module' => 'users'],
            ['name' => 'Delete Users', 'slug' => 'delete-users', 'module' => 'users'],
            
            // System Settings
            ['name' => 'View Settings', 'slug' => 'view-settings', 'module' => 'settings'],
            ['name' => 'Edit Settings', 'slug' => 'edit-settings', 'module' => 'settings'],
            
            // Messages/SMS
            ['name' => 'View Messages', 'slug' => 'view-messages', 'module' => 'messages'],
            ['name' => 'Send Messages', 'slug' => 'send-messages', 'module' => 'messages'],
            ['name' => 'Manage SMS Credits', 'slug' => 'manage-sms-credits', 'module' => 'messages'],
            
            // Reports & Analytics
            ['name' => 'View Reports', 'slug' => 'view-reports', 'module' => 'reports'],
            ['name' => 'Export Reports', 'slug' => 'export-reports', 'module' => 'reports'],
            
            // Billing & Subscription
            ['name' => 'View Billing', 'slug' => 'view-billing', 'module' => 'billing'],
            ['name' => 'Manage Subscriptions', 'slug' => 'manage-subscriptions', 'module' => 'billing'],
            ['name' => 'View Payments', 'slug' => 'view-payments', 'module' => 'billing'],
            
            // System Monitoring
            ['name' => 'View System Monitoring', 'slug' => 'view-monitoring', 'module' => 'monitoring'],
            
            // Staff Management
            ['name' => 'View Staff', 'slug' => 'view-staff', 'module' => 'staff'],
            ['name' => 'Create Staff', 'slug' => 'create-staff', 'module' => 'staff'],
            ['name' => 'Edit Staff', 'slug' => 'edit-staff', 'module' => 'staff'],
            ['name' => 'Delete Staff', 'slug' => 'delete-staff', 'module' => 'staff'],
            ['name' => 'Manage Roles', 'slug' => 'manage-roles', 'module' => 'staff'],
        ];

        // Insert or update permissions
        foreach ($permissions as $permission) {
            DB::table('admin_permissions')->updateOrInsert(
                ['slug' => $permission['slug']],
                [
                    'name' => $permission['name'],
                    'module' => $permission['module'],
                    'description' => $permission['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Define admin roles
        $roles = [
            [
                'name' => 'CEO',
                'slug' => 'ceo',
                'description' => 'Chief Executive Officer - Full system access',
                'permissions' => ['*'] // All permissions
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'System Administrator - Full system access',
                'permissions' => ['*'] // All permissions
            ],
            [
                'name' => 'Manager',
                'slug' => 'manager',
                'description' => 'Manager - Access to most features',
                'permissions' => [
                    'view-dashboard', 'view-users', 'view-messages', 'send-messages',
                    'view-reports', 'export-reports', 'view-billing', 'view-staff'
                ]
            ],
            [
                'name' => 'Accountant',
                'slug' => 'accountant',
                'description' => 'Accountant - Financial and billing access',
                'permissions' => [
                    'view-dashboard', 'view-billing', 'manage-subscriptions', 'view-payments',
                    'view-reports', 'export-reports'
                ]
            ],
            [
                'name' => 'IT Technician',
                'slug' => 'it-technician',
                'description' => 'IT Technician - Technical system access',
                'permissions' => [
                    'view-dashboard', 'view-settings', 'edit-settings', 'view-monitoring',
                    'view-users', 'view-reports'
                ]
            ],
            [
                'name' => 'Marketing Manager',
                'slug' => 'marketing-manager',
                'description' => 'Marketing Manager - Marketing and messaging access',
                'permissions' => [
                    'view-dashboard', 'view-messages', 'send-messages', 'manage-sms-credits',
                    'view-reports', 'export-reports', 'view-users'
                ]
            ],
            [
                'name' => 'Sales Manager',
                'slug' => 'sales-manager',
                'description' => 'Sales Manager - Sales and customer access',
                'permissions' => [
                    'view-dashboard', 'view-users', 'view-messages', 'send-messages',
                    'view-reports', 'export-reports', 'view-billing'
                ]
            ],
        ];

        // Insert or update roles and assign permissions
        foreach ($roles as $roleData) {
            // Check if role exists
            $existingRole = DB::table('admin_roles')->where('slug', $roleData['slug'])->first();
            
            if ($existingRole) {
                // Update existing role
                DB::table('admin_roles')->where('id', $existingRole->id)->update([
                    'name' => $roleData['name'],
                    'description' => $roleData['description'],
                    'updated_at' => now(),
                ]);
                $roleId = $existingRole->id;
                
                // Clear existing permissions for this role
                DB::table('admin_role_permission')->where('admin_role_id', $roleId)->delete();
            } else {
                // Insert new role
                $roleId = DB::table('admin_roles')->insertGetId([
                    'name' => $roleData['name'],
                    'slug' => $roleData['slug'],
                    'description' => $roleData['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Assign permissions to role
            if ($roleData['permissions'][0] === '*') {
                // Assign all permissions
                $allPermissions = DB::table('admin_permissions')->pluck('id');
                foreach ($allPermissions as $permissionId) {
                    DB::table('admin_role_permission')->insert([
                        'admin_role_id' => $roleId,
                        'admin_permission_id' => $permissionId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } else {
                // Assign specific permissions
                foreach ($roleData['permissions'] as $permissionSlug) {
                    $permission = DB::table('admin_permissions')->where('slug', $permissionSlug)->first();
                    if ($permission) {
                        DB::table('admin_role_permission')->insert([
                            'admin_role_id' => $roleId,
                            'admin_permission_id' => $permission->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }
}
