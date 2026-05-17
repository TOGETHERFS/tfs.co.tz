<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Loan;
use App\Models\Repayment;
use App\Models\LoanSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the application dashboard.
     */
    public function index()
    {
        // Get key statistics
        $stats = $this->getStatistics();
        
        // Get recent activities
        $recentLoans = Loan::with(['client', 'product'])
                          ->latest()
                          ->take(5)
                          ->get();

        $recentRepayments = Repayment::with(['loan.client'])
                                   ->latest()
                                   ->take(5)
                                   ->get();

        // Get overdue loans
        $overdueLoans = Loan::with('client')
                           ->overdue()
                           ->take(10)
                           ->get();

        // Get upcoming payments (only for disbursed/active loans)
        $upcomingPayments = LoanSchedule::with(['loan.client'])
                                       ->where('status', 'pending')
                                       ->where('due_date', '>=', now())
                                       ->where('due_date', '<=', now()->addDays(7))
                                       ->whereHas('loan', function ($q) {
                                           $q->whereIn('status', ['disbursed', 'active']);
                                       })
                                       ->orderBy('due_date')
                                       ->take(10)
                                       ->get();

        // Get monthly trends
        $monthlyTrends = $this->getMonthlyTrends();

        // Get online users for this tenant
        $online_users = $this->getTenantOnlineUsers();

        return view('dashboard.index', compact(
            'stats',
            'recentLoans',
            'recentRepayments',
            'overdueLoans',
            'upcomingPayments',
            'monthlyTrends',
            'online_users'
        ));
    }

    /**
     * Get dashboard statistics using industry-standard schedule-based formulas.
     * All queries are tenant-scoped via BaseModel global scope.
     */
    private function getStatistics()
    {
        $activeLoanIds = Loan::whereIn('status', ['disbursed', 'active', 'partially_paid'])->pluck('id');

        // PORTFOLIO VALUE = sum of principal for active/disbursed loans (what was lent out)
        $portfolioValue = (float) Loan::whereIn('status', ['disbursed', 'active', 'partially_paid'])->sum('principal');

        // OUTSTANDING BALANCE — hybrid approach:
        // If schedule paid_amounts are synced (sum > 0), use schedule-based calc.
        // Otherwise fall back to loan.total_amount - repayments (for legacy/imported data).
        $schedPaidTotal    = (float) LoanSchedule::whereIn('loan_id', $activeLoanIds)->sum('paid_amount');
        $totalRepaidActive = (float) Repayment::whereIn('loan_id', $activeLoanIds)->sum('amount');

        if ($schedPaidTotal > 0 || $totalRepaidActive == 0) {
            $outstandingBalance = (float) LoanSchedule::whereIn('loan_id', $activeLoanIds)
                ->whereIn('status', ['pending', 'partial'])
                ->selectRaw('SUM(total_amount - paid_amount) as remaining')
                ->value('remaining') ?? 0.0;
        } else {
            $totalRepayable     = (float) Loan::whereIn('id', $activeLoanIds)->sum('total_amount');
            $outstandingBalance  = max(0.0, $totalRepayable - $totalRepaidActive);
        }
        $outstandingBalance = max(0.0, $outstandingBalance);

        // OVERDUE AMOUNT = outstanding on past-due schedules of active loans
        $overdueScheduleAmount = (float) LoanSchedule::whereIn('loan_id', $activeLoanIds)
            ->whereIn('status', ['pending', 'partial'])
            ->where('due_date', '<', now()->toDateString())
            ->selectRaw('SUM(total_amount - paid_amount) as overdue_remaining')
            ->value('overdue_remaining') ?? 0.0;
        $overdueScheduleAmount = max(0.0, $overdueScheduleAmount);

        // PAR = overdue outstanding / total outstanding × 100
        $portfolioAtRisk = $outstandingBalance > 0
            ? round(($overdueScheduleAmount / $outstandingBalance) * 100, 2)
            : 0.0;

        // COLLECTION RATE = paid_amount / total_amount for all due schedules × 100
        $totalExpectedDue = (float) LoanSchedule::whereIn('loan_id', $activeLoanIds)
            ->where('due_date', '<=', now()->toDateString())
            ->sum('total_amount');
        $totalCollectedDue = (float) LoanSchedule::whereIn('loan_id', $activeLoanIds)
            ->where('due_date', '<=', now()->toDateString())
            ->sum('paid_amount');
        $collectionRate = $totalExpectedDue > 0
            ? round(($totalCollectedDue / $totalExpectedDue) * 100, 2)
            : 100.0;

        return [
            // Client statistics
            'total_clients'            => Client::count(),
            'active_clients'           => Client::active()->count(),
            'new_clients_this_month'   => Client::whereBetween('created_at', [
                                             now()->startOfMonth(),
                                             now()->endOfMonth(),
                                         ])->count(),

            // Loan statistics
            'total_loans'    => Loan::count(),
            'active_loans'   => Loan::whereIn('status', ['disbursed', 'active', 'partially_paid'])->count(),
            'pending_loans'  => Loan::where('status', 'pending')->count(),
            'overdue_loans'  => Loan::overdue()->count(),

            // Financial statistics
            'total_disbursed'          => $portfolioValue,
            'total_outstanding'        => $outstandingBalance,
            'total_collected'          => Repayment::whereIn('loan_id', $activeLoanIds)->sum('amount'),
            'collections_today'        => Repayment::whereIn('loan_id', $activeLoanIds)
                                              ->whereDate('payment_date', today())->sum('amount'),
            'collections_this_month'   => Repayment::whereIn('loan_id', $activeLoanIds)
                                              ->whereBetween('payment_date', [
                                                  now()->startOfMonth(),
                                                  now()->endOfMonth(),
                                              ])->sum('amount'),

            // Portfolio health
            'portfolio_at_risk' => $portfolioAtRisk,
            'collection_rate'   => $collectionRate,
        ];
    }

    /**
     * Get monthly trends for charts.
     */
    private function getMonthlyTrends()
    {
        $months = [];
        $disbursements = [];
        $collections = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');

            // Disbursements
            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();
            $disbursements[] = Loan::whereBetween('disbursed_at', [$start, $end])
                                  ->sum('principal');

            // Collections - only from disbursed/active loans
            $collections[] = Repayment::whereHas('loan', function ($q) {
                                         $q->whereIn('status', ['disbursed', 'active', 'partially_paid']);
                                     })->whereBetween('payment_date', [$start, $end])
                                     ->sum('amount');
        }

        return [
            'months' => $months,
            'disbursements' => $disbursements,
            'collections' => $collections,
        ];
    }

    /**
     * Get statistics for API endpoints.
     */
    public function apiStats()
    {
        return response()->json($this->getStatistics());
    }

    /**
     * Get chart data for API.
     */
    public function chartData(Request $request)
    {
        $type = $request->get('type', 'monthly');

        switch ($type) {
            case 'monthly':
                return response()->json($this->getMonthlyTrends());
            
            case 'loan_status':
                return response()->json([
                    'labels' => ['Pending', 'Active', 'Completed', 'Overdue'],
                    'data' => [
                        Loan::where('status', 'pending')->count(),
                        Loan::where('status', 'active')->count(),
                        Loan::where('status', 'completed')->count(),
                        Loan::overdue()->count(),
                    ]
                ]);

            case 'client_status':
                return response()->json([
                    'labels' => ['Active', 'Inactive'],
                    'data' => [
                        Client::where('status', 'active')->count(),
                        Client::where('status', 'inactive')->count(),
                    ]
                ]);

            default:
                return response()->json(['error' => 'Invalid chart type'], 400);
        }
    }

    /**
     * Get recent activities for API.
     */
    public function recentActivities()
    {
        $activities = collect();

        // Recent loans
        $recentLoans = Loan::with('client')
                          ->latest()
                          ->take(5)
                          ->get()
                          ->map(function ($loan) {
                              return [
                                  'type' => 'loan',
                                  'title' => "New loan for {$loan->client->full_name}",
                                  'amount' => $loan->principal,
                                  'date' => $loan->created_at,
                                  'status' => $loan->status,
                              ];
                          });

        // Recent repayments
        $recentRepayments = Repayment::with('loan.client')
                                   ->latest()
                                   ->take(5)
                                   ->get()
                                   ->map(function ($repayment) {
                                       return [
                                           'type' => 'repayment',
                                           'title' => "Payment from {$repayment->loan->client->full_name}",
                                           'amount' => $repayment->amount,
                                           'date' => $repayment->payment_date,
                                           'status' => 'completed',
                                       ];
                                   });

        $activities = $activities->merge($recentLoans)
                                ->merge($recentRepayments)
                                ->sortByDesc('date')
                                ->take(10)
                                ->values();

        return response()->json($activities);
    }

    /**
     * Get alerts and notifications.
     */
    public function alerts()
    {
        $alerts = [];

        // Overdue loans alert
        $overdueCount = Loan::overdue()->count();
        if ($overdueCount > 0) {
            $alerts[] = [
                'type' => 'danger',
                'title' => 'Overdue Loans',
            ];
        }

        // Upcoming payments (only for disbursed/active loans)
        $upcomingCount = LoanSchedule::where('status', 'pending')
                                   ->where('due_date', '>=', now())
                                   ->where('due_date', '<=', now()->addDays(3))
                                   ->whereHas('loan', function ($q) {
                                       $q->whereIn('status', ['disbursed', 'active']);
                                   })
                                   ->count();

        if ($upcomingCount > 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => 'Upcoming Payments',
                'message' => "{$upcomingCount} payments are due in the next 3 days.",
                'action' => route('dashboard'),
            ];
        }

        return response()->json($alerts);
    }

    /**
     * Get online users for the current tenant (active in last 30 minutes).
     */
    private function getTenantOnlineUsers()
    {
        try {
            $tenantId = auth()->user()->tenant_id;
            $thirtyMinutesAgo = now()->subMinutes(30)->timestamp;

            // Get user IDs belonging to this tenant
            $tenantUserIds = User::withoutGlobalScope('tenant')
                ->where('tenant_id', $tenantId)
                ->pluck('id');

            $sessions = DB::table('sessions')
                ->where('last_activity', '>=', $thirtyMinutesAgo)
                ->whereIn('user_id', $tenantUserIds)
                ->get();

            $onlineUsers = [];
            foreach ($sessions as $session) {
                $user = User::withoutGlobalScope('tenant')->find($session->user_id);
                if ($user) {
                    $lastActivity = Carbon::createFromTimestamp($session->last_activity);
                    $onlineUsers[] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role ?? 'staff',
                        'last_activity' => $lastActivity,
                        'time_spent' => $lastActivity->diffForHumans(now(), true),
                        'ip_address' => $session->ip_address ?? 'N/A',
                    ];
                }
            }

            usort($onlineUsers, fn($a, $b) => $b['last_activity']->timestamp <=> $a['last_activity']->timestamp);

            return collect($onlineUsers);
        } catch (\Exception $e) {
            return collect([]);
        }
    }

    private function calculateTotalDisbursed()
    {
        $totalWithInterest = Loan::whereIn('status', ['disbursed', 'active', 'completed'])
                        ->sum('total_amount');

        if ($totalWithInterest > 0) {
            return $totalWithInterest;
        }

        return Loan::whereIn('status', ['disbursed', 'active', 'completed'])
                    ->sum('principal');
    }
}