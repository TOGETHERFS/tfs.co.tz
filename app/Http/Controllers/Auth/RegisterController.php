<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\NotificationSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use App\Models\Tenant;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\RbacService;
use App\Services\TenantOnboardingService;
use App\Models\Role;

class RegisterController extends Controller
{
    /**
     * Show the registration form.
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Handle a registration request for the application.
     */
    public function register(Request $request)
    {
        $validator = $this->validator($request->all());

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput($request->except('password', 'password_confirmation'));
        }

        $user = $this->create($request->all());

        // Auto-create a default tenant and start 20-minute trial
        DB::transaction(function () use ($user) {
            $orgName = trim($user->name) ? ($user->name . ' Organization') : 'My Organization';
            $baseSlug = Str::slug($orgName);
            $slug = $baseSlug;
            $counter = 1;
            while (\App\Models\Tenant::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $plan = Plan::where('is_active', true)
                ->whereIn('code', ['starter', 'growth', 'enterprise'])
                ->orderByRaw("CASE code WHEN 'starter' THEN 1 WHEN 'growth' THEN 2 ELSE 3 END")
                ->first();

            $tenant = Tenant::create([
                'name' => $orgName,
                'slug' => $slug,
                'contact_email' => $user->email,
                'phone' => $user->phone,
                'status' => 'active',
                'plan_id' => optional($plan)->id,
                'plan_slug' => optional($plan)->code ?? 'starter',
                'trial_ends_at' => now()->addDays(3),
                'plan_renews_at' => null,
            ]);

            // Seed all tenant defaults (roles, permissions, branches, loan products)
            $seed = TenantOnboardingService::seedDefaults($tenant);
            $roles = $seed['roles'] ?? [];

            $user->update([
                'tenant_id' => $tenant->id,
                'role' => 'admin',
            ]);

            // Attach admin role to the tenant creator
            if (isset($roles['admin'])) {
                RbacService::attachUserRole($user, $roles['admin']);
            } else {
                $role = Role::where('tenant_id', $tenant->id)
                    ->where('slug', 'admin')
                    ->first();
                if ($role) {
                    RbacService::attachUserRole($user, $role);
                }
            }

            session([
                'tenant_id' => $tenant->id,
                'tenant_slug' => $tenant->slug,
                'tenant_name' => $tenant->name,
                'tenant' => $tenant,
            ]);
        });

        // Log the user in and redirect to dashboard
        Auth::login($user);

        // Send welcome SMS with login credentials (non-critical)
        try {
            $tenant = Tenant::where('contact_email', $user->email)->latest()->first();
            if ($tenant && $user->phone) {
                app(NotificationSmsService::class)->sendWelcomeSms(
                    $tenant,
                    $user->email,
                    $request->input('password'),
                    $user->phone
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Welcome SMS failed silently', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        // Notify superadmin of new tenant registration (non-critical)
        try {
            if (!isset($tenant)) {
                $tenant = Tenant::where('contact_email', $user->email)->latest()->first();
            }
            if ($tenant) {
                app(NotificationSmsService::class)->notifySuperadminNewTenant(
                    $tenant,
                    $user->email,
                    $user->phone ?? 'N/A'
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Superadmin new-tenant SMS failed silently', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }

        // Set Kiswahili as default language for new tenants
        session()->put('locale', 'sw');

        return redirect()->route('user.dashboard')
            ->with('success', 'Usajili umefanikiwa! Shirika limeundwa na majaribio ya siku 3 yameanza.');
    }

    /**
     * Get a validator for an incoming registration request.
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email:rfc,dns|max:255|unique:users',
            'phone' => ['required', 'string', 'min:12', 'max:15', 'regex:/^255[0-9]{9,12}$/'],
            'password' => 'required|string|min:8|confirmed',
            'terms' => 'required|accepted',
        ], [
            'email.email' => 'Please enter a valid email address.',
            'phone.required' => 'Phone number is required.',
            'phone.regex' => 'Phone number must start with 255 (e.g., 255712345678).',
            'phone.min' => 'Phone number must be at least 12 digits.',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     */
    protected function create(array $data)
    {
        // If the UI still posts first/last name, join them gracefully
        $name = $data['name'] ?? trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

        $user = User::create([
            'name' => $name,
            'email' => strtolower(trim($data['email'])), // Normalize email to lowercase
            'phone' => trim($data['phone'] ?? ''),
            'password' => Hash::make($data['password']),
            'role' => 'admin', // tenant creator gets admin role
            'email_verified_at' => now(), // auto-verify on registration
        ]);

        return $user;
    }
}