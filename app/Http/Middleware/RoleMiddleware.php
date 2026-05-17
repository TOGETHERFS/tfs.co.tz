<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Check if user is admin staff member (has admin_role_id)
        $isAdminStaff = !empty($user->admin_role_id);
        
        // Check if user is superadmin (only check role and position, NOT tenant-level admin)
        $superAliases = ['super_admin', 'superadmin', 'super-admin', 'super admin'];
        $hasSuperAdmin = in_array(strtolower($user->role ?? ''), $superAliases)
            || strtolower($user->position ?? '') === 'superadmin'
            || (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin());
        
        // Check if user is tenant admin (role: admin)
        $isTenantAdmin = strtolower($user->role ?? '') === 'admin';

        // Super admin, admin staff, and tenant admin pass all role checks
        if ($hasSuperAdmin || $isAdminStaff || $isTenantAdmin) {
            return $next($request);
        }

        // If a user matches any of the required roles, allow
        foreach ($roles as $role) {
            if ($user->role === $role || (method_exists($user, 'hasRole') && $user->hasRole($role))) {
                return $next($request);
            }
            // Allow super admin when admin is required
            if ($role === 'admin' && $hasSuperAdmin) {
                return $next($request);
            }
        }

        // Fallback: redirect based on role
        // If none of the requested roles matched, redirect appropriately
        if ($hasSuperAdmin) {
            return redirect()->route('admin.dashboard');
        }
        
        // Debug logging before abort
        \Log::error('RoleMiddleware 403', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_role' => $user->role,
            'user_admin_role_id' => $user->admin_role_id,
            'user_tenant_id' => $user->tenant_id,
            'required_roles' => $roles,
            'is_admin_staff' => $isAdminStaff,
            'is_super_admin' => $hasSuperAdmin,
            'is_tenant_admin' => $isTenantAdmin,
            'route' => request()->route()?->getName(),
        ]);
        
        // Abort with 403 instead of redirecting to prevent redirect loops
        abort(403, 'Unauthorized. You do not have the required role to access this resource.');
    }
}