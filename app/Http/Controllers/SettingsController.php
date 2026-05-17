<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\LoanProduct;
use App\Models\Subscription;
use App\Models\Plan;

class SettingsController extends Controller
{
    /**
     * Display the settings dashboard.
     */
    public function index()
    {
        $settings = $this->getAllSettings();
        return view('settings.index', compact('settings'));
    }

    /**
     * Display general settings.
     */
    public function general()
    {
        $settings = [
            'company_name' => $this->getSetting('company_name', 'Microfinance Institution'),
            'company_address' => $this->getSetting('company_address', ''),
            'company_phone' => $this->getSetting('company_phone', ''),
            'company_email' => $this->getSetting('company_email', ''),
            'company_website' => $this->getSetting('company_website', ''),
            'company_logo' => $this->getSetting('company_logo', ''),
            'timezone' => $this->getSetting('timezone', 'UTC'),
            'date_format' => $this->getSetting('date_format', 'Y-m-d'),
            'currency' => $this->getSetting('currency', 'TZS'),
            'currency_symbol' => $this->getSetting('currency_symbol', 'TSHS'),
        ];

        return view('settings.general', compact('settings'));
    }

    /**
     * Update general settings.
     */
    public function updateGeneral(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'company_address' => 'nullable|string|max:500',
            'company_phone' => 'nullable|string|max:20',
            'company_email' => 'nullable|email|max:255',
            'company_website' => 'nullable|url|max:255',
            'company_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'timezone' => 'required|string|max:50',
            'date_format' => 'required|string|max:20',
            'currency' => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:5',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        // Handle logo upload
        if ($request->hasFile('company_logo')) {
            $logoPath = $request->file('company_logo')->store('logos', 'public');
            $this->setSetting('company_logo', $logoPath);
        }

        // Update other settings
        $settings = $request->except(['company_logo', '_token']);
        foreach ($settings as $key => $value) {
            $this->setSetting($key, $value);
        }

        return redirect()->back()->with('success', 'General settings updated successfully.');
    }

    /**
     * Display loan settings.
     */
    public function loan()
    {
        $settings = [
            'default_interest_rate' => $this->getSetting('default_interest_rate', 10),
            'default_loan_term' => $this->getSetting('default_loan_term', 12),
            'minimum_loan_amount' => $this->getSetting('minimum_loan_amount', 1000),
            'maximum_loan_amount' => $this->getSetting('maximum_loan_amount', 100000),
            'processing_fee_rate' => $this->getSetting('processing_fee_rate', 2),
            'late_payment_penalty' => $this->getSetting('late_payment_penalty', 5),
            'grace_period_days' => $this->getSetting('grace_period_days', 3),
            'auto_approve_limit' => $this->getSetting('auto_approve_limit', 5000),
            'require_guarantor' => $this->getSetting('require_guarantor', false),
            'allow_partial_payments' => $this->getSetting('allow_partial_payments', true),
        ];

        return view('settings.loan', compact('settings'));
    }

    /**
     * Update loan settings.
     */
    public function updateLoan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'default_interest_rate' => 'required|numeric|min:0|max:100',
            'default_loan_term' => 'required|integer|min:1|max:120',
            'minimum_loan_amount' => 'required|numeric|min:0',
            'maximum_loan_amount' => 'required|numeric|min:0',
            'processing_fee_rate' => 'required|numeric|min:0|max:100',
            'late_payment_penalty' => 'required|numeric|min:0|max:100',
            'grace_period_days' => 'required|integer|min:0|max:30',
            'auto_approve_limit' => 'required|numeric|min:0',
            'require_guarantor' => 'boolean',
            'allow_partial_payments' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $settings = $request->except(['_token']);
        foreach ($settings as $key => $value) {
            $this->setSetting($key, $value);
        }

        return redirect()->back()->with('success', 'Loan settings updated successfully.');
    }

    /**
     * Display notification settings.
     */
    public function notifications()
    {
        $settings = [
            'email_notifications' => $this->getSetting('email_notifications', true),
            'sms_notifications' => $this->getSetting('sms_notifications', false),
            'notify_loan_approval' => $this->getSetting('notify_loan_approval', true),
            'notify_loan_disbursement' => $this->getSetting('notify_loan_disbursement', true),
            'notify_payment_due' => $this->getSetting('notify_payment_due', true),
            'notify_payment_overdue' => $this->getSetting('notify_payment_overdue', true),
            'notify_payment_received' => $this->getSetting('notify_payment_received', true),
            'reminder_days_before' => $this->getSetting('reminder_days_before', 3),
            'overdue_reminder_frequency' => $this->getSetting('overdue_reminder_frequency', 7),
        ];

        return view('settings.notifications', compact('settings'));
    }

    /**
     * Update notification settings.
     */
    public function updateNotifications(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'notify_loan_approval' => 'boolean',
            'notify_loan_disbursement' => 'boolean',
            'notify_payment_due' => 'boolean',
            'notify_payment_overdue' => 'boolean',
            'notify_payment_received' => 'boolean',
            'reminder_days_before' => 'required|integer|min:1|max:30',
            'overdue_reminder_frequency' => 'required|integer|min:1|max:30',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $settings = $request->except(['_token']);
        foreach ($settings as $key => $value) {
            $this->setSetting($key, $value);
        }

        return redirect()->back()->with('success', 'Notification settings updated successfully.');
    }

    /**
     * Display security settings.
     */
    public function security()
    {
        $settings = [
            'password_min_length' => $this->getSetting('password_min_length', 8),
            'password_require_uppercase' => $this->getSetting('password_require_uppercase', true),
            'password_require_lowercase' => $this->getSetting('password_require_lowercase', true),
            'password_require_numbers' => $this->getSetting('password_require_numbers', true),
            'password_require_symbols' => $this->getSetting('password_require_symbols', false),
            'session_timeout' => $this->getSetting('session_timeout', 120),
            'max_login_attempts' => $this->getSetting('max_login_attempts', 5),
            'lockout_duration' => $this->getSetting('lockout_duration', 15),
            'two_factor_auth' => $this->getSetting('two_factor_auth', false),
        ];

        return view('settings.security', compact('settings'));
    }

    /**
     * Update security settings.
     */
    public function updateSecurity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password_min_length' => 'required|integer|min:6|max:50',
            'password_require_uppercase' => 'boolean',
            'password_require_lowercase' => 'boolean',
            'password_require_numbers' => 'boolean',
            'password_require_symbols' => 'boolean',
            'session_timeout' => 'required|integer|min:5|max:480',
            'max_login_attempts' => 'required|integer|min:3|max:20',
            'lockout_duration' => 'required|integer|min:5|max:120',
            'two_factor_auth' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $settings = $request->except(['_token']);
        foreach ($settings as $key => $value) {
            $this->setSetting($key, $value);
        }

        return redirect()->back()->with('success', 'Security settings updated successfully.');
    }

    /**
     * Display policies settings.
     */
    public function policies()
    {
        $settings = [
            'privacy_policy' => $this->getSetting('privacy_policy', ''),
            'terms_of_service' => $this->getSetting('terms_of_service', ''),
            'loan_approval_policy' => $this->getSetting('loan_approval_policy', ''),
            'loan_disbursement_policy' => $this->getSetting('loan_disbursement_policy', ''),
        ];

        return view('settings.policies', compact('settings'));
    }

    /**
     * Update policies settings.
     */
    public function updatePolicies(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'privacy_policy' => 'nullable|string',
            'terms_of_service' => 'nullable|string',
            'loan_approval_policy' => 'nullable|string',
            'loan_disbursement_policy' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $this->setSetting('privacy_policy', $request->input('privacy_policy'));
        $this->setSetting('terms_of_service', $request->input('terms_of_service'));
        $this->setSetting('loan_approval_policy', $request->input('loan_approval_policy'));
        $this->setSetting('loan_disbursement_policy', $request->input('loan_disbursement_policy'));

        return redirect()->back()->with('success', 'Policies updated successfully.');
    }

    /**
     * Display branches management.
     */
    public function branches()
    {
        $branches = $this->getSetting('branches', []);
        if (is_string($branches)) {
            $decoded = json_decode($branches, true);
            $branches = is_array($decoded) ? $decoded : [];
        }

        return view('settings.branches', compact('branches'));
    }

    /**
     * Store a new branch (basic cache-backed storage).
     */
    public function storeBranch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $branches = $this->getSetting('branches', []);
        if (is_string($branches)) {
            $decoded = json_decode($branches, true);
            $branches = is_array($decoded) ? $decoded : [];
        }

        // Enforce plan branch limit
        $tenantId = session('tenant_id');
        $subscription = Subscription::where('tenant_id', $tenantId)->with('plan')->first();
        $branchLimit = $subscription && $subscription->plan ? ($subscription->plan->branch_limit ?? null) : null;
        $currentBranchCount = is_array($branches) ? count($branches) : 0;
        if ($branchLimit !== null && $currentBranchCount >= (int) $branchLimit) {
            return redirect()->back()
                ->with('error', 'Branch limit reached for your current plan. Please upgrade to add more branches.')
                ->with('open_billing_modal', true);
        }

        $branches[] = [
            'id' => (string) Str::uuid(),
            'name' => $request->name,
            'code' => $request->code,
            'address' => $request->address,
        ];

        $this->setSetting('branches', $branches);

        return redirect()->back()->with('success', 'Branch added successfully.');
    }

    /**
     * Delete a branch (by id).
     */
    public function deleteBranch($branch)
    {
        $branches = $this->getSetting('branches', []);
        if (is_string($branches)) {
            $decoded = json_decode($branches, true);
            $branches = is_array($decoded) ? $decoded : [];
        }

        $branches = array_values(array_filter($branches, function ($b) use ($branch) {
            return ($b['id'] ?? null) !== $branch;
        }));

        $this->setSetting('branches', $branches);

        return redirect()->back()->with('success', 'Branch deleted successfully.');
    }

    /**
     * Display login logs (based on activity log if available).
     */
    public function loginLogs()
    {
        try {
            $table = config('activitylog.table_name', 'activity_log');
            $logs = DB::table($table)
                ->where(function ($q) {
                    $q->where('description', 'like', '%logged%')
                      ->orWhere('log_name', 'auth');
                })
                ->orderByDesc('created_at')
                ->limit(100)
                ->get();
        } catch (\Throwable $e) {
            $logs = collect();
        }

        return view('settings.login-logs', compact('logs'));
    }

    /**
     * Users management under settings (list users for this tenant).
     */
    public function users()
    {
        $tenantId = session('tenant_id');
        $users = User::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->paginate(15);

        return view('settings.users', compact('users'));
    }

    /**
     * Roles management under settings.
     */
    public function roles()
    {
        $roles = $this->getRoleDefinitions();
        return view('settings.roles', compact('roles'));
    }

    /**
     * Placeholder: Account settings overview.
     */
    public function account()
    {
        return view('settings.account');
    }

    /**
     * Staffs management under settings.
     */
    public function staffs()
    {
        $tenantId = session('tenant_id');
        $roles = $this->getRoleDefinitions();
        $staffs = User::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        return view('settings.staffs', compact('roles', 'staffs'));
    }

    /**
     * Create a new user (staff) within current tenant.
     */
    public function storeUser(Request $request)
    {
        $roleCodes = collect($this->getRoleDefinitions())->pluck('code')->all();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->where(fn($q) => $q->where('tenant_id', session('tenant_id')))],
            'password' => ['required', 'string', 'min:8'],
            'role_code' => ['required', Rule::in($roleCodes)],
            'position' => ['nullable', 'string', 'max:120'],
        ]);

        // Enforce plan staff limit
        $tenantId = session('tenant_id');
        $subscription = Subscription::where('tenant_id', $tenantId)->with('plan')->first();
        $staffLimit = $subscription && $subscription->plan ? ($subscription->plan->staff_limit ?? null) : null;
        $currentStaffCount = User::where('tenant_id', $tenantId)->count();
        if ($staffLimit !== null && $currentStaffCount >= (int) $staffLimit) {
            return redirect()->route('settings.staffs')
                ->with('error', 'Staff limit reached for your current plan. Please upgrade to add more staff.')
                ->with('open_billing_modal', true);
        }

        $user = User::create([
            'tenant_id' => session('tenant_id'),
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $this->canonicalRoleCode($validated['role_code']),
            'position' => $validated['position'] ?? $validated['role_code'],
        ]);

        return redirect()->route('settings.staffs')->with('success', 'Staff created successfully.');
    }

    /**
     * Update an existing user (staff).
     */
    public function updateUser(User $user, Request $request)
    {
        $roleCodes = collect($this->getRoleDefinitions())->pluck('code')->all();

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($user->id)->where(fn($q) => $q->where('tenant_id', session('tenant_id')))],
            'password' => ['nullable', 'string', 'min:8'],
            'role_code' => ['nullable', Rule::in($roleCodes)],
            'position' => ['nullable', 'string', 'max:120'],
        ]);

        $update = [];
        foreach (['name','email','position'] as $field) {
            if (isset($validated[$field])) {
                $update[$field] = $validated[$field];
            }
        }
        if (!empty($validated['password'])) {
            $update['password'] = Hash::make($validated['password']);
        }
        if (!empty($validated['role_code'])) {
            $update['role'] = $this->canonicalRoleCode($validated['role_code']);
            // If position was not provided, default to selected role_code for clarity
            if (!isset($update['position'])) {
                $update['position'] = $validated['role_code'];
            }
        }

        if (!empty($update)) {
            $user->update($update);
        }

        return redirect()->route('settings.staffs')->with('success', 'Staff updated successfully.');
    }

    /**
     * Delete a user (staff).
     */
    public function deleteUser(User $user)
    {
        $user->delete();
        return redirect()->route('settings.staffs')->with('success', 'Staff deleted successfully.');
    }

    /**
     * Store a new role definition.
     */
    public function storeRole(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:60'],
            'category' => ['nullable', 'string', 'max:60'],
        ]);

        $roles = $this->getRoleDefinitions();
        $code = $request->input('code') ?: Str::slug($request->input('name'), '_');

        // Disallow creating Super Admin role
        $normalizedName = strtolower($request->input('name'));
        if (in_array($normalizedName, ['super admin','super_admin','superadmin']) || strtolower($code) === 'superadmin') {
            return redirect()->back()->with('error', 'Super Admin role is reserved and cannot be created.');
        }

        // Prevent duplicates by code
        foreach ($roles as $r) {
            if (($r['code'] ?? '') === $code) {
                return redirect()->back()->with('error', 'Role code already exists.');
            }
        }

        $roles[] = [
            'name' => $request->input('name'),
            'code' => $code,
            'category' => $request->input('category') ?: null,
        ];
        $this->setSetting('role_definitions', $roles);

        return redirect()->route('settings.roles')->with('success', 'Role added successfully.');
    }

    /**
     * Update role name or code.
     */
    public function updateRole($role, Request $request)
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:60'],
            'category' => ['nullable', 'string', 'max:60'],
        ]);

        // Disallow modifying Super Admin role or changing any role to Super Admin
        if (strtolower($role) === 'superadmin') {
            return redirect()->back()->with('error', 'Super Admin role cannot be modified.');
        }
        if ($request->filled('code') && strtolower($request->input('code')) === 'superadmin') {
            return redirect()->back()->with('error', 'Cannot change role code to Super Admin.');
        }
        if ($request->filled('name')) {
            $nm = strtolower($request->input('name'));
            if (in_array($nm, ['super admin','super_admin','superadmin'])) {
                return redirect()->back()->with('error', 'Cannot rename role to Super Admin.');
            }
        }

        $roles = $this->getRoleDefinitions();
        $updated = false;
        foreach ($roles as &$r) {
            if (($r['code'] ?? '') === $role) {
                if ($request->filled('name')) { $r['name'] = $request->input('name'); }
                if ($request->filled('code')) { $r['code'] = $request->input('code'); }
                if ($request->has('category')) { $r['category'] = $request->input('category'); }
                $updated = true;
                break;
            }
        }

        if (!$updated) {
            return redirect()->back()->with('error', 'Role not found.');
        }

        $this->setSetting('role_definitions', $roles);
        return redirect()->route('settings.roles')->with('success', 'Role updated successfully.');
    }

    /**
     * Delete a role definition by code.
     */
    public function deleteRole($role)
    {
        // Disallow deleting Super Admin role
        if (strtolower($role) === 'superadmin') {
            return redirect()->back()->with('error', 'Super Admin role cannot be deleted.');
        }

        $roles = $this->getRoleDefinitions();
        $roles = array_values(array_filter($roles, fn($r) => ($r['code'] ?? '') !== $role));
        $this->setSetting('role_definitions', $roles);

        return redirect()->route('settings.roles')->with('success', 'Role deleted successfully.');
    }

    /**
     * Loan products index (scoped to current tenant).
     */
    public function loanProducts(Request $request)
    {
        $tenantId = session('tenant_id') ?? optional(session('tenant'))->id ?? optional(auth()->user())->tenant_id;

        $query = \App\Models\LoanProduct::query()
            ->when($tenantId, function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->withCount('loans')
            ->orderBy('name');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('is_active', $status === 'active');
        }

        $products = $query->paginate(15);

        return view('loan-products.index', compact('products'));
    }

    /**
     * Store a new loan product.
     */
    public function storeLoanProduct(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'interest_rate' => ['required', 'numeric', 'min:0'],
            'interest_type' => ['required', 'in:flat,reducing_balance'],
            'min_amount' => ['required', 'numeric', 'min:0'],
            'max_amount' => ['required', 'numeric', 'min:0', 'gte:min_amount'],
            'min_term' => ['required', 'integer', 'min:1'],
            'max_term' => ['required', 'integer', 'min:1', 'gte:min_term'],
            'processing_fee' => ['nullable', 'numeric', 'min:0'],
            'processing_fee_type' => ['nullable', 'in:percentage,fixed'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $tenantId = session('tenant_id') ?? optional(session('tenant'))->id ?? optional(auth()->user())->tenant_id;

        \App\Models\LoanProduct::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'min_amount' => $validated['min_amount'],
            'max_amount' => $validated['max_amount'],
            'interest_rate' => $validated['interest_rate'],
            'interest_type' => $validated['interest_type'],
            'min_term' => $validated['min_term'],
            'max_term' => $validated['max_term'],
            'processing_fee' => $validated['processing_fee'] ?? 0,
            'processing_fee_type' => $validated['processing_fee_type'] ?? 'percentage',
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('settings.loan-products')->with('success', 'Loan product created successfully.');
    }

    /**
     * Update an existing loan product.
     */
    public function updateLoanProduct(Request $request, \App\Models\LoanProduct $product)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'interest_rate' => ['required', 'numeric', 'min:0'],
            'interest_type' => ['required', 'in:flat,reducing_balance'],
            'min_amount' => ['required', 'numeric', 'min:0'],
            'max_amount' => ['required', 'numeric', 'min:0', 'gte:min_amount'],
            'min_term' => ['required', 'integer', 'min:1'],
            'max_term' => ['required', 'integer', 'min:1', 'gte:min_term'],
            'processing_fee' => ['nullable', 'numeric', 'min:0'],
            'processing_fee_type' => ['nullable', 'in:percentage,fixed'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $tenantId = session('tenant_id') ?? optional(session('tenant'))->id ?? optional(auth()->user())->tenant_id;
        if ($tenantId && $product->tenant_id !== $tenantId) {
            abort(403);
        }

        $product->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'min_amount' => $validated['min_amount'],
            'max_amount' => $validated['max_amount'],
            'interest_rate' => $validated['interest_rate'],
            'interest_type' => $validated['interest_type'],
            'min_term' => $validated['min_term'],
            'max_term' => $validated['max_term'],
            'processing_fee' => $validated['processing_fee'] ?? 0,
            'processing_fee_type' => $validated['processing_fee_type'] ?? $product->processing_fee_type ?? 'percentage',
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('settings.loan-products')->with('success', 'Loan product updated successfully.');
    }

    public function deleteLoanProduct(\App\Models\LoanProduct $product)
    {
        // Prevent deleting if product has loans to avoid orphan references
        if ($product->loans()->exists()) {
            return redirect()->route('settings.loan-products')->with('error', 'Cannot delete a product with existing loans.');
        }

        // Ensure tenant ownership using the same resolution strategy as listing
        $tenantId = session('tenant_id') ?? optional(session('tenant'))->id ?? optional(auth()->user())->tenant_id;
        if ($tenantId && $product->tenant_id !== $tenantId) {
            logger()->warning('LoanProduct delete unauthorized', [
                'product_id' => $product->id,
                'product_tenant_id' => $product->tenant_id,
                'resolved_tenant_id' => $tenantId,
                'session_tenant_id' => session('tenant_id'),
                'auth_user_tenant_id' => optional(auth()->user())->tenant_id,
            ]);
            abort(403);
        }

        $product->delete();
        return redirect()->route('settings.loan-products')->with('success', 'Loan product deleted successfully.');
    }

    /**
     * Toggle the active status of a loan product.
     */
    public function toggleLoanProductStatus(\App\Models\LoanProduct $product)
    {
        $tenantId = session('tenant_id') ?? optional(session('tenant'))->id ?? optional(auth()->user())->tenant_id;
        if ($tenantId && $product->tenant_id !== $tenantId) {
            abort(403);
        }

        $product->update([
            'is_active' => !$product->is_active
        ]);

        $status = $product->is_active ? 'activated' : 'deactivated';

        return redirect()->route('settings.loan-products')->with('success', "Loan product {$status} successfully.");
    }

    /**
     * Display backup settings.
     */
    public function backup()
    {
        $settings = [
            'auto_backup' => $this->getSetting('auto_backup', false),
            'backup_frequency' => $this->getSetting('backup_frequency', 'daily'),
            'backup_retention_days' => $this->getSetting('backup_retention_days', 30),
            'backup_location' => $this->getSetting('backup_location', 'local'),
        ];

        $backups = $this->getBackupHistory();

        return view('settings.backup', compact('settings', 'backups'));
    }

    /**
     * Update backup settings.
     */
    public function updateBackup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'auto_backup' => 'boolean',
            'backup_frequency' => 'required|in:daily,weekly,monthly',
            'backup_retention_days' => 'required|integer|min:1|max:365',
            'backup_location' => 'required|in:local,cloud',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $settings = $request->except(['_token']);
        foreach ($settings as $key => $value) {
            $this->setSetting($key, $value);
        }

        return redirect()->back()->with('success', 'Backup settings updated successfully.');
    }

    /**
     * Create manual backup.
     */
    public function createBackup()
    {
        try {
            // Implementation for creating backup
            // This would typically involve database export and file archiving
            
            return redirect()->back()->with('success', 'Backup created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create backup: ' . $e->getMessage());
        }
    }

    /**
     * Restore backup from uploaded file (placeholder implementation).
     */
    public function restoreBackup(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file',
        ]);

        try {
            $file = $request->file('backup_file');
            $storedPath = $file->store('backups/restore');

            // In a real implementation, this would trigger database and files restore
            // from the uploaded archive or SQL dump.

            return redirect()->back()->with('success', 'Backup file uploaded. Restore process initiated.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to restore backup: ' . $e->getMessage());
        }
    }

    /**
     * Change user password.
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                           ->withErrors($validator)
                           ->withInput();
        }

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->with('error', 'Current password is incorrect.');
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return redirect()->back()->with('success', 'Password changed successfully.');
    }

    /**
     * Get a setting value.
     */
    private function getSetting($key, $default = null)
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            // In a real implementation, this would fetch from a settings table
            // For now, return default values
            return $default;
        });
    }

    /**
     * Set a setting value.
     */
    private function setSetting($key, $value)
    {
        // In a real implementation, this would save to a settings table
        // For now, just cache the value
        Cache::put("setting_{$key}", $value, 3600);
        
        // Log the setting change
        activity()
            ->causedBy(Auth::user())
            ->withProperties(['key' => $key, 'value' => $value])
            ->log('Setting updated');
    }

    /**
     * Get all settings.
     */
    private function getAllSettings()
    {
        return [
            'general' => [
                'company_name' => $this->getSetting('company_name', 'Microfinance Institution'),
                'currency' => $this->getSetting('currency', 'TZS'),
                'timezone' => $this->getSetting('timezone', 'UTC'),
            ],
            'loan' => [
                'default_interest_rate' => $this->getSetting('default_interest_rate', 10),
                'minimum_loan_amount' => $this->getSetting('minimum_loan_amount', 1000),
                'maximum_loan_amount' => $this->getSetting('maximum_loan_amount', 100000),
            ],
            'notifications' => [
                'email_notifications' => $this->getSetting('email_notifications', true),
                'sms_notifications' => $this->getSetting('sms_notifications', false),
            ],
            'security' => [
                'password_min_length' => $this->getSetting('password_min_length', 8),
                'session_timeout' => $this->getSetting('session_timeout', 120),
            ],
        ];
    }

    /**
     * Get backup history.
     */
    private function getBackupHistory()
    {
        // In a real implementation, this would fetch backup records
        return [];
    }

    /**
     * Get role definitions from settings or defaults.
     */
    private function getRoleDefinitions(): array
    {
        $defaults = [
            ['name' => 'Super Admin', 'code' => 'superadmin', 'category' => 'System'],
            ['name' => 'CEO', 'code' => 'ceo', 'category' => 'Executive'],
            ['name' => 'Board of Directors', 'code' => 'board', 'category' => 'Executive'],
            ['name' => 'General Manager', 'code' => 'gm', 'category' => 'Management'],
            ['name' => 'Manager', 'code' => 'manager', 'category' => 'Management'],
            ['name' => 'Loan Officers', 'code' => 'officer', 'category' => 'Operations'],
            ['name' => 'Teller', 'code' => 'teller', 'category' => 'Operations'],
            ['name' => 'Accounts', 'code' => 'accountant', 'category' => 'Finance'],
            ['name' => 'Admin', 'code' => 'admin', 'category' => 'System'],
        ];

        $roles = $this->getSetting('role_definitions', $defaults);
        if (is_string($roles)) {
            $decoded = json_decode($roles, true);
            $roles = is_array($decoded) ? $decoded : $defaults;
        }
        return $roles;
    }

    private function canonicalRoleCode(string $code): string
    {
        // Map arbitrary role codes to the limited enum allowed by users.role
        // Allowed enum values: admin, manager, officer
        $map = [
            'admin' => 'admin',
            'superadmin' => 'admin',
            'ceo' => 'manager',
            'board' => 'manager',
            'gm' => 'manager',
            'manager' => 'manager',
            'officer' => 'officer',
            'loan_officer' => 'officer',
            'teller' => 'officer',
            'accountant' => 'officer',
            'cso' => 'officer',
        ];
        return $map[strtolower($code)] ?? 'officer';
    }
}