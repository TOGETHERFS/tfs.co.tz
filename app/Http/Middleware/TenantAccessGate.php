<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantAccessGate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        // Skip all tenant checks for true superadmins only (system-level, no tenant_id)
        $superAliases = ['super_admin', 'superadmin', 'super-admin', 'super admin'];
        $isSuperAdmin = in_array(strtolower($user->role ?? ''), $superAliases) || 
                       strtolower($user->position ?? '') === 'superadmin';

        if ($isSuperAdmin) {
            return $next($request);
        }

        // Skip checks for system-level admin staff (admin_role_id set AND no tenant)
        if ($user->admin_role_id && !$user->tenant_id) {
            return $next($request);
        }
        
        // Allow tenant admins with admin_role_id to access admin routes
        if ($user->admin_role_id && $user->tenant_id) {
            return $next($request);
        }
        
        $sessionTenantId = session('tenant_id');

        // If no tenant in session, try to resolve from user's tenant
        if (!$sessionTenantId) {
            // Try to get tenant from user
            if ($user->tenant_id) {
                $sessionTenantId = $user->tenant_id;
                session(['tenant_id' => $sessionTenantId]);
            } else {
                // User has no tenant - abort instead of redirect to prevent loop
                abort(403, 'No tenant associated with your account. Please contact support.');
            }
        }

        // Check if user belongs to the current tenant (loose comparison to handle string/int mismatch)
        if ((int)$user->tenant_id !== (int)$sessionTenantId) {
            // User doesn't belong to current tenant
            auth()->logout();
            session()->flush();
            
            return redirect()->route('login')
                           ->with('error', 'Access denied. You do not have permission to access this tenant.');
        }

        // Check if tenant is active
        $tenant = $user->tenant;
        if (!$tenant || $tenant->status !== 'active') {
            auth()->logout();
            session()->flush();
            
            return redirect()->route('login')
                           ->with('error', 'Tenant account is not active.');
        }

        // Skip subscription check for subscription-related routes to prevent redirect loops
        if ($this->shouldSkipSubscriptionCheck($request)) {
            return $next($request);
        }

        // Check subscription status
        $valid = $this->hasValidSubscription($tenant);
        \Log::info('TenantAccessGate subscription check', [
            'tenant_id'    => $tenant->id,
            'tenant_name'  => $tenant->name,
            'trial_ends'   => $tenant->trial_ends_at,
            'is_on_trial'  => $tenant->isOnTrial(),
            'valid'        => $valid,
            'route'        => $request->route()?->getName(),
            'sub_count'    => $tenant->subscriptions()->count(),
            'valid_sub'    => $tenant->subscriptions()->where('current_period_end', '>', now())->count(),
        ]);

        if (!$valid) {
            return redirect()->route('subscription.expired')
                           ->with('warning', 'Your subscription has expired. Please renew to continue.');
        }

        return $next($request);
    }

    /**
     * Check if subscription check should be skipped for this request.
     */
    private function shouldSkipSubscriptionCheck(Request $request): bool
    {
        $skipRoutes = [
            'subscription.expired',
            'subscribe.*',
            'billing.pay',
            'billing.subscription',
            'billing.invoices.*',
            'logout',
        ];

        foreach ($skipRoutes as $pattern) {
            if ($request->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if tenant has valid subscription.
     * Single-tenant mode: always valid.
     */
    private function hasValidSubscription($tenant): bool
    {
        return true;
    }
}