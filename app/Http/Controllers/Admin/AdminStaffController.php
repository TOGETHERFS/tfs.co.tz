<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AdminRole;
use App\Models\AdminPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminStaffController extends Controller
{
    public function index()
    {
        $staff = User::whereNotNull('admin_role_id')
            ->with('adminRole')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.staff.index', compact('staff'));
    }

    public function create()
    {
        $roles = AdminRole::orderBy('name')->get();
        $permissions = AdminPermission::orderBy('module')->orderBy('name')->get();
        
        // Group permissions by module
        $groupedPermissions = $permissions->groupBy('module');

        return view('admin.staff.create', compact('roles', 'groupedPermissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'admin_role_id' => 'required|exists:admin_roles,id',
            'position' => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'admin_role_id' => $validated['admin_role_id'],
                'position' => $validated['position'] ?? 'Staff',
                'role' => 'staff', // Set a default role
                'tenant_id' => null, // Admin staff don't belong to a tenant
            ]);

            DB::commit();

            return redirect()->route('admin.staff.index')
                ->with('success', 'Staff member created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to create staff member: ' . $e->getMessage());
        }
    }

    public function edit(User $staff)
    {
        // Ensure we're editing an admin staff member
        if (!$staff->admin_role_id) {
            return redirect()->route('admin.staff.index')
                ->with('error', 'Invalid staff member.');
        }

        $roles = AdminRole::orderBy('name')->get();
        $permissions = AdminPermission::orderBy('module')->orderBy('name')->get();
        
        // Group permissions by module
        $groupedPermissions = $permissions->groupBy('module');

        return view('admin.staff.edit', compact('staff', 'roles', 'groupedPermissions'));
    }

    public function update(Request $request, User $staff)
    {
        // Ensure we're updating an admin staff member
        if (!$staff->admin_role_id) {
            return redirect()->route('admin.staff.index')
                ->with('error', 'Invalid staff member.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $staff->id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'admin_role_id' => 'required|exists:admin_roles,id',
            'position' => 'nullable|string|max:100',
        ]);

        DB::beginTransaction();
        try {
            $staff->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'admin_role_id' => $validated['admin_role_id'],
                'position' => $validated['position'] ?? 'Staff',
            ]);

            // Update password if provided
            if (!empty($validated['password'])) {
                $staff->update([
                    'password' => Hash::make($validated['password'])
                ]);
            }

            DB::commit();

            return redirect()->route('admin.staff.index')
                ->with('success', 'Staff member updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to update staff member: ' . $e->getMessage());
        }
    }

    public function destroy(User $staff)
    {
        // Ensure we're deleting an admin staff member
        if (!$staff->admin_role_id) {
            return redirect()->route('admin.staff.index')
                ->with('error', 'Invalid staff member.');
        }

        // Prevent deleting yourself
        if ($staff->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        try {
            $staff->delete();
            return redirect()->route('admin.staff.index')
                ->with('success', 'Staff member deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete staff member: ' . $e->getMessage());
        }
    }

    public function roles()
    {
        $roles = AdminRole::withCount('users')->orderBy('name')->get();
        return view('admin.staff.roles', compact('roles'));
    }
}
