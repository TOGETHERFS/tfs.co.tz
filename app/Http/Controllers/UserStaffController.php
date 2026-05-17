<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Subscription;
use App\Services\RbacService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserStaffController extends Controller
{
    /**
     * Check if current user can manage staff
     */
    private function authorizeStaffManagement(): void
    {
        $user = auth()->user();
        $allowedRoles = ['admin', 'administrator', 'manager', 'officer', 'gm', 'general manager', 'ceo', 'hr'];
        $userRole = strtolower($user->role ?? '');
        
        if (!in_array($userRole, $allowedRoles) && !$user->isAdmin() && !$user->isManager()) {
            abort(403, 'Unauthorized. You do not have permission to manage staff.');
        }
    }

    /**
     * Display staff management page for users
     */
    public function index()
    {
        $this->authorizeStaffManagement();
        $tenantId = session('tenant_id');
        
        // Get roles for the current tenant (excluding super admin)
        $roles = Role::where('tenant_id', $tenantId)
            ->where('slug', '!=', 'superadmin')
            ->orderBy('name')
            ->get();
        
        // Get branches for the current tenant
        $branches = Branch::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        // Get staff users for the current tenant with their branch and roles
        // Exclude super admin accounts from tenant staff list
        $staffs = User::where('tenant_id', $tenantId)
            ->where('role', '!=', 'superadmin')
            ->where('email', '!=', 'phidtechnology@gmail.com')
            ->with(['branch', 'roles'])
            ->orderBy('name')
            ->get();

        // Get permissions for the current tenant
        $permissions = Permission::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        // Seed default permissions if none exist (uses RbacService for consistency)
        if ($permissions->isEmpty()) {
            $tenant = \App\Models\Tenant::find($tenantId);
            if ($tenant) {
                RbacService::seedDefaultsForTenant($tenant);
            }
            $permissions = Permission::where('tenant_id', $tenantId)
                ->orderBy('name')
                ->get();
        }

        return view('user.staff.index', compact('roles', 'staffs', 'branches', 'permissions'));
    }

    /**
     * Show form to create a new staff member
     */
    public function create()
    {
        $this->authorizeStaffManagement();
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;
        
        // Get roles for the current tenant (excluding super admin)
        $roles = Role::where('tenant_id', $tenantId)
            ->where('slug', '!=', 'superadmin')
            ->orderBy('name')
            ->get();
        
        // Seed default roles if none exist
        if ($roles->isEmpty()) {
            $this->seedDefaultRoles($tenantId);
            $roles = Role::where('tenant_id', $tenantId)
                ->where('slug', '!=', 'superadmin')
                ->orderBy('name')
                ->get();
        }
        
        // Get branches for the current tenant
        $branches = Branch::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get permissions for the current tenant
        $permissions = Permission::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        // Seed default permissions if none exist (uses RbacService for consistency)
        if ($permissions->isEmpty()) {
            $tenant = \App\Models\Tenant::find($tenantId);
            if ($tenant) {
                RbacService::seedDefaultsForTenant($tenant);
            }
            $permissions = Permission::where('tenant_id', $tenantId)
                ->orderBy('name')
                ->get();
        }

        return view('user.staff.create', compact('roles', 'branches', 'permissions'));
    }

    /**
     * Seed default roles for tenant
     */
    private function seedDefaultRoles($tenantId)
    {
        $defaultRoles = [
            ['name' => 'CEO', 'slug' => 'ceo', 'category' => 'management'],
            ['name' => 'Manager', 'slug' => 'manager', 'category' => 'management'],
            ['name' => 'HR', 'slug' => 'hr', 'category' => 'management'],
            ['name' => 'Loan Officer', 'slug' => 'loan_officer', 'category' => 'operations'],
            ['name' => 'Credit Officer', 'slug' => 'credit_officer', 'category' => 'operations'],
            ['name' => 'Customer Service Officer (CSO)', 'slug' => 'cso', 'category' => 'operations'],
            ['name' => 'Accountant', 'slug' => 'accountant', 'category' => 'finance'],
            ['name' => 'Bursar', 'slug' => 'bursar', 'category' => 'finance'],
            ['name' => 'IT Staff', 'slug' => 'it_staff', 'category' => 'support'],
        ];

        foreach ($defaultRoles as $role) {
            Role::firstOrCreate(
                ['tenant_id' => $tenantId, 'slug' => $role['slug']],
                ['name' => $role['name'], 'category' => $role['category'], 'is_system' => false]
            );
        }
    }

    /**
     * Store a new staff member
     */
    public function store(Request $request)
    {
        $this->authorizeStaffManagement();
        $tenantId = session('tenant_id');
        
        // Get available roles (excluding super admin)
        $availableRoles = Role::where('tenant_id', $tenantId)
            ->where('slug', '!=', 'superadmin')
            ->pluck('name')
            ->toArray();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,id,tenant_id,' . $tenantId],
            'position' => ['nullable', 'string', 'max:120'],
            'branch_id' => ['nullable', 'exists:branches,id,tenant_id,' . $tenantId . ',is_active,1'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id,tenant_id,' . $tenantId],
        ]);

        // Check staff limit if subscription exists
        $subscription = Subscription::where('tenant_id', $tenantId)->with('plan')->first();
        $staffLimit = $subscription && $subscription->plan ? ($subscription->plan->staff_limit ?? null) : null;
        
        if ($staffLimit !== null) {
            $currentStaffCount = User::where('tenant_id', $tenantId)->count();
            if ($currentStaffCount >= $staffLimit) {
                return back()->withErrors(['roles' => 'Staff limit reached for your current plan.']);
            }
        }

        // Get the first role name for the legacy role column
        $firstRole = Role::where('tenant_id', $tenantId)
            ->whereIn('id', $validated['roles'])
            ->first();

        // Create the user
        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => strtolower(trim($validated['email'])),
            'password' => Hash::make($validated['password']),
            'role' => $firstRole ? $firstRole->name : 'staff',
            'position' => $validated['position'] ?? null,
            'branch_id' => !empty($validated['branch_id']) ? $validated['branch_id'] : null,
            'tenant_id' => $tenantId,
            'email_verified_at' => now(), // Auto-verify for staff created by managers
        ]);

        // Attach all selected roles to the user
        foreach ($validated['roles'] as $roleId) {
            $roleModel = Role::find($roleId);
            if ($roleModel) {
                RbacService::attachUserRole($user, $roleModel);
            }
        }

        return redirect()->route('user.staff')->with('success', 'Staff member created successfully!');
    }

    /**
     * Delete a staff member
     */
    public function destroy(User $user)
    {
        $tenantId = session('tenant_id');
        
        // Ensure the user belongs to the current tenant
        if ($user->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized action.');
        }

        // Prevent deletion of super admin
        if ($user->role === 'superadmin') {
            return back()->withErrors(['error' => 'Cannot delete super admin users.']);
        }

        // Prevent users from deleting themselves
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        // Check if user has associated loans
        $loanCount = \App\Models\Loan::where('user_id', $user->id)->count();
        if ($loanCount > 0) {
            return back()->withErrors(['error' => "Cannot delete this staff member. They have {$loanCount} loan(s) associated with their account. Please reassign the loans first or deactivate the user instead."]);
        }

        // Note: Clients are not associated with individual users, only with tenants
        // So we don't need to check for client associations

        $user->delete();

        return back()->with('success', 'Staff member deleted successfully!');
    }

    /**
     * Show edit form for a staff member
     */
    public function edit(User $user)
    {
        $tenantId = session('tenant_id');
        
        // Ensure the user belongs to the current tenant
        if ($user->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized action.');
        }

        // Get roles for the current tenant (excluding super admin)
        $roles = Role::where('tenant_id', $tenantId)
            ->where('slug', '!=', 'superadmin')
            ->orderBy('name')
            ->get();
        
        // Get branches for the current tenant
        $branches = Branch::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get permissions for the current tenant
        $permissions = Permission::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        // Get user's current permissions through their roles
        $userPermissionIds = [];
        foreach ($user->roles as $role) {
            $rolePermissions = $role->permissions()->pluck('permissions.id')->toArray();
            $userPermissionIds = array_merge($userPermissionIds, $rolePermissions);
        }
        $userPermissionIds = array_unique($userPermissionIds);

        return view('user.staff.edit', compact('user', 'roles', 'branches', 'permissions', 'userPermissionIds'));
    }

    /**
     * Update a staff member
     */
    public function update(Request $request, User $user)
    {
        $tenantId = session('tenant_id');
        
        // Ensure the user belongs to the current tenant
        if ($user->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized action.');
        }

        // Prevent editing super admin
        if ($user->role === 'superadmin') {
            return back()->withErrors(['error' => 'Cannot edit super admin users.']);
        }

        // Get available roles (excluding super admin)
        $availableRoles = Role::where('tenant_id', $tenantId)
            ->where('slug', '!=', 'superadmin')
            ->pluck('name')
            ->toArray();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', Rule::in($availableRoles)],
            'position' => ['nullable', 'string', 'max:120'],
            'branch_id' => ['nullable', 'exists:branches,id,tenant_id,' . $tenantId . ',is_active,1'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id,tenant_id,' . $tenantId],
            'is_active' => ['nullable', 'in:0,1'],
        ]);

        // Update user details
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->position = $validated['position'] ?? null;
        $user->branch_id = !empty($validated['branch_id']) ? $validated['branch_id'] : null;
        
        // Handle deactivation
        if ($request->has('is_active')) {
            $user->is_active = $request->input('is_active') == '0' ? 0 : 1;
        }
        
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        
        $user->save();

        // Update role assignment
        $roleModel = Role::where('tenant_id', $tenantId)
            ->where('name', $validated['role'])
            ->first();
        
        if ($roleModel) {
            // Detach all current roles and attach the new one
            $user->roles()->detach();
            RbacService::attachUserRole($user, $roleModel);

            // Update role permissions based on selected permissions
            if (isset($validated['permissions'])) {
                // Build sync array with tenant_id for pivot table
                $syncData = [];
                foreach ($validated['permissions'] as $permissionId) {
                    $syncData[$permissionId] = ['tenant_id' => $tenantId];
                }
                $roleModel->permissions()->sync($syncData);
            }
        }

        return redirect()->route('user.staff')->with('success', 'Staff member updated successfully!');
    }
}