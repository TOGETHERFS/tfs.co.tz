<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    /**
     * Display a listing of tenants
     */
    public function index()
    {
        $tenants = Tenant::with('users')->orderBy('created_at', 'desc')->paginate(15);
        return view('admin.tenants.index', compact('tenants'));
    }

    /**
     * Display the specified tenant
     */
    public function show(Tenant $tenant)
    {
        $tenant->load(['users', 'loans', 'clients']);
        return view('admin.tenants.show', compact('tenant'));
    }

    /**
     * Suspend the specified tenant
     */
    public function suspend(Tenant $tenant)
    {
        $tenant->update([
            'status' => 'suspended',
            'suspended_at' => now()
        ]);
        return redirect()->back()->with('success', 'Tenant suspended successfully.');
    }

    /**
     * Activate the specified tenant
     */
    public function activate(Tenant $tenant)
    {
        $tenant->update([
            'status' => 'active',
            'suspended_at' => null
        ]);
        return redirect()->back()->with('success', 'Tenant activated successfully.');
    }

    /**
     * Show the form for editing the specified tenant
     */
    public function edit(Tenant $tenant)
    {
        return view('admin.tenants.edit', compact('tenant'));
    }

    /**
     * Update the specified tenant in storage
     */
    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $tenant->update($validated);

        return redirect()->route('admin.tenants.index')
            ->with('success', 'Tenant updated successfully.');
    }

    /**
     * Delete the specified tenant and all related data
     */
    public function destroy(Tenant $tenant)
    {
        // Use database transaction for safety
        \DB::beginTransaction();
        
        try {
            $tid = $tenant->id;

            // ── Billing / Subscriptions ──────────────────────────────────────
            \DB::table('subscription_items')->whereIn('subscription_id',
                \DB::table('subscriptions')->where('tenant_id', $tid)->pluck('id')
            )->delete();
            \DB::table('subscriptions')->where('tenant_id', $tid)->delete();
            \DB::table('selcom_transactions')->whereIn('loan_id',
                \DB::table('loans')->where('tenant_id', $tid)->pluck('id')
            )->delete();
            \DB::table('billing_audit_logs')->where('tenant_id', $tid)->delete();

            // ── Invoices / Payments ──────────────────────────────────────────
            \DB::table('payments')->whereIn('invoice_id',
                \DB::table('invoices')->where('tenant_id', $tid)->pluck('id')
            )->delete();
            \DB::table('invoices')->where('tenant_id', $tid)->delete();

            // ── SMS ──────────────────────────────────────────────────────────
            \DB::table('sms_messages')->where('tenant_id', $tid)->delete();
            \DB::table('sms_campaigns')->where('tenant_id', $tid)->delete();
            \DB::table('sms_logs')->where('tenant_id', $tid)->delete();
            \DB::table('sms_purchases')->where('tenant_id', $tid)->delete();
            \DB::table('sms_purchase_requests')->where('tenant_id', $tid)->delete();
            \DB::table('sms_topups')->where('tenant_id', $tid)->delete();
            \DB::table('sms_transactions')->where('tenant_id', $tid)->delete();
            \DB::table('sms_wallets')->where('tenant_id', $tid)->delete();
            \DB::table('sms_balances')->where('tenant_id', $tid)->delete();
            \DB::table('sms_sender_ids')->where('tenant_id', $tid)->delete();
            \DB::table('sms_sender_id_requests')->where('tenant_id', $tid)->delete();
            \DB::table('sms_templates')->where('tenant_id', $tid)->delete();
            \DB::table('sender_ids')->where('tenant_id', $tid)->delete();
            \DB::table('sms_provider_settings')->where('tenant_id', $tid)->delete();

            // ── Loans ────────────────────────────────────────────────────────
            \DB::table('loan_workflow_logs')->where('tenant_id', $tid)->delete();
            \DB::table('loan_approvals')->whereIn('loan_id',
                \DB::table('loans')->where('tenant_id', $tid)->pluck('id')
            )->delete();
            \DB::table('loan_documents')->whereIn('loan_id',
                \DB::table('loans')->where('tenant_id', $tid)->pluck('id')
            )->delete();
            \DB::table('repayments')->where('tenant_id', $tid)->delete();
            \DB::table('loan_schedules')->where('tenant_id', $tid)->delete();
            \DB::table('loans')->where('tenant_id', $tid)->delete();
            \DB::table('loan_products')->where('tenant_id', $tid)->delete();
            \DB::table('workflow_step_assignments')->where('tenant_id', $tid)->delete();
            \DB::table('workflow_configs')->where('tenant_id', $tid)->delete();

            // ── Savings ──────────────────────────────────────────────────────
            \DB::table('savings_transactions')->whereIn('savings_account_id',
                \DB::table('savings_accounts')->where('tenant_id', $tid)->pluck('id')
            )->delete();
            \DB::table('savings_accounts')->where('tenant_id', $tid)->delete();

            // ── Clients / Groups ─────────────────────────────────────────────
            \DB::table('group_client')->whereIn('group_id',
                \DB::table('groups')->where('tenant_id', $tid)->pluck('id')
            )->delete();
            \DB::table('group_user')->whereIn('group_id',
                \DB::table('groups')->where('tenant_id', $tid)->pluck('id')
            )->delete();
            \DB::table('groups')->where('tenant_id', $tid)->delete();
            \DB::table('clients')->where('tenant_id', $tid)->delete();

            // ── Accounting ───────────────────────────────────────────────────
            \DB::table('journal_entry_lines')->whereIn('journal_entry_id',
                \DB::table('journal_entries')->where('tenant_id', $tid)->pluck('id')
            )->delete();
            \DB::table('journal_entries')->where('tenant_id', $tid)->delete();
            \DB::table('general_ledger')->where('tenant_id', $tid)->delete();
            \DB::table('accounting_audit_trail')->where('tenant_id', $tid)->delete();
            \DB::table('accounting_periods')->where('tenant_id', $tid)->delete();
            \DB::table('fiscal_years')->where('tenant_id', $tid)->delete();
            \DB::table('budgets')->where('tenant_id', $tid)->delete();
            \DB::table('chart_of_accounts')->where('tenant_id', $tid)->delete();
            \DB::table('account_categories')->where('tenant_id', $tid)->delete();
            \DB::table('bank_accounts')->where('tenant_id', $tid)->delete();
            \DB::table('expenses')->where('tenant_id', $tid)->delete();
            \DB::table('expense_categories')->where('tenant_id', $tid)->delete();
            // admin_expenses has no tenant_id — skip (superadmin-only table)

            // ── Assets ───────────────────────────────────────────────────────
            \DB::table('asset_depreciation_schedules')->whereIn('fixed_asset_id',
                \DB::table('fixed_assets')->where('tenant_id', $tid)->pluck('id')
            )->delete();
            \DB::table('fixed_assets')->where('tenant_id', $tid)->delete();
            \DB::table('fixed_asset_categories')->where('tenant_id', $tid)->delete();

            // ── Notifications / Activity ─────────────────────────────────────
            // notifications/activity_log have no tenant_id — skip (global tables)

            // ── Branches / Roles / Sessions ──────────────────────────────────
            \DB::table('branches')->where('tenant_id', $tid)->delete();
            \DB::table('role_assignments')->where('tenant_id', $tid)->delete();
            \DB::table('roles')->where('tenant_id', $tid)->delete();

            // ── Users ────────────────────────────────────────────────────────
            $userIds = \DB::table('users')->where('tenant_id', $tid)->pluck('id');
            \DB::table('sessions')->whereIn('user_id', $userIds)->delete();
            \DB::table('personal_access_tokens')->where('tokenable_type', 'App\\Models\\User')
                ->whereIn('tokenable_id', $userIds)->delete();
            \DB::table('user_role')->whereIn('user_id', $userIds)->delete();
            \DB::table('users')->where('tenant_id', $tid)->delete();

            // ── Tenant ───────────────────────────────────────────────────────
            $tenant->delete();
            
            \DB::commit();

            return redirect()->route('admin.tenants.index')->with('success', 'Tenant and all related data deleted successfully.');
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->back()->with('error', 'Failed to delete tenant: ' . $e->getMessage());
        }
    }
}