<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Support\Pricing;

class EnforceBranchLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Skip if user is not authenticated or has no tenant
        if (!$user || !$user->tenant) {
            return $next($request);
        }

        $tenant = $user->tenant;
        
        // Check if this is a branch creation request
        if ($request->isMethod('POST') && 
            (str_contains($request->route()->getName() ?? '', 'branches.store') || 
             str_contains($request->path(), 'branches'))) {
            
            // Check if tenant can create more branches
            if (!$tenant->canCreateBranch()) {
                $currentPlan = $tenant->getCurrentPlan();
                $planName = $currentPlan['name'] ?? ($tenant->plan_slug ?? 'current');
                $upgradePlans = Pricing::getUpgradeSuggestions($tenant->plan_slug);
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Branch limit exceeded',
                        'message' => "You've reached the branch limit for your {$planName} plan.",
                        'current_plan' => $currentPlan,
                        'upgrade_plans' => $upgradePlans,
                        'upgrade_url' => route('checkout.show')
                    ], 403);
                }
                
                return redirect()->back()->with('error', 
                    "You've reached the branch limit for your {$planName} plan. " .
                    "Please upgrade your plan to create more branches."
                )->with('upgrade_plans', $upgradePlans);
            }
        }

        return $next($request);
    }
}
