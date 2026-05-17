<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Client;
use App\Models\Loan;
use App\Models\Repayment;
use App\Models\Tenant;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\LoanSchedule;
use App\Models\SenderId;
use App\Models\Sms\SmsBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        $user = auth()->user();

        \Log::info('AdminDashboardController accessed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'position' => $user->position,
            'admin_role_id' => $user->admin_role_id,
            'tenant_id' => $user->tenant_id,
        ]);

        // Allow Super Admins and Admin Staff to access admin dashboard
        $superAliases = ['super_admin', 'superadmin', 'super-admin', 'super admin'];
        $isSuper = in_array($user->role, $superAliases)
            || (method_exists($user, 'hasRole') && (
                $user->hasRole('super_admin') ||
                $user->hasRole('superadmin') ||
                $user->hasRole('super-admin') ||
                $user->hasRole('super admin')
            ));

        // Check if user is admin staff member
        $isAdminStaff = !empty($user->admin_role_id);

        \Log::info('AdminDashboardController access check', [
            'user_id' => $user->id,
            'isSuper' => $isSuper,
            'isAdminStaff' => $isAdminStaff,
        ]);

        if (!$isSuper && !$isAdminStaff) {
            \Log::info('AdminDashboardController redirecting to user dashboard', ['user_id' => $user->id]);
            return redirect()->route('user.dashboard');
        }

        \Log::info('AdminDashboardController allowing access', ['user_id' => $user->id]);

        // Get system-wide statistics (remove tenant scope for superadmin)
        $adminActiveIds = Loan::withoutGlobalScope('tenant')->whereIn('status', ['disbursed', 'active'])->pluck('id');
        $adminTotalToPay = (float) Loan::withoutGlobalScope('tenant')->whereIn('status', ['disbursed', 'active'])->sum('total_amount');
        $adminTotalRepaid = (float) Repayment::withoutGlobalScope('tenant')->whereIn('loan_id', $adminActiveIds)->sum('amount');

        $allAdminLoanIds = Loan::withoutGlobalScope('tenant')->pluck('id');

        $stats = [
            'total_tenants' => Tenant::count(),
            'total_users' => User::withoutGlobalScope('tenant')->count(),
            'total_clients' => Client::withoutGlobalScope('tenant')->count(),
            'total_loans' => Loan::withoutGlobalScope('tenant')->count(),
            'total_loan_amount' => $adminTotalToPay - $adminTotalRepaid,
            'total_repayments' => Repayment::withoutGlobalScope('tenant')->whereIn('loan_id', $allAdminLoanIds)->sum('amount'),
            'active_loans' => Loan::withoutGlobalScope('tenant')->whereIn('status', ['active', 'disbursed'])->count(),
            'pending_loans' => Loan::withoutGlobalScope('tenant')->where('status', 'pending')->count(),
        ];

        // Get recent activities (without tenant scope for superadmin)
        $recent_loans = Loan::withoutGlobalScope('tenant')->with(['client'])
            ->latest()
            ->take(5)
            ->get();

        $recent_repayments = Repayment::withoutGlobalScope('tenant')->with(['loan.client'])
            ->whereIn('loan_id', $allAdminLoanIds)
            ->latest()
            ->take(5)
            ->get();

        // Get monthly loan disbursement data for charts (SQLite-friendly)
        $currentYear = (int) date('Y');
        $monthly_disbursements = collect();
        for ($m = 1; $m <= 12; $m++) {
            $start = \Carbon\Carbon::createFromDate($currentYear, $m, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();

            $total = Loan::withoutGlobalScope('tenant')->where('status', 'active')
                        ->whereBetween('created_at', [$start, $end])
                        ->sum('principal');

            $count = Loan::withoutGlobalScope('tenant')->where('status', 'active')
                        ->whereBetween('created_at', [$start, $end])
                        ->count();

            $monthly_disbursements->push([
                'month' => $m,
                'year' => $currentYear,
                'total_amount' => $total,
                'loan_count' => $count,
            ]);
        }

        // Get user role distribution
        $user_roles = User::withoutGlobalScope('tenant')->select('role', DB::raw('count(*) as count'))
            ->groupBy('role')
            ->get();

        // Get tenant subscription status
        $tenant_stats = Tenant::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        // Global Overview KPI toggles (today / MTD / YTD)
        [$startToday, $endToday] = $this->dateRange('today');
        [$startMTD, $endMTD] = $this->dateRange('mtd');
        [$startYTD, $endYTD] = $this->dateRange('ytd');

        $kpis = [
            'today' => $this->calculateGlobalKpis($startToday, $endToday),
            'mtd' => $this->calculateGlobalKpis($startMTD, $endMTD),
            'ytd' => $this->calculateGlobalKpis($startYTD, $endYTD),
        ];

        // System alerts snapshot
        $alerts = $this->systemAlerts();

        // Get pending sender ID applications (use SmsSenderIdRequest)
        $pending_sender_ids = \App\Models\Sms\SmsSenderIdRequest::with(['tenant', 'requestedBy'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Get online users (active sessions in last 12 hours)
        $online_users = $this->getOnlineUsers();

        // Tenants with subscription/trial expiring in 0-5 days
        $expiring_soon_tenants = $this->getExpiringSoonTenants();

        return view('admin.dashboard', compact(
            'stats',
            'recent_loans',
            'recent_repayments',
            'monthly_disbursements',
            'user_roles',
            'tenant_stats',
            'kpis',
            'alerts',
            'pending_sender_ids',
            'online_users',
            'expiring_soon_tenants'
        ));
    }

    /**
     * Date ranges for KPI toggles
     */
    private function dateRange(string $mode): array
    {
        $now = now();
        switch ($mode) {
            case 'today':
                return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
            case 'mtd':
                return [$now->copy()->startOfMonth(), $now->copy()->endOfDay()];
            case 'ytd':
                return [$now->copy()->startOfYear(), $now->copy()->endOfDay()];
            default:
                return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
        }
    }

    /**
     * Calculate global KPIs for a date range
     */
    private function calculateGlobalKpis($start, $end): array
    {
        // Active tenants & borrowers (remove tenant scope for system-wide stats)
        $activeTenants = Tenant::active()->count();
        $activeBorrowers = Client::withoutGlobalScope('tenant')->where('status', 'active')->count();

        // Loans outstanding = total_amount of active/disbursed loans minus all repayments on those loans
        $activeIds = Loan::withoutGlobalScope('tenant')->whereIn('status', ['disbursed', 'active'])->pluck('id');
        $activeTotalAmount = (float) Loan::withoutGlobalScope('tenant')->whereIn('status', ['disbursed', 'active'])->sum('total_amount');
        $activeTotalRepaid = (float) Repayment::withoutGlobalScope('tenant')->whereIn('loan_id', $activeIds)->sum('amount');
        $baseOutstanding = $activeTotalAmount - $activeTotalRepaid;

        // PAR30 / PAR60: outstanding on overdue loans (past due_date by 30/60 days)
        $overdue30Ids = Loan::withoutGlobalScope('tenant')->whereIn('status', ['disbursed', 'active'])
            ->whereHas('schedules', fn($q) => $q->where('status', 'pending')->where('due_date', '<', Carbon::now()->subDays(30)))
            ->pluck('id');
        $overdue30Total = (float) Loan::withoutGlobalScope('tenant')->whereIn('id', $overdue30Ids)->sum('total_amount');
        $overdue30Repaid = (float) Repayment::withoutGlobalScope('tenant')->whereIn('loan_id', $overdue30Ids)->sum('amount');
        $par30Outstanding = $overdue30Total - $overdue30Repaid;

        $overdue60Ids = Loan::withoutGlobalScope('tenant')->whereIn('status', ['disbursed', 'active'])
            ->whereHas('schedules', fn($q) => $q->where('status', 'pending')->where('due_date', '<', Carbon::now()->subDays(60)))
            ->pluck('id');
        $overdue60Total = (float) Loan::withoutGlobalScope('tenant')->whereIn('id', $overdue60Ids)->sum('total_amount');
        $overdue60Repaid = (float) Repayment::withoutGlobalScope('tenant')->whereIn('loan_id', $overdue60Ids)->sum('amount');
        $par60Outstanding = $overdue60Total - $overdue60Repaid;

        $par30Percent = $baseOutstanding > 0 ? round(($par30Outstanding / $baseOutstanding) * 100, 2) : 0;
        $par60Percent = $baseOutstanding > 0 ? round(($par60Outstanding / $baseOutstanding) * 100, 2) : 0;

        // Repayments collected in range - all tenants
        $repaymentsCollected = Repayment::withoutGlobalScope('tenant')->whereBetween('payment_date', [$start, $end])->sum('amount');

        // Disbursements in range (prefer disbursed_at if available) - all tenants
        $disbursements = Loan::withoutGlobalScope('tenant')->whereIn('status', ['disbursed', 'active'])
            ->whereBetween(DB::raw('COALESCE(disbursed_at, created_at)'), [$start, $end])
            ->sum('principal');

        // Wallet/Float balance (SMS credits from SmsBalance table) - all tenants
        $smsFloatBalance = SmsBalance::withoutGlobalScope('tenant')->sum('balance');

        // Get live Beem Africa balance
        $beemLiveBalance = 0;
        try {
            $smsManager = app(\App\Services\SmsManager::class);
            $balanceResult = $smsManager->getProviderBalance('beem_africa');
            if ($balanceResult['success']) {
                $beemLiveBalance = $balanceResult['balance'] ?? 0;
            }
        } catch (\Exception $e) {
            \Log::warning('Failed to fetch Beem Africa balance for dashboard', ['error' => $e->getMessage()]);
        }

        // MRR & churn
        $mrr = $this->calculateMrr();
        $churn = $this->calculateChurn($start, $end);

        return [
            'active_tenants' => $activeTenants,
            'active_borrowers' => $activeBorrowers,
            'loans_outstanding' => (int) $baseOutstanding,
            'par30' => $par30Percent,
            'par60' => $par60Percent,
            'repayments_collected' => (float) $repaymentsCollected,
            'disbursements' => (int) $disbursements,
            'wallet_sms_credits' => (int) $smsFloatBalance,
            'beem_live_balance' => (float) $beemLiveBalance,
            'mrr' => (int) $mrr,
            'churn_percent' => $churn,
        ];
    }

    /**
     * Calculate Monthly Recurring Revenue
     */
    private function calculateMrr(): int
    {
        $activeSubs = Subscription::active()->with('plan')->get();
        $total = 0;
        foreach ($activeSubs as $sub) {
            $price = $sub->plan ? (int) $sub->plan->price : 0;
            $period = $sub->plan ? $sub->plan->period : 'monthly';
            $monthly = $period === 'yearly' ? (int) round($price / 12) : $price;
            $total += $monthly;
        }
        return $total;
    }

    /**
     * Approximate churn percent = canceled subs in period / active subs at start
     */
    private function calculateChurn($start, $end): float
    {
        // Active subs at start (approx): active with current_period_end >= start
        $startingActive = Subscription::active()
            ->where('current_period_end', '>=', $start)
            ->count();

        // Canceled in period (approx): status = canceled and current_period_end between range
        $canceled = Subscription::where('status', 'canceled')
            ->whereBetween('current_period_end', [$start, $end])
            ->count();

        if ($startingActive <= 0) { return 0.0; }
        return round(($canceled / $startingActive) * 100, 2);
    }

    /**
     * System alerts snapshot
     */
    private function systemAlerts(): array
    {
        $failedPayments = Payment::where('status', 'failed')->count();
        $failedJobs = DB::table('failed_jobs')->count();
        $lowSmsTenants = SmsBalance::where('balance', '<', 50)->count();

        // Licenses expiring soon: subs ending in next 7 days or trial ending in next 7 days
        $soon = now()->addDays(7);
        $expiringSubs = Subscription::where('status', 'active')
            ->whereBetween('current_period_end', [now(), $soon])
            ->count();
        $trialExpiring = Tenant::whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [now(), $soon])
            ->count();

        return [
            'failed_payments' => $failedPayments,
            'failed_jobs' => $failedJobs,
            'low_sms_credits' => $lowSmsTenants,
            'licenses_expiring_soon' => $expiringSubs + $trialExpiring,
        ];
    }

    /**
     * Get system health metrics
     */
    public function systemHealth()
    {
        $health = [
            'database' => $this->checkDatabaseHealth(),
            'storage' => $this->checkStorageHealth(),
            'cache' => $this->checkCacheHealth(),
        ];

        return view('admin.system-health', compact('health'));
    }

    /**
     * Check database health
     */
    private function checkDatabaseHealth()
    {
        try {
            DB::connection()->getPdo();
            return ['status' => 'healthy', 'message' => 'Database connection is working'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Database connection failed'];
        }
    }

    /**
     * Check storage health
     */
    private function checkStorageHealth()
    {
        $disk = disk_free_space(storage_path());
        $total = disk_total_space(storage_path());
        $used_percentage = (($total - $disk) / $total) * 100;

        return [
            'status' => $used_percentage > 90 ? 'warning' : 'healthy',
            'used_percentage' => round($used_percentage, 2),
            'free_space' => $this->formatBytes($disk),
            'total_space' => $this->formatBytes($total)
        ];
    }

    /**
     * Check cache health
     */
    private function checkCacheHealth()
    {
        try {
            cache()->put('health_check', 'test', 60);
            $value = cache()->get('health_check');
            cache()->forget('health_check');
            
            return [
                'status' => $value === 'test' ? 'healthy' : 'error',
                'message' => $value === 'test' ? 'Cache is working' : 'Cache test failed'
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => 'Cache connection failed'];
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Get tenants whose subscription or trial expires in 0-5 days
     */
    private function getExpiringSoonTenants(): \Illuminate\Support\Collection
    {
        $now   = Carbon::now();
        $limit = Carbon::now()->addDays(5);

        // Tenants with an active subscription ending in 0-5 days
        $subTenantIds = Subscription::where('status', 'active')
            ->whereBetween('current_period_end', [$now, $limit])
            ->pluck('tenant_id');

        $fromSubs = Tenant::whereIn('id', $subTenantIds)
            ->with(['subscriptions' => function ($q) use ($now, $limit) {
                $q->where('status', 'active')
                  ->whereBetween('current_period_end', [$now, $limit]);
            }])
            ->get()
            ->map(function ($tenant) {
                $sub = $tenant->subscriptions->first();
                return [
                    'id'          => $tenant->id,
                    'name'        => $tenant->name,
                    'email'       => $tenant->contact_email,
                    'type'        => 'subscription',
                    'expires_at'  => $sub ? $sub->current_period_end : null,
                    'days_left'   => $sub ? (int) Carbon::now()->diffInDays($sub->current_period_end, false) : null,
                ];
            });

        // Tenants still on trial ending in 0-5 days
        $fromTrials = Tenant::where('status', 'active')
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [$now, $limit])
            ->whereNotIn('id', $subTenantIds)
            ->get()
            ->map(function ($tenant) {
                return [
                    'id'         => $tenant->id,
                    'name'       => $tenant->name,
                    'email'      => $tenant->contact_email,
                    'type'       => 'trial',
                    'expires_at' => $tenant->trial_ends_at,
                    'days_left'  => (int) Carbon::now()->diffInDays($tenant->trial_ends_at, false),
                ];
            });

        return collect($fromSubs)->merge(collect($fromTrials))->sortBy('days_left')->values();
    }

    /**
     * Get currently online users
     */
    private function getOnlineUsers()
    {
        // Get active sessions from last 12 hours
        $twelveHoursAgo = now()->subHours(12)->timestamp;
        
        $sessions = DB::table('sessions')
            ->where('last_activity', '>=', $twelveHoursAgo)
            ->whereNotNull('user_id')
            ->get();

        $onlineUsers = [];
        
        foreach ($sessions as $session) {
            $user = User::with('tenant')->find($session->user_id);
            
            if ($user) {
                $lastActivity = Carbon::createFromTimestamp($session->last_activity);
                $timeSpent = $lastActivity->diffForHumans(now(), true);
                
                $onlineUsers[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'tenant_name' => $user->tenant ? $user->tenant->name : 'N/A',
                    'last_activity' => $lastActivity,
                    'time_spent' => $timeSpent,
                    'ip_address' => $session->ip_address ?? 'N/A',
                ];
            }
        }

        // Sort by last activity (most recent first)
        usort($onlineUsers, function($a, $b) {
            return $b['last_activity']->timestamp <=> $a['last_activity']->timestamp;
        });

        return collect($onlineUsers);
    }
}