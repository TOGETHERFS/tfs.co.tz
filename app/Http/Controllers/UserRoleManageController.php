<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserRoleManageController extends Controller
{
    /**
     * Predefined microfinance roles
     */
    protected $predefinedRoles = [
        'ceo' => [
            'name' => 'CEO',
            'description' => 'Chief Executive Officer with full organizational oversight and decision-making authority',
            'category' => 'management'
        ],
        'manager' => [
            'name' => 'Manager',
            'description' => 'Branch or regional manager with oversight capabilities',
            'category' => 'management'
        ],
        'hr' => [
            'name' => 'HR',
            'description' => 'Human Resources - manages staff records, recruitment and employee relations',
            'category' => 'management'
        ],
        'loan_officer' => [
            'name' => 'Loan Officer',
            'description' => 'Handles loan applications, disbursements and client relationships',
            'category' => 'operations'
        ],
        'credit_officer' => [
            'name' => 'Credit Officer',
            'description' => 'Evaluates loan applications and performs credit assessments',
            'category' => 'operations'
        ],
        'cso' => [
            'name' => 'Customer Service Officer (CSO)',
            'description' => 'Handles client inquiries, support and customer relations',
            'category' => 'operations'
        ],
        'accountant' => [
            'name' => 'Accountant',
            'description' => 'Manages financial records, reports and transactions',
            'category' => 'finance'
        ],
        'bursar' => [
            'name' => 'Bursar',
            'description' => 'Manages cash, payments and financial disbursements',
            'category' => 'finance'
        ],
        'it_staff' => [
            'name' => 'IT Staff',
            'description' => 'Provides technical support and system maintenance',
            'category' => 'support'
        ],
    ];

    /**
     * Display all roles for the tenant
     */
    public function index()
    {
        $tenantId = session('tenant_id');
        
        $roles = Role::where('tenant_id', $tenantId)
            ->where('slug', '!=', 'superadmin')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return view('user.roles.manage', compact('roles'));
    }

    /**
     * Show form to create a new role
     */
    public function create()
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;
        
        // Get existing role slugs to filter predefined list
        $existingRoleSlugs = [];
        if ($tenantId) {
            $existingRoleSlugs = Role::where('tenant_id', $tenantId)
                ->pluck('slug')
                ->toArray();
        }
        
        // Filter out already created roles from predefined list
        $availablePredefinedRoles = collect($this->predefinedRoles)
            ->filter(function ($role, $slug) use ($existingRoleSlugs) {
                return !in_array($slug, $existingRoleSlugs);
            })
            ->toArray();

        // Get permissions for the tenant (ensure collection, not null)
        $permissions = collect([]);
        if ($tenantId) {
            $permissions = Permission::where('tenant_id', $tenantId)
                ->orderBy('name')
                ->get();
        }

        return view('user.roles.create', compact('availablePredefinedRoles', 'permissions'));
    }

    /**
     * Store a new role
     */
    public function store(Request $request)
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;

        $validated = $request->validate([
            'predefined_role' => 'nullable|string',
            'custom_name' => 'nullable|string|max:255',
            'custom_description' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:50',
            'permissions' => 'nullable|array',
        ], [
            'predefined_role.string' => 'Please select a valid role.',
            'custom_name.string' => 'Role name must be valid text.',
            'custom_name.max' => 'Role name cannot exceed 255 characters.',
        ]);

        // Validate that either predefined or custom role is provided
        if (empty($validated['predefined_role']) && empty($validated['custom_name'])) {
            return back()->withErrors(['custom_name' => 'Please select a predefined role or enter a custom role name.'])->withInput();
        }

        // Determine if using predefined or custom role
        if (!empty($validated['predefined_role']) && isset($this->predefinedRoles[$validated['predefined_role']])) {
            $predefined = $this->predefinedRoles[$validated['predefined_role']];
            $name = $predefined['name'];
            $slug = $validated['predefined_role'];
            $description = $predefined['description'];
            $category = $predefined['category'];
        } else {
            // Custom role
            if (empty($validated['custom_name'])) {
                return back()->withErrors(['custom_name' => 'Please select a predefined role or enter a custom role name.']);
            }
            $name = $validated['custom_name'];
            $slug = Str::slug($name);
            $description = $validated['custom_description'] ?? '';
            $category = $validated['category'] ?? 'custom';
        }

        // Check if role already exists
        $existingRole = Role::where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->first();

        if ($existingRole) {
            return back()->withErrors(['predefined_role' => 'This role already exists.']);
        }

        // Create the role
        $role = Role::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'slug' => $slug,
            'category' => $category,
            'is_system' => false,
        ]);

        // Attach permissions if provided
        if (!empty($validated['permissions'])) {
            $role->permissions()->attach($validated['permissions']);
        }

        return redirect()->route('user.roles.manage')
            ->with('success', "Role '{$name}' created successfully!");
    }

    /**
     * Delete a role
     */
    public function destroy(Role $role)
    {
        $tenantId = session('tenant_id');
        
        // Ensure role belongs to tenant
        if ($role->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized action.');
        }

        // Prevent deletion of system roles
        if ($role->is_system) {
            return back()->withErrors(['error' => 'Cannot delete system roles.']);
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete role with assigned users. Please reassign users first.']);
        }

        $roleName = $role->name;
        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('user.roles.manage')
            ->with('success', "Role '{$roleName}' deleted successfully!");
    }
}
