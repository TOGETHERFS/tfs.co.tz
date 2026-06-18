<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip tenant resolution for certain routes
        if ($this->shouldSkipTenantResolution($request)) {
            return $next($request);
        }

        $tenant = null;
        $hasSession = method_exists($request, 'hasSession') && $request->hasSession();

        // Try to resolve tenant from subdomain
        $host = $request->getHost();
        $subdomain = $this->extractSubdomain($host);

        if ($subdomain && $subdomain !== 'www') {
            $tenant = Tenant::where('slug', $subdomain)
                           ->where('status', 'active')
                           ->first();
        }

        // If no tenant found from subdomain, try from session (only if available)
        if (!$tenant && $hasSession && session()->has('tenant_id')) {
            $tenant = Tenant::find(session('tenant_id'));
            // Ensure active tenant only
            if ($tenant && $tenant->status !== 'active') {
                $tenant = null;
            }
        }

        // Fallback: if authenticated, use the user's associated tenant
        if (!$tenant && auth()->check()) {
            $userTenant = auth()->user()->tenant ?? null;
            if ($userTenant && $userTenant->status === 'active') {
                $tenant = $userTenant;
            }
        }

        // If tenant found, set in session (only if session exists)
        if ($tenant && $hasSession) {
            session([
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'tenant_slug' => $tenant->slug,
            ]);

            // Share tenant data with views
            view()->share('currentTenant', $tenant);
        } elseif ($hasSession) {
            // No active tenant found — fall back to auth user's tenant_id so session stays populated
            if (auth()->check() && auth()->user()->tenant_id) {
                session(['tenant_id' => auth()->user()->tenant_id]);
            } else {
                session()->forget(['tenant_id', 'tenant_name', 'tenant_slug']);
            }
        }

        return $next($request);
    }

    /**
     * Extract subdomain from host, correctly handling 2-level TLDs like .co.tz, .co.uk, .co.ke.
     */
    private function extractSubdomain(string $host): ?string
    {
        // Strip port if present
        $host = strtolower(explode(':', $host)[0]);

        // Known 2-level TLDs — domains using these need 4 parts to have a subdomain
        $twoLevelTlds = ['co.tz', 'co.uk', 'co.ke', 'co.za', 'com.au', 'org.uk', 'net.au', 'or.tz'];

        foreach ($twoLevelTlds as $tld) {
            if (str_ends_with($host, '.' . $tld)) {
                // e.g. tfs.co.tz → base='tfs', no subdomain
                // e.g. app.tfs.co.tz → subdomain='app'
                $withoutTld = substr($host, 0, strlen($host) - strlen($tld) - 1);
                $parts = explode('.', $withoutTld);
                return count($parts) > 1 ? $parts[0] : null;
            }
        }

        // Standard TLDs: subdomain.domain.tld = 3 parts
        $parts = explode('.', $host);
        if (count($parts) > 2) {
            return $parts[0];
        }
        return null;
    }

    /**
     * Check if tenant resolution should be skipped for this request.
     */
    private function shouldSkipTenantResolution(Request $request): bool
    {
        $skipRoutes = [
            'api/webhooks/*',
            'health-check',
            'login',
            'register',
            'password/*',
        ];

        foreach ($skipRoutes as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        // Keep super admin/admin in admin context on billing routes
        if (auth()->check()) {
            $user = auth()->user();
            $isAdminLike = (
                ($user->role === 'admin') ||
                (method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('superadmin'))) ||
                (method_exists($user, 'hasPermission') && $user->hasPermission('manage-billing'))
            );

            if ($isAdminLike) {
                $path = trim($request->path(), '/');
                $name = $request->route() ? $request->route()->getName() : null;
                if (str_starts_with($path, 'billing') || (is_string($name) && str_starts_with($name, 'billing.'))) {
                    return true;
                }
            }
        }

        return false;
    }
}