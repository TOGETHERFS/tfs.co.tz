<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Notifications\RoleAssignmentNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class UserRoleController extends Controller
{
    /**
     * Display role assignments for the current user
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get current user's roles
        $currentRoles = $user->roles()->get();
        
        // Get pending role assignments for this user
        $pendingAssignments = RoleAssignment::with(['role', 'requestedBy'])
            ->where('user_id', $user->id)
            ->pending()
            ->latest()
            ->get();
        
        // Get approved role assignments for this user
        $approvedAssignments = RoleAssignment::with(['role', 'requestedBy', 'approvedBy'])
            ->where('user_id', $user->id)
            ->approved()
            ->latest()
            ->take(10)
            ->get();
        
        return view('user.roles.index', compact('currentRoles', 'pendingAssignments', 'approvedAssignments'));
    }

    /**
     * Show form to assign role to a user (Admin only)
     */
    public function create(Request $request)
    {
        // Check if user is admin
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Only administrators can assign roles.');
        }

        $users = User::where('tenant_id', session('tenant_id'))
            ->where('id', '!=', Auth::id())
            ->orderBy('name')
            ->get();
            
        $roles = Role::where('tenant_id', session('tenant_id'))
            ->where('name', '!=', 'super admin')
            ->orderBy('name')
            ->get();

        $selectedUserId = $request->get('user_id');

        return view('user.roles.create', compact('users', 'roles', 'selectedUserId'));
    }

    /**
     * Store a role assignment request (Admin only)
     */
    public function store(Request $request)
    {
        // Check if user is admin
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Only administrators can assign roles.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
            'reason' => 'required|string|max:500',
        ]);

        // Check if user already has this role
        $user = User::findOrFail($request->user_id);
        if ($user->roles()->where('role_id', $request->role_id)->exists()) {
            return back()->withErrors(['role_id' => 'User already has this role.']);
        }

        // Check if there's already a pending assignment for this user and role
        $existingAssignment = RoleAssignment::where('user_id', $request->user_id)
            ->where('role_id', $request->role_id)
            ->pending()
            ->first();

        if ($existingAssignment) {
            return back()->withErrors(['role_id' => 'There is already a pending assignment for this role.']);
        }

        $assignment = RoleAssignment::create([
            'tenant_id' => session('tenant_id'),
            'user_id' => $request->user_id,
            'role_id' => $request->role_id,
            'requested_by' => Auth::id(),
            'reason' => $request->reason,
        ]);

        // Send notification to super admins about the new role assignment request
        $superAdmins = User::where('tenant_id', session('tenant_id'))
            ->where(function ($query) {
                $query->whereHas('roles', function ($q) {
                    $q->where('name', 'super_admin');
                })->orWhere('role', 'super_admin');
            })
            ->get();

        foreach ($superAdmins as $superAdmin) {
            $superAdmin->notify(new RoleAssignmentNotification($assignment, 'requested'));
        }

        return redirect()->route('user.roles.pending')
            ->with('success', 'Role assignment request submitted successfully. Awaiting super admin approval.');
    }

    /**
     * Show pending role assignments for approval (Super Admin only)
     */
    public function pending()
    {
        $user = Auth::user();
        
        // For super admin, show all pending assignments
        if ($user->hasRole('super_admin') || $user->role === 'super_admin') {
            $pendingAssignments = RoleAssignment::with(['user', 'role', 'requestedBy'])
                ->where('tenant_id', session('tenant_id'))
                ->pending()
                ->latest()
                ->paginate(15);
        } 
        // For admin, show assignments they requested
        elseif ($user->isAdmin()) {
            $pendingAssignments = RoleAssignment::with(['user', 'role'])
                ->where('tenant_id', session('tenant_id'))
                ->where('requested_by', $user->id)
                ->pending()
                ->latest()
                ->paginate(15);
        } 
        else {
            abort(403, 'Access denied.');
        }

        return view('user.roles.pending', compact('pendingAssignments'));
    }

    /**
     * Approve a role assignment (Super Admin only)
     */
    public function approve(Request $request, RoleAssignment $roleAssignment)
    {
        $user = Auth::user();
        
        if (!$user->hasRole('super_admin') && $user->role !== 'super_admin') {
            abort(403, 'Only super administrators can approve role assignments.');
        }

        if (!$roleAssignment->isPending()) {
            return back()->withErrors(['error' => 'This assignment has already been processed.']);
        }

        $roleAssignment->approve($user, $request->get('approval_reason'));

        return back()->with('success', 'Role assignment approved successfully.');
    }

    /**
     * Reject a role assignment (Super Admin only)
     */
    public function reject(Request $request, RoleAssignment $roleAssignment)
    {
        $user = Auth::user();
        
        if (!$user->hasRole('super_admin') && $user->role !== 'super_admin') {
            abort(403, 'Only super administrators can reject role assignments.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        if (!$roleAssignment->isPending()) {
            return back()->withErrors(['error' => 'This assignment has already been processed.']);
        }

        $roleAssignment->reject($user, $request->rejection_reason);

        return back()->with('success', 'Role assignment rejected.');
    }

    /**
     * Remove a role from a user (Admin only)
     */
    public function removeRole(Request $request, User $user, Role $role)
    {
        if (!Auth::user()->isAdmin()) {
            abort(403, 'Only administrators can remove roles.');
        }

        // Prevent removing roles from super admin
        if ($user->hasRole('super_admin') || $user->role === 'super_admin') {
            return back()->withErrors(['error' => 'Cannot remove roles from super administrator.']);
        }

        $user->roles()->detach($role->id);

        return back()->with('success', 'Role removed successfully.');
    }
}
