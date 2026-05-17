<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminExpense;
use App\Models\Payment;
use App\Models\SmsProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SuperadminAccountsController extends Controller
{
    private function authorizeAdmin(): void
    {
        if (!auth()->check() || !in_array(strtolower(auth()->user()->role), ['admin', 'super_admin', 'superadmin', 'super-admin', 'super admin'])) {
            abort(403, 'Unauthorized');
        }
    }

    // ─── Revenue / Sales ──────────────────────────────────────────────────────

    public function revenue(Request $request)
    {
        $this->authorizeAdmin();

        $period = $request->get('period', 'month');
        [$start, $end] = $this->periodRange($period, $request);

        // Subscription revenue (payments with status success/completed)
        $subscriptionRevenue = Payment::whereIn('status', ['success', 'completed'])
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');

        // SMS revenue: selling price (what tenants paid for SMS)
        $smsRevenue = DB::table('sms_purchases')
            ->where('status', 'approved')
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_amount');

        // SMS cost (buying price from provider × units sold)
        $smsCostPerUnit = SmsProvider::where('is_primary', true)->value('cost_per_sms') ?? 0;
        $smsUnitsSold = DB::table('sms_purchases')
            ->where('status', 'approved')
            ->whereBetween('created_at', [$start, $end])
            ->sum('quantity');
        $smsCost = $smsCostPerUnit * $smsUnitsSold;
        $smsMargin = $smsRevenue - $smsCost;

        // Daily breakdown
        $dailySubscription = Payment::whereIn('status', ['success', 'completed'])
            ->whereBetween('paid_at', [$start, $end])
            ->selectRaw('DATE(paid_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()->keyBy('date');

        $dailySms = DB::table('sms_purchases')
            ->where('status', 'approved')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get()->keyBy('date');

        // Recent payments — filtered to the selected period
        $recentPayments = Payment::whereIn('status', ['success', 'completed'])
            ->whereBetween('paid_at', [$start, $end])
            ->with('tenant')
            ->latest('paid_at')
            ->take(20)
            ->get();

        return view('admin.accounts.revenue', compact(
            'subscriptionRevenue', 'smsRevenue', 'smsCost', 'smsMargin',
            'smsUnitsSold', 'smsCostPerUnit',
            'dailySubscription', 'dailySms',
            'recentPayments', 'period', 'start', 'end'
        ));
    }

    // ─── Expenses ─────────────────────────────────────────────────────────────

    public function expenses(Request $request)
    {
        $this->authorizeAdmin();

        $period = $request->get('period', 'month');
        [$start, $end] = $this->periodRange($period, $request);

        $expenses = AdminExpense::whereBetween('expense_date', [$start, $end])
            ->orderByDesc('expense_date')
            ->get();

        $totalExpenses = $expenses->sum('amount');
        $byCategory    = $expenses->groupBy('category')
            ->map(fn($g) => $g->sum('amount'));

        return view('admin.accounts.expenses', compact(
            'expenses', 'totalExpenses', 'byCategory',
            'period', 'start', 'end'
        ));
    }

    public function storeExpense(Request $request)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'category'       => 'required|string|max:50',
            'description'    => 'required|string|max:255',
            'amount'         => 'required|numeric|min:0.01',
            'expense_date'   => 'required|date',
            'payment_method' => 'required|string|max:50',
            'reference'      => 'nullable|string|max:100',
            'notes'          => 'nullable|string|max:500',
        ]);

        AdminExpense::create(array_merge($validated, ['created_by' => auth()->id()]));

        return redirect()->route('admin.accounts.expenses')
            ->with('success', 'Expense recorded successfully.');
    }

    public function destroyExpense(AdminExpense $expense)
    {
        $this->authorizeAdmin();
        $expense->delete();
        return redirect()->route('admin.accounts.expenses')
            ->with('success', 'Expense deleted.');
    }

    // ─── Profit & Loss ────────────────────────────────────────────────────────

    public function profitLoss(Request $request)
    {
        $this->authorizeAdmin();

        $period = $request->get('period', 'month');
        [$start, $end] = $this->periodRange($period, $request);

        // Revenue streams
        $subscriptionRevenue = Payment::whereIn('status', ['success', 'completed'])
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');

        $smsRevenue = DB::table('sms_purchases')
            ->where('status', 'approved')
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_amount');

        $smsCostPerUnit = SmsProvider::where('is_primary', true)->value('cost_per_sms') ?? 0;
        $smsUnitsSold   = DB::table('sms_purchases')
            ->where('status', 'approved')
            ->whereBetween('created_at', [$start, $end])
            ->sum('quantity');
        $smsBuyingCost = $smsCostPerUnit * $smsUnitsSold;

        $totalRevenue = $subscriptionRevenue + $smsRevenue;
        $grossProfit  = $totalRevenue - $smsBuyingCost;   // revenue minus direct SMS cost

        // Operating expenses
        $expenses = AdminExpense::whereBetween('expense_date', [$start, $end])->get();
        $totalExpenses = $expenses->sum('amount');
        $byCategory    = $expenses->groupBy('category')->map(fn($g) => $g->sum('amount'));

        $netProfit = $grossProfit - $totalExpenses;

        // Monthly trend (last 12 months)
        $monthlyTrend = collect();
        for ($i = 11; $i >= 0; $i--) {
            $mStart = now()->startOfMonth()->subMonths($i);
            $mEnd   = $mStart->copy()->endOfMonth();
            $label  = $mStart->format('M Y');

            $mRev = Payment::whereIn('status', ['success', 'completed'])
                ->whereBetween('paid_at', [$mStart, $mEnd])->sum('amount');
            $mSmsRev = DB::table('sms_purchases')->where('status', 'approved')
                ->whereBetween('created_at', [$mStart, $mEnd])->sum('total_amount');
            $mSmsCost = $smsCostPerUnit * (DB::table('sms_purchases')->where('status', 'approved')
                ->whereBetween('created_at', [$mStart, $mEnd])->sum('quantity'));
            $mExp = AdminExpense::whereBetween('expense_date', [$mStart, $mEnd])->sum('amount');
            $mNet = ($mRev + $mSmsCost) - $mSmsCost - $mExp;  // simplified

            $monthlyTrend->push([
                'label'   => $label,
                'revenue' => $mRev + $mSmsRev,
                'expense' => $mExp + $mSmsCost,
                'net'     => ($mRev + $mSmsRev) - $mSmsCost - $mExp,
            ]);
        }

        return view('admin.accounts.profit-loss', compact(
            'subscriptionRevenue', 'smsRevenue', 'smsBuyingCost',
            'totalRevenue', 'grossProfit', 'totalExpenses', 'netProfit',
            'byCategory', 'monthlyTrend',
            'period', 'start', 'end'
        ));
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    private function periodRange(string $period, Request $request): array
    {
        return match ($period) {
            'today'  => [now()->startOfDay(), now()->endOfDay()],
            'week'   => [now()->startOfWeek(), now()->endOfWeek()],
            'year'   => [now()->startOfYear(), now()->endOfYear()],
            'custom' => [
                Carbon::parse($request->get('from', now()->startOfMonth())),
                Carbon::parse($request->get('to',   now()->endOfMonth())),
            ],
            default  => [now()->startOfMonth(), now()->endOfMonth()], // month
        };
    }
}
