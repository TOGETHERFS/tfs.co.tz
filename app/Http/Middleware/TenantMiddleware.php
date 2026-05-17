<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
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

        // Get tenant from subdomain or header
        $tenant = $this->resolveTenant($request);

        if ($tenant) {
            // Set the tenant context
            app()->instance('tenant', $tenant);
            
            // Switch database connection if needed
            $this->switchTenantDatabase($tenant);
            
            // Set tenant-specific configuration
            $this->setTenantConfiguration($tenant);
        }

        return $next($request);
    }

    /**
     * Determine if tenant resolution should be skipped for this request.
     */
    protected function shouldSkipTenantResolution(Request $request): bool
    {
        $skipRoutes = [
            'health',
            'webhooks.*',
            'api/public/*',
        ];

        foreach ($skipRoutes as $pattern) {
            if ($request->routeIs($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the tenant from the request.
     */
    protected function resolveTenant(Request $request)
    {
        // Try to get tenant from subdomain
        $host = $request->getHost();
        $subdomain = explode('.', $host)[0];

        if ($subdomain && $subdomain !== 'www') {
            $tenant = \App\Models\Tenant::where('subdomain', $subdomain)->first();
            if ($tenant) {
                return $tenant;
            }
        }

        // Try to get tenant from header (for API requests)
        $tenantId = $request->header('X-Tenant-ID');
        if ($tenantId) {
            return \App\Models\Tenant::find($tenantId);
        }

        // Try to get tenant from authenticated user
        if ($request->user()) {
            return $request->user()->tenant;
        }

        return null;
    }

    /**
     * Switch to the tenant's database.
     */
    protected function switchTenantDatabase($tenant): void
    {
        if ($tenant->database_name) {
            config(['database.connections.tenant.database' => $tenant->database_name]);
            DB::purge('tenant');
            DB::reconnect('tenant');
        }
    }

    /**
     * Set tenant-specific configuration.
     */
    protected function setTenantConfiguration($tenant): void
    {
        // Set tenant-specific mail configuration
        if ($tenant->mail_settings) {
            $mailSettings = json_decode($tenant->mail_settings, true);
            config(['mail.mailers.smtp' => array_merge(
                config('mail.mailers.smtp'),
                $mailSettings
            )]);
        }

        // Set tenant-specific app name
        if ($tenant->name) {
            config(['app.name' => $tenant->name]);
        }

        // Set tenant-specific timezone
        if ($tenant->timezone) {
            config(['app.timezone' => $tenant->timezone]);
        }

        // Set tenant-specific currency
        if ($tenant->currency) {
            config(['app.currency' => $tenant->currency]);
        }
    }
}