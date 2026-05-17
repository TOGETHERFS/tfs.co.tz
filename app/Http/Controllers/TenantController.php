<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Sms\SmsSenderId;
use App\Services\RbacService;
use App\Services\TenantOnboardingService;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class TenantController extends Controller
{
    /**
     * Resolve the current tenant from session or authenticated user.
     */
    protected function resolveTenant(): ?Tenant
    {
        $tenantId = session('tenant_id') ?? optional(session('tenant'))->id ?? optional(auth()->user())->tenant_id;
        
        if ($tenantId) {
            return Tenant::find($tenantId);
        }
        
        return null;
    }

    /**
     * Display tenant settings.
     */
    public function settings()
    {
        $tenant = $this->resolveTenant();
        if ($tenant) {
            $tenant->load('plan');
        }

        $senderIds = $tenant
            ? SmsSenderId::where('tenant_id', $tenant->id)->where('is_active', true)->get()
            : collect();

        $defaultSenderId = $tenant
            ? SmsSenderId::getDefaultForTenant($tenant->id)
            : null;

        return view('tenant.settings', compact('tenant', 'senderIds', 'defaultSenderId'));
    }

    /**
     * Update tenant settings.
     */
    public function updateSettings(Request $request)
    {
        $tenant = $this->resolveTenant();

        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'phone'         => 'nullable|string|max:20',
            'sender_id'     => 'nullable|string|max:20',
        ]);

        $tenant->update([
            'name'          => $validated['name'],
            'contact_email' => $validated['contact_email'],
            'phone'         => $validated['phone'] ?? null,
        ]);

        // Save/update the tenant's default SMS Sender ID
        if (!empty($validated['sender_id'])) {
            // Clear existing default flag for this tenant
            SmsSenderId::where('tenant_id', $tenant->id)->update(['is_default' => false]);

            SmsSenderId::updateOrCreate(
                ['tenant_id' => $tenant->id, 'sender_id' => strtoupper(trim($validated['sender_id']))],
                [
                    'is_default'      => true,
                    'is_active'       => true,
                    'provider_status' => 'approved',
                ]
            );
        }

        return back()->with('success', 'Settings updated successfully.');
    }

    /**
     * Display user management.
     */
    public function users()
    {
        $tenant = session('tenant');
        
        $users = User::where('tenant_id', $tenant->id)
                    ->latest()
                    ->paginate(15);

        return view('tenant.users', compact('users'));
    }

    /**
     * Show form for creating a new user.
     */
    public function createUser()
    {
        return view('tenant.create-user');
    }

    /**
     * Store a new user.
     */
    public function storeUser(Request $request)
    {
        $tenant = session('tenant');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email'
            ],
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,manager,gm,loan_officer,officer,credit_officer,accountant,teller,cashier,staff',
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'email_verified_at' => now(),
        ]);

        // Assign the corresponding RBAC role in the user_role pivot table
        $this->syncUserRbacRole($user, $validated['role']);

        return redirect()->route('tenant.users')
                        ->with('success', 'User created successfully.');
    }

    /**
     * Show form for editing a user.
     */
    public function editUser(User $user)
    {
        $this->authorize('update', $user);
        
        return view('tenant.edit-user', compact('user'));
    }

    /**
     * Update a user.
     */
    public function updateUser(Request $request, User $user)
    {
        $this->authorize('update', $user);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $user->id
            ],
            'role' => 'required|in:admin,manager,gm,loan_officer,officer,credit_officer,accountant,teller,cashier,staff',
        ]);

        $user->update($validated);

        // Sync the corresponding RBAC role in the user_role pivot table
        $this->syncUserRbacRole($user, $validated['role']);

        return redirect()->route('tenant.users')
                        ->with('success', 'User updated successfully.');
    }

    /**
     * Sync the RBAC role for a user based on their role column value.
     * Ensures the user has the matching role in the user_role pivot table
     * so that permission checks work correctly.
     */
    private function syncUserRbacRole(User $user, string $roleSlug): void
    {
        $tenantId = $user->tenant_id;

        // Map role column values to RBAC role slugs
        $roleSlugMap = [
            'administrator' => 'admin',
            'credit_officer' => 'loan_officer',
            'cashier' => 'accountant',
            'staff' => 'officer',
        ];
        $rbacSlug = $roleSlugMap[$roleSlug] ?? $roleSlug;

        // Find the RBAC role for this tenant
        $rbacRole = Role::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('slug', $rbacSlug)
            ->first();

        if (!$rbacRole) {
            // If RBAC roles haven't been seeded for this tenant, seed them now
            $tenant = Tenant::find($tenantId);
            if ($tenant) {
                $seed = RbacService::seedDefaultsForTenant($tenant);
                $rbacRole = $seed['roles'][$rbacSlug] ?? null;
            }
        }

        if ($rbacRole) {
            // Remove all existing roles for this user in this tenant, then assign the new one
            DB::table('user_role')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $user->id)
                ->delete();

            RbacService::attachUserRole($user, $rbacRole);
        }
    }

    /**
     * Delete a user.
     */
    public function deleteUser(User $user)
    {
        $this->authorize('delete', $user);
        
        // Prevent deleting the last admin
        if ($user->isAdmin()) {
            $adminCount = User::where('tenant_id', $user->tenant_id)
                             ->where('role', 'admin')
                             ->count();
            
            if ($adminCount <= 1) {
                return back()->with('error', 'Cannot delete the last admin user.');
            }
        }

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }

    /**
     * Reset user password.
     */
    public function resetUserPassword(Request $request, User $user)
    {
        $this->authorize('update', $user);
        
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password reset successfully.');
    }

    /**
     * Display tenant dashboard/overview.
     */
    public function dashboard()
    {
        $tenant = $this->resolveTenant();
        
        // Get basic statistics
        $activeLoans = \App\Models\Loan::whereIn('status', ['disbursed', 'active'])->get();
        $totalOutstanding = $activeLoans->sum(function ($loan) {
            return $loan->outstanding_balance;
        });
        
        $stats = [
            'total_users' => User::where('tenant_id', $tenant->id)->count(),
            'total_clients' => \App\Models\Client::count(),
            'active_loans' => $activeLoans->count(),
            'total_outstanding' => $totalOutstanding,
        ];

        // Get recent activities
        $recentLoans = \App\Models\Loan::with('client')
                                      ->latest()
                                      ->take(5)
                                      ->get();

        $recentRepayments = \App\Models\Repayment::with(['loan.client'])
                                                ->latest()
                                                ->take(5)
                                                ->get();

        // Get subscription info
        $subscription = Subscription::where('tenant_id', $tenant->id)
                                   ->with('plan')
                                   ->first();

        return view('tenant.dashboard', compact('stats', 'recentLoans', 'recentRepayments', 'subscription'));
    }

    /**
     * Show tenant registration form.
     */
    public function showRegistration(Request $request)
    {
        $allowedCodes = ['free_trial', 'starter', 'growth', 'enterprise'];
        $plans = Plan::where('is_active', true)
            ->whereIn('code', $allowedCodes)
            ->orderBy('price', 'asc')
            ->get();
        
        // Get selected plan from URL parameter
        $selectedPlan = $request->get('plan');
        
        return view('tenant.register', compact('plans', 'selectedPlan'));
    }

    /**
     * Register a new tenant.
     */
    public function register(Request $request)
    {
        // Build validation rules dynamically: guests must set a password
        $rules = [
            'name' => 'required|string|max:255',
            'contact_email' => 'required|email:rfc,dns|max:255',
            'phone' => ['required', 'string', 'min:12', 'max:15', 'regex:/^255[0-9]{9,12}$/'],
            'plan_id' => 'required|exists:plans,id',
        ];
        $messages = [
            'contact_email.email' => 'Please enter a valid email address.',
            'phone.required' => 'Phone number is required.',
            'phone.regex' => 'Phone number must start with 255 (e.g., 255712345678).',
        ];
        if (!Auth::check()) {
            $rules['password'] = 'required|string|min:8|confirmed';
        }
        $validated = $request->validate($rules, $messages);

        // Auto-generate a unique slug from organization name
        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $counter = 1;
        while (Tenant::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        DB::transaction(function () use ($validated, $slug) {
            $plan = Plan::find($validated['plan_id']);

            // Determine trial duration based on plan
            $trialDays = $plan->code === 'free_trial' ? 3 : 3;
            
            $tenant = Tenant::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'contact_email' => $validated['contact_email'],
                'phone' => $validated['phone'] ?? null,
                'status' => 'active',
                'plan_id' => $plan->id,
                'plan_slug' => $plan->code,
                'trial_ends_at' => now()->addDays($trialDays),
                'plan_renews_at' => null,
                'is_on_trial' => true,
            ]);

            // Determine or create a user for this tenant
            // Tenant owner (registrant) should always be admin with full permissions
            $user = Auth::user();
            if (!$user) {
                $user = User::create([
                    'tenant_id' => $tenant->id,
                    'name' => $validated['name'],
                    'email' => $validated['contact_email'],
                    'password' => Hash::make($validated['password'] ?? Str::random(12)),
                    'role' => 'admin', // Tenant owner is always admin
                    'email_verified_at' => now(),
                ]);
                Auth::login($user);
            } else {
                // If already authenticated, assign their account to the new tenant as admin
                $user->update([
                    'tenant_id' => $tenant->id,
                    'role' => 'admin', // Tenant owner is always admin
                ]);
            }

            // Seed all tenant defaults (roles, permissions, branches, loan products)
            $seed = TenantOnboardingService::seedDefaults($tenant);
            $roles = $seed['roles'] ?? [];

            // Attach admin role to tenant owner (admin has all permissions)
            if ($user) {
                if (isset($roles['admin'])) {
                    RbacService::attachUserRole($user, $roles['admin']);
                } else {
                    // Fallback: find admin role
                    $adminRole = Role::where('tenant_id', $tenant->id)
                        ->where('slug', 'admin')
                        ->first();
                    if ($adminRole) {
                        RbacService::attachUserRole($user, $adminRole);
                    }
                }
            }

            // Set session tenant context
            session([
                'tenant_id'   => $tenant->id,
                'tenant_slug' => $tenant->slug,
                'tenant_name' => $tenant->name,
                'tenant'      => $tenant,
            ]);
        });

        // Send welcome SMS to new tenant (non-critical)
        try {
            $tenant = Tenant::where('contact_email', $validated['contact_email'])->latest()->first();
            $phone  = $validated['phone'] ?? null;
            if ($tenant && $phone) {
                app(\App\Services\NotificationSmsService::class)->sendWelcomeSms(
                    $tenant,
                    $validated['contact_email'],
                    $validated['password'] ?? '',
                    $phone
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Welcome SMS failed silently', ['error' => $e->getMessage()]);
        }

        // Notify superadmin of new tenant registration (non-critical)
        try {
            if (!isset($tenant)) {
                $tenant = Tenant::where('contact_email', $validated['contact_email'])->latest()->first();
            }
            if ($tenant) {
                app(\App\Services\NotificationSmsService::class)->notifySuperadminNewTenant(
                    $tenant,
                    $validated['contact_email'],
                    $validated['phone'] ?? 'N/A'
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Superadmin new-tenant SMS failed silently', ['error' => $e->getMessage()]);
        }

        return redirect()->route('user.dashboard')->with('success', 'Organization created and a 7-day free trial has started.');
    }
}