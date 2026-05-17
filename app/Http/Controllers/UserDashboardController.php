<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\Repayment;
use App\Models\LoanProduct;
use App\Models\Subscription;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserDashboardController extends Controller
{
    /**
     * Display the user dashboard.
     */
    public function index()
    {
        $user = auth()->user();
        
        // Redirect admin staff to admin dashboard
        if ($user->admin_role_id) {
            return redirect()->route('admin.dashboard');
        }
        
        // Redirect superadmin to admin dashboard
        $superAliases = ['super_admin', 'superadmin', 'super-admin', 'super admin'];
        $isSuperAdmin = in_array(strtolower($user->role ?? ''), $superAliases) || 
                       strtolower($user->position ?? '') === 'superadmin';
        
        if ($isSuperAdmin) {
            return redirect()->route('admin.dashboard');
        }

        // ---------------------------------------------------------------
        // PORTFOLIO & OUTSTANDING — industry-standard schedule-based calc
        // All queries are automatically tenant-scoped via BaseModel scope
        // ---------------------------------------------------------------

        $activeLoanIds = Loan::whereIn('status', ['disbursed', 'active', 'partially_paid'])->pluck('id');

        // PORTFOLIO VALUE = sum of principal disbursed for active/disbursed loans
        $portfolioValue = (float) Loan::whereIn('status', ['disbursed', 'active', 'partially_paid'])->sum('principal');

        // OUTSTANDING BALANCE — hybrid approach:
        // If schedule paid_amounts are synced (sum > 0), use schedule-based calc.
        // Otherwise fall back to loan.total_amount - repayments (for legacy/imported data).
        $schedPaidTotal = (float) LoanSchedule::whereIn('loan_id', $activeLoanIds)->sum('paid_amount');
        $totalRepaidActive = (float) Repayment::whereIn('loan_id', $activeLoanIds)->sum('amount');

        if ($schedPaidTotal > 0 || $totalRepaidActive == 0) {
            // Schedules are synced — use schedule remaining
            $outstandingBalance = (float) LoanSchedule::whereIn('loan_id', $activeLoanIds)
                ->whereIn('status', ['pending', 'partial'])
                ->selectRaw('SUM(total_amount - paid_amount) as remaining')
                ->value('remaining') ?? 0.0;
        } else {
            // Schedules unsynced — use total_repayable minus repayments
            $totalRepayable = (float) Loan::whereIn('id', $activeLoanIds)->sum('total_amount');
            $outstandingBalance = max(0.0, $totalRepayable - $totalRepaidActive);
        }
        $outstandingBalance = max(0.0, $outstandingBalance);

        // OVERDUE AMOUNT (for PAR) = outstanding on schedules that are past due date
        $overdueScheduleAmount = (float) LoanSchedule::whereIn('loan_id', $activeLoanIds)
            ->whereIn('status', ['pending', 'partial'])
            ->where('due_date', '<', now()->toDateString())
            ->selectRaw('SUM(total_amount - paid_amount) as overdue_remaining')
            ->value('overdue_remaining') ?? 0.0;
        $overdueScheduleAmount = max(0.0, $overdueScheduleAmount);

        // OVERDUE LOAN COUNT = loans with at least one pending past-due schedule
        $overdueIds = Loan::overdue()->pluck('id');
        $overdueAmount = $overdueScheduleAmount; // use schedule-based amount

        // REPAYMENT RATE = collected / expected on due schedules × 100
        // If schedules unsynced, use repayments vs expected from schedules
        $totalExpectedDue = (float) LoanSchedule::whereIn('loan_id', $activeLoanIds)
            ->where('due_date', '<=', now()->toDateString())
            ->sum('total_amount');
        $totalCollectedDue = $schedPaidTotal > 0
            ? (float) LoanSchedule::whereIn('loan_id', $activeLoanIds)
                ->where('due_date', '<=', now()->toDateString())
                ->sum('paid_amount')
            : $totalRepaidActive;

        // PAR (Portfolio at Risk) = outstanding balance of overdue loans / total outstanding × 100
        $portfolioAtRisk = $outstandingBalance > 0
            ? round(($overdueScheduleAmount / $outstandingBalance) * 100, 2)
            : 0.0;

        // Repayment rate based on schedule dues
        $repaymentRate = $totalExpectedDue > 0
            ? round(($totalCollectedDue / $totalExpectedDue) * 100, 2)
            : 0.0;

        $allTimeLoanIds = Loan::whereIn('status', ['disbursed', 'active', 'partially_paid', 'completed'])->pluck('id');

        $stats = [
            'total_clients'             => Client::count(),
            'total_loans'               => Loan::count(),
            'total_loan_amount'         => $portfolioValue,       // Portfolio Value = principal of active loans
            'total_principal_disbursed' => (float) Loan::whereIn('id', $allTimeLoanIds)->sum('principal'),
            'total_repayable'           => (float) Loan::whereIn('id', $allTimeLoanIds)->sum('total_amount'),
            'total_repayments'          => Repayment::whereIn('loan_id', $activeLoanIds)->sum('amount'),
            'active_loans'              => Loan::whereIn('status', ['disbursed', 'active', 'partially_paid'])->count(),
            'pending_loans'             => Loan::where('status', 'pending')->count(),
            'overdue_loans'             => $overdueIds->count(),
            'overdue_amount'            => $overdueAmount,
            'completed_loans'           => Loan::where('status', 'completed')->count(),
        ];

        $todayDate = now()->toDateString();

        // TODAY'S repayments — schedules due exactly today
        $today_repayments = LoanSchedule::with(['loan.client'])
            ->whereIn('status', ['pending', 'partial'])
            ->where('due_date', $todayDate)
            ->whereHas('loan', function ($q) {
                $q->whereIn('status', ['disbursed', 'active', 'partially_paid']);
            })
            ->orderBy('due_date')
            ->get();

        // UPCOMING repayments — schedule-based (tomorrow to +7 days), excludes today
        $upcoming_repayments = LoanSchedule::with(['loan.client'])
            ->whereIn('status', ['pending', 'partial'])
            ->whereBetween('due_date', [
                now()->addDay()->toDateString(),
                now()->addDays(7)->toDateString(),
            ])
            ->whereHas('loan', function ($q) {
                $q->whereIn('status', ['disbursed', 'active', 'partially_paid']);
            })
            ->orderBy('due_date')
            ->take(10)
            ->get();

        // OVERDUE loans — unique loans with overdue unpaid schedules
        $overdue_loans = Loan::with(['client', 'schedules' => function ($q) use ($todayDate) {
                $q->whereIn('status', ['pending', 'partial'])
                  ->where('due_date', '<', $todayDate);
            }])
            ->whereIn('status', ['disbursed', 'active', 'partially_paid'])
            ->whereHas('schedules', function ($q) use ($todayDate) {
                $q->whereIn('status', ['pending', 'partial'])
                  ->where('due_date', '<', $todayDate);
            })
            ->latest('created_at')
            ->take(10)
            ->get()
            ->each(function ($loan) {
                $loan->total_overdue_amount = $loan->schedules->sum(function ($s) {
                    return $s->total_amount - $s->paid_amount;
                });
            });

        // Recent entities
        $recent_clients = Client::latest()->take(5)->get();
        $recent_loans = Loan::with('client')->latest()->take(5)->get();
        // Only show repayments for disbursed/active loans (excludes pending/approved new loans)
        $recent_repayments = Repayment::with('loan.client')->whereIn('loan_id', $activeLoanIds)->latest()->take(5)->get();

        // Loan products
        $loan_products = LoanProduct::orderBy('name')->get();

        // Monthly loan disbursements (SQLite-friendly)
        $currentYear = (int) date('Y');
        $monthly_disbursements = collect();
        for ($m = 1; $m <= 12; $m++) {
            $start = Carbon::createFromDate($currentYear, $m, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $total = Loan::whereBetween('created_at', [$start, $end])
                        ->sum('principal');
            $count = Loan::whereBetween('created_at', [$start, $end])
                        ->count();
            $monthly_disbursements->push([
                'month' => $m,
                'year' => $currentYear,
                'total_amount' => $total,
                'loan_count' => $count,
            ]);
        }

        // Loan status distribution
        $loan_status_distribution = Loan::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        // Subscription and billing info (tenant scoped)
        $subscription = Subscription::where('tenant_id', session('tenant_id'))
            ->with('plan')
            ->first();

        $pending_invoice = Invoice::where('tenant_id', session('tenant_id'))
            ->where('status', 'pending')
            ->latest()
            ->first();

        // Notifications
        $notifications = $user->notifications()->latest()->take(10)->get();
        $unreadNotificationsCount = $user->unreadNotifications()->count();

        // Portfolio metrics for dashboard widgets
        $portfolio_metrics = [
            'outstanding_balance' => $outstandingBalance,
            'repayment_rate'      => $repaymentRate,
            'portfolio_at_risk'   => $portfolioAtRisk,
        ];

        // Get all available plans for trial users
        $plans = Plan::active()->orderBy('price')->get();

        // Get current tenant for trial information
        $tenant = Tenant::find(session('tenant_id'));
        $trialEndsAt = optional($tenant)->trial_ends_at;
        $trialMinutesLeft = $trialEndsAt ? now()->diffInMinutes(\Carbon\Carbon::parse($trialEndsAt), false) : null;
        $isTrialActive = $tenant && $trialEndsAt && $trialMinutesLeft !== null && $trialMinutesLeft > 0;

        // Get online staff only for tenant admins (not regular staff)
        $online_staff = collect([]);
        if ($user->isAdmin() || strtolower($user->role) === 'admin' || strtolower($user->role) === 'administrator') {
            $online_staff = $this->getOnlineStaff(session('tenant_id'));
        }

        return view('user.dashboard', compact(
            'stats',
            'recent_clients',
            'recent_loans',
            'recent_repayments',
            'today_repayments',
            'upcoming_repayments',
            'overdue_loans',
            'loan_products',
            'monthly_disbursements',
            'loan_status_distribution',
            'subscription',
            'pending_invoice',
            'portfolio_metrics',
            'notifications',
            'unreadNotificationsCount',
            'plans',
            'tenant',
            'trialEndsAt',
            'trialMinutesLeft',
            'isTrialActive',
            'online_staff'
        ));
    }

    /**
     * Get currently online staff for the tenant
     */
    private function getOnlineStaff($tenantId)
    {
        // Get active sessions from last 12 hours
        $twelveHoursAgo = now()->subHours(12)->timestamp;
        
        $sessions = DB::table('sessions')
            ->where('last_activity', '>=', $twelveHoursAgo)
            ->whereNotNull('user_id')
            ->get();

        $onlineStaff = [];
        
        foreach ($sessions as $session) {
            $user = User::find($session->user_id);
            
            // Only include users from this tenant
            if ($user && $user->tenant_id == $tenantId) {
                $lastActivity = Carbon::createFromTimestamp($session->last_activity);
                $timeSpent = $lastActivity->diffForHumans(now(), true);
                
                $onlineStaff[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'last_activity' => $lastActivity,
                    'time_spent' => $timeSpent,
                    'ip_address' => $session->ip_address ?? 'N/A',
                ];
            }
        }

        // Sort by last activity (most recent first)
        usort($onlineStaff, function($a, $b) {
            return $b['last_activity']->timestamp <=> $a['last_activity']->timestamp;
        });

        return collect($onlineStaff);
    }
}