<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        // Bypass for admin and superadmin aliases
        $adminAliases = ['admin', 'administrator'];
        $superAliases = ['super_admin', 'superadmin', 'super-admin', 'super admin'];
        $hasBypass =
            in_array($user->role, $adminAliases) ||
            (method_exists($user, 'hasRole') && $user->hasRole('admin')) ||
            in_array($user->role, $superAliases) ||
            (method_exists($user, 'hasRole') && (
                $user->hasRole('super_admin') ||
                $user->hasRole('superadmin') ||
                $user->hasRole('super-admin') ||
                $user->hasRole('super admin')
            ));

        if ($hasBypass) {
            return $next($request);
        }

        if (method_exists($user, 'hasPermission') && $user->hasPermission($permission)) {
            return $next($request);
        }

        abort(403, 'Unauthorized');
    }
}