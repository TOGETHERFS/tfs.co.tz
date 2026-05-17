<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsWallet;
use App\Models\Tenant;
use App\Services\SmsManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmsWalletController extends Controller
{
    protected SmsManager $smsManager;

    public function __construct(SmsManager $smsManager)
    {
        $this->smsManager = $smsManager;
    }

    /**
     * Display a listing of SMS wallets.
     */
    public function index(Request $request)
    {
        $query = SmsWallet::with('tenant')
            ->select('sms_wallets.*')
            ->join('tenants', 'sms_wallets.tenant_id', '=', 'tenants.id');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tenants.name', 'like', "%{$search}%")
                  ->orWhere('tenants.domain', 'like', "%{$search}%");
            });
        }

        // Balance filter
        if ($request->filled('balance_filter')) {
            switch ($request->balance_filter) {
                case 'low':
                    $query->whereRaw('sms_wallets.balance <= sms_wallets.low_balance_threshold');
                    break;
                case 'zero':
                    $query->where('sms_wallets.balance', 0);
                    break;
                case 'high':
                    $query->where('sms_wallets.balance', '>', 1000);
                    break;
            }
        }

        // Auto topup filter
        if ($request->filled('auto_topup')) {
            $query->where('sms_wallets.auto_topup_enabled', $request->auto_topup === 'enabled');
        }

        $wallets = $query->orderBy('sms_wallets.balance', 'asc')
            ->paginate(20)
            ->withQueryString();

        // Get summary statistics
        $stats = [
            'total_wallets' => SmsWallet::count(),
            'total_balance' => SmsWallet::sum('balance'),
            'low_balance_count' => SmsWallet::whereRaw('balance <= low_balance_threshold')->count(),
            'zero_balance_count' => SmsWallet::where('balance', 0)->count(),
            'auto_topup_enabled' => SmsWallet::where('auto_topup_enabled', true)->count()
        ];

        return view('admin.sms-wallets.index', compact('wallets', 'stats'));
    }

    /**
     * Display the specified SMS wallet.
     */
    public function show(SmsWallet $smsWallet)
    {
        $smsWallet->load('tenant', 'topups', 'smsLogs');
        
        // Get recent transactions
        $recentTransactions = $smsWallet->getRecentTransactions(50);
        
        // Get usage statistics
        $stats = [
            'total_credits_added' => $smsWallet->getTotalCreditsAdded(),
            'total_credits_used' => $smsWallet->getTotalCreditsUsed(),
            'total_sms_sent' => $smsWallet->smsLogs()->where('status', 'sent')->count(),
            'total_sms_delivered' => $smsWallet->smsLogs()->where('status', 'delivered')->count(),
            'total_sms_failed' => $smsWallet->smsLogs()->where('status', 'failed')->count(),
            'last_sms_sent' => $smsWallet->smsLogs()->latest()->first()?->created_at,
            'average_daily_usage' => $this->calculateAverageDailyUsage($smsWallet)
        ];

        return view('admin.sms-wallets.show', compact('smsWallet', 'recentTransactions', 'stats'));
    }

    /**
     * Show the form for creating a new SMS wallet.
     */
    public function create()
    {
        // Get tenants without SMS wallets
        $tenantsWithoutWallets = Tenant::whereNotIn('id', function ($query) {
            $query->select('tenant_id')->from('sms_wallets');
        })->get();

        return view('admin.sms-wallets.create', compact('tenantsWithoutWallets'));
    }

    /**
     * Store a newly created SMS wallet.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id|unique:sms_wallets,tenant_id',
            'balance' => 'required|integer|min:0',
            'low_balance_threshold' => 'nullable|integer|min:0',
            'auto_topup_enabled' => 'boolean',
            'auto_topup_amount' => 'nullable|integer|min:1',
            'auto_topup_threshold' => 'nullable|integer|min:0'
        ]);

        $wallet = SmsWallet::create($validated);

        // Add initial credits if specified
        if ($validated['balance'] > 0) {
            $wallet->addCredits($validated['balance'], 'Initial wallet setup');
        }

        return redirect()->route('admin.sms-wallets.index')
            ->with('success', 'SMS wallet created successfully.');
    }

    /**
     * Show the form for editing the specified SMS wallet.
     */
    public function edit(SmsWallet $smsWallet)
    {
        return view('admin.sms-wallets.edit', compact('smsWallet'));
    }

    /**
     * Update the specified SMS wallet.
     */
    public function update(Request $request, SmsWallet $smsWallet)
    {
        $validated = $request->validate([
            'low_balance_threshold' => 'nullable|integer|min:0',
            'auto_topup_enabled' => 'boolean',
            'auto_topup_amount' => 'nullable|integer|min:1',
            'auto_topup_threshold' => 'nullable|integer|min:0'
        ]);

        $smsWallet->update($validated);

        return redirect()->route('admin.sms-wallets.show', $smsWallet)
            ->with('success', 'SMS wallet updated successfully.');
    }

    /**
     * Add credits to the wallet.
     */
    public function addCredits(Request $request, SmsWallet $smsWallet)
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1',
            'description' => 'nullable|string|max:255'
        ]);

        $description = $validated['description'] ?? 'Admin credit addition';
        
        $success = $this->smsManager->addCredits(
            $smsWallet->tenant_id,
            $validated['amount'],
            $description
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Credits added successfully.',
                'new_balance' => $smsWallet->fresh()->balance
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to add credits.'
        ]);
    }

    /**
     * Deduct credits from the wallet.
     */
    public function deductCredits(Request $request, SmsWallet $smsWallet)
    {
        $validated = $request->validate([
            'amount' => 'required|integer|min:1|max:' . $smsWallet->balance,
            'description' => 'nullable|string|max:255'
        ]);

        $description = $validated['description'] ?? 'Admin credit deduction';
        
        $success = $this->smsManager->deductCredits(
            $smsWallet->tenant_id,
            $validated['amount'],
            $description
        );

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Credits deducted successfully.',
                'new_balance' => $smsWallet->fresh()->balance
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to deduct credits.'
        ]);
    }

    /**
     * Get wallet statistics for dashboard.
     */
    public function getStats()
    {
        $stats = [
            'total_wallets' => SmsWallet::count(),
            'total_balance' => SmsWallet::sum('balance'),
            'low_balance_wallets' => SmsWallet::whereRaw('balance <= low_balance_threshold')->count(),
            'zero_balance_wallets' => SmsWallet::where('balance', 0)->count(),
            'auto_topup_enabled' => SmsWallet::where('auto_topup_enabled', true)->count(),
            'recent_activity' => $this->getRecentActivity()
        ];

        return response()->json($stats);
    }

    /**
     * Bulk operations on wallets.
     */
    public function bulkOperation(Request $request)
    {
        $validated = $request->validate([
            'operation' => 'required|in:add_credits,deduct_credits,enable_auto_topup,disable_auto_topup',
            'wallet_ids' => 'required|array',
            'wallet_ids.*' => 'exists:sms_wallets,id',
            'amount' => 'required_if:operation,add_credits,deduct_credits|integer|min:1',
            'description' => 'nullable|string|max:255'
        ]);

        $wallets = SmsWallet::whereIn('id', $validated['wallet_ids'])->get();
        $successCount = 0;
        $errors = [];

        DB::transaction(function () use ($validated, $wallets, &$successCount, &$errors) {
            foreach ($wallets as $wallet) {
                try {
                    switch ($validated['operation']) {
                        case 'add_credits':
                            $success = $this->smsManager->addCredits(
                                $wallet->tenant_id,
                                $validated['amount'],
                                $validated['description'] ?? 'Bulk credit addition'
                            );
                            if ($success) $successCount++;
                            break;

                        case 'deduct_credits':
                            if ($wallet->balance >= $validated['amount']) {
                                $success = $this->smsManager->deductCredits(
                                    $wallet->tenant_id,
                                    $validated['amount'],
                                    $validated['description'] ?? 'Bulk credit deduction'
                                );
                                if ($success) $successCount++;
                            } else {
                                $errors[] = "Insufficient balance for {$wallet->tenant->name}";
                            }
                            break;

                        case 'enable_auto_topup':
                            $wallet->update(['auto_topup_enabled' => true]);
                            $successCount++;
                            break;

                        case 'disable_auto_topup':
                            $wallet->update(['auto_topup_enabled' => false]);
                            $successCount++;
                            break;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error with {$wallet->tenant->name}: " . $e->getMessage();
                }
            }
        });

        $message = "Operation completed. {$successCount} wallets processed successfully.";
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', $errors);
        }

        return redirect()->route('admin.sms-wallets.index')
            ->with($successCount > 0 ? 'success' : 'error', $message);
    }

    /**
     * Calculate average daily usage for a wallet.
     */
    protected function calculateAverageDailyUsage(SmsWallet $wallet): float
    {
        $thirtyDaysAgo = now()->subDays(30);
        
        $totalUsage = $wallet->smsLogs()
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->sum('cost');

        return round($totalUsage / 30, 2);
    }

    /**
     * Get recent activity across all wallets.
     */
    protected function getRecentActivity(): array
    {
        $recentTopups = DB::table('sms_topups')
            ->join('tenants', 'sms_topups.tenant_id', '=', 'tenants.id')
            ->where('sms_topups.created_at', '>=', now()->subDays(7))
            ->where('sms_topups.status', 'paid')
            ->select('tenants.name', 'sms_topups.units', 'sms_topups.created_at')
            ->orderBy('sms_topups.created_at', 'desc')
            ->limit(10)
            ->get();

        return $recentTopups->toArray();
    }
}