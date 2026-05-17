<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use App\Models\SalaryAdvance;
use App\Models\User;
use App\Services\Accounting\AutomatedAccountingService;
use App\Services\TanzaniaTaxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class PayrollController extends Controller
{
    // -------------------------------------------------------
    // Admin: List all salaries
    // -------------------------------------------------------
    public function index(Request $request)
    {
        $tenantId = session('tenant_id') ?? Auth::user()->tenant_id;
        $month    = $request->get('month', '');

        $query = Salary::where('tenant_id', $tenantId)->with('user');
        if ($month) {
            $query->where('month', $month);
        }
        $salaries = $query->orderBy('month', 'desc')->orderBy('created_at', 'desc')->get();

        $staff = User::where('tenant_id', $tenantId)->get();

        return view('payroll.index', compact('salaries', 'staff', 'month'));
    }

    // -------------------------------------------------------
    // Admin: Show create salary form
    // -------------------------------------------------------
    public function create()
    {
        $tenantId = session('tenant_id') ?? Auth::user()->tenant_id;
        $staff    = User::where('tenant_id', $tenantId)->get();
        $month    = now()->format('Y-m');
        return view('payroll.create', compact('staff', 'month'));
    }

    // -------------------------------------------------------
    // Admin: Store salary record
    // -------------------------------------------------------
    public function store(Request $request)
    {
        $tenantId = session('tenant_id') ?? Auth::user()->tenant_id;

        $request->validate([
            'user_id'                => 'required|exists:users,id',
            'month'                  => 'required|string',
            'basic_salary'           => 'required|numeric|min:0',
            'allowance_names'        => 'nullable|array',
            'allowance_amounts'      => 'nullable|array',
            'deduction_names'        => 'nullable|array',
            'deduction_amounts'      => 'nullable|array',
            'payment_date'           => 'nullable|date',
        ]);

        // Build allowances breakdown
        $allowancesBreakdown = [];
        $totalAllowances     = 0;
        if ($request->filled('allowance_names')) {
            foreach ($request->allowance_names as $i => $name) {
                $amount = (float) ($request->allowance_amounts[$i] ?? 0);
                if ($name && $amount > 0) {
                    $allowancesBreakdown[] = ['name' => $name, 'amount' => $amount];
                    $totalAllowances += $amount;
                }
            }
        }

        $basicSalary = (float) $request->basic_salary;
        $grossIncome = $basicSalary + $totalAllowances;

        // Auto-calculate statutory deductions (PAYE, NSSF) unless user opted out
        $statutory = TanzaniaTaxService::calculate($grossIncome);
        $employerContributions = TanzaniaTaxService::employerContributionsBreakdown($grossIncome);

        // Build deductions breakdown: start with statutory, then add custom
        $deductionsBreakdown = TanzaniaTaxService::deductionsBreakdown($grossIncome);
        $totalDeductions     = $statutory['total_employee_deductions'];

        if ($request->filled('deduction_names')) {
            foreach ($request->deduction_names as $i => $name) {
                $amount = (float) ($request->deduction_amounts[$i] ?? 0);
                if ($name && $amount > 0) {
                    $deductionsBreakdown[] = ['name' => $name, 'amount' => $amount, 'type' => 'custom', 'party' => 'employee'];
                    $totalDeductions += $amount;
                }
            }
        }

        $netSalary = round(max(0, $grossIncome - $totalDeductions), 2);

        $salary = Salary::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'user_id'   => $request->user_id,
                'month'     => $request->month,
            ],
            [
                'basic_salary'             => $basicSalary,
                'allowances'               => $totalAllowances,
                'allowances_breakdown'     => $allowancesBreakdown,
                'deductions'               => $totalDeductions,
                'deductions_breakdown'     => $deductionsBreakdown,
                'employer_contributions'   => $employerContributions,
                'net_salary'               => $netSalary,
                'payment_date'             => $request->payment_date,
                'status'                   => $request->get('status', 'draft'),
                'created_by'               => Auth::id(),
            ]
        );

        // Record accounting journal entry when salary is marked as paid
        if ($salary->status === 'paid') {
            try {
                app(AutomatedAccountingService::class)->recordSalaryPayment($salary->load('user'));
            } catch (\Throwable $e) {
                Log::warning('Accounting entry for salary failed silently', ['salary_id' => $salary->id, 'error' => $e->getMessage()]);
            }
        }

        return redirect()->route('payroll.index')
            ->with('success', __('messages.salary_saved'));
    }

    // -------------------------------------------------------
    // Admin: Edit salary
    // -------------------------------------------------------
    public function edit(Salary $salary)
    {
        $tenantId = session('tenant_id') ?? Auth::user()->tenant_id;
        abort_unless($salary->tenant_id == $tenantId, 403);
        $staff = User::where('tenant_id', $tenantId)->get();
        return view('payroll.edit', compact('salary', 'staff'));
    }

    // -------------------------------------------------------
    // Admin: Update salary
    // -------------------------------------------------------
    public function update(Request $request, Salary $salary)
    {
        $tenantId = session('tenant_id') ?? Auth::user()->tenant_id;
        abort_unless($salary->tenant_id == $tenantId, 403);

        $request->validate([
            'basic_salary'    => 'required|numeric|min:0',
            'allowance_names' => 'nullable|array',
            'allowance_amounts'=> 'nullable|array',
            'deduction_names' => 'nullable|array',
            'deduction_amounts'=> 'nullable|array',
            'payment_date'    => 'nullable|date',
        ]);

        $allowancesBreakdown = [];
        $totalAllowances = 0;
        if ($request->filled('allowance_names')) {
            foreach ($request->allowance_names as $i => $name) {
                $amount = (float)($request->allowance_amounts[$i] ?? 0);
                if ($name && $amount > 0) {
                    $allowancesBreakdown[] = ['name' => $name, 'amount' => $amount];
                    $totalAllowances += $amount;
                }
            }
        }

        $basicSalary = (float)$request->basic_salary;
        $grossIncome = $basicSalary + $totalAllowances;

        $statutory             = TanzaniaTaxService::calculate($grossIncome);
        $employerContributions = TanzaniaTaxService::employerContributionsBreakdown($grossIncome);

        $deductionsBreakdown = TanzaniaTaxService::deductionsBreakdown($grossIncome);
        $totalDeductions     = $statutory['total_employee_deductions'];

        if ($request->filled('deduction_names')) {
            foreach ($request->deduction_names as $i => $name) {
                $amount = (float)($request->deduction_amounts[$i] ?? 0);
                if ($name && $amount > 0) {
                    $deductionsBreakdown[] = ['name' => $name, 'amount' => $amount, 'type' => 'custom', 'party' => 'employee'];
                    $totalDeductions += $amount;
                }
            }
        }

        $netSalary = round(max(0, $grossIncome - $totalDeductions), 2);

        $wasAlreadyPaid = $salary->status === 'paid';
        $newStatus      = $request->get('status', $salary->status);

        $salary->update([
            'basic_salary'           => $basicSalary,
            'allowances'             => $totalAllowances,
            'allowances_breakdown'   => $allowancesBreakdown,
            'deductions'             => $totalDeductions,
            'deductions_breakdown'   => $deductionsBreakdown,
            'employer_contributions' => $employerContributions,
            'net_salary'             => $netSalary,
            'payment_date'           => $request->payment_date,
            'status'                 => $newStatus,
        ]);

        // Record accounting journal entry only when transitioning to paid for the first time
        if ($newStatus === 'paid' && !$wasAlreadyPaid) {
            try {
                app(AutomatedAccountingService::class)->recordSalaryPayment($salary->fresh()->load('user'));
            } catch (\Throwable $e) {
                Log::warning('Accounting entry for salary update failed silently', ['salary_id' => $salary->id, 'error' => $e->getMessage()]);
            }
        }

        return redirect()->route('payroll.index')
            ->with('success', __('messages.salary_updated'));
    }

    // -------------------------------------------------------
    // Staff: View own salary slips
    // -------------------------------------------------------
    public function mySlips()
    {
        $user     = Auth::user();
        $salaries = Salary::where('user_id', $user->id)
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('month', 'desc')
            ->get();
        return view('payroll.my-slips', compact('salaries'));
    }

    // -------------------------------------------------------
    // Download salary slip as PDF
    // -------------------------------------------------------
    public function downloadSlip(Salary $salary)
    {
        $tenantId = session('tenant_id') ?? Auth::user()->tenant_id;
        $user     = Auth::user();

        // Admin can see all, staff can only see own
        if (!$user->isAdmin() && $salary->user_id !== $user->id) {
            abort(403);
        }
        abort_unless($salary->tenant_id == $tenantId, 403);

        $salary->load('user');

        $grossIncome = (float) $salary->basic_salary + (float) $salary->allowances;

        // Always ensure deductions_breakdown and employer_contributions are populated in memory
        $statutory             = TanzaniaTaxService::calculate($grossIncome);
        $deductionsBreakdown   = TanzaniaTaxService::deductionsBreakdown($grossIncome);
        $employerContributions = TanzaniaTaxService::employerContributionsBreakdown($grossIncome);
        $totalDeductions       = $statutory['total_employee_deductions'];
        $netSalary             = round(max(0, $grossIncome - $totalDeductions), 2);

        // For old records with 0 deductions — persist recalculated values
        if ((float) $salary->deductions == 0 && $grossIncome > 0) {
            try {
                $salary->update([
                    'deductions'             => $totalDeductions,
                    'deductions_breakdown'   => $deductionsBreakdown,
                    'employer_contributions' => $employerContributions,
                    'net_salary'             => $netSalary,
                ]);
                $salary->refresh();
            } catch (\Throwable $e) {
                // Column may not exist yet — set values in memory only
                $salary->deductions           = $totalDeductions;
                $salary->net_salary           = $netSalary;
                Log::warning('Could not persist statutory deductions for salary #' . $salary->id . ': ' . $e->getMessage());
            }
        }

        // Inject in-memory for PDF even if DB columns are missing
        if (empty($salary->deductions_breakdown)) {
            $salary->setRawAttributes(array_merge($salary->getAttributes(), [
                'deductions_breakdown' => json_encode($deductionsBreakdown),
            ]), true);
        }
        if (empty($salary->employer_contributions)) {
            $salary->setRawAttributes(array_merge($salary->getAttributes(), [
                'employer_contributions' => json_encode($employerContributions),
            ]), true);
        }

        $pdf = Pdf::loadView('payroll.slip-pdf', compact('salary'))
            ->setPaper('a4', 'portrait');

        return $pdf->download('salary-slip-' . $salary->user->name . '-' . $salary->month . '.pdf');
    }

    // -------------------------------------------------------
    // Staff: Apply for salary advance
    // -------------------------------------------------------
    public function advanceCreate()
    {
        return view('payroll.advance-create');
    }

    public function advanceStore(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'reason' => 'nullable|string|max:500',
        ]);

        $user = Auth::user();
        SalaryAdvance::create([
            'tenant_id'      => $user->tenant_id,
            'user_id'        => $user->id,
            'amount'         => $request->amount,
            'reason'         => $request->reason,
            'requested_date' => now()->toDateString(),
            'status'         => 'pending',
        ]);

        return redirect()->route('payroll.advances')
            ->with('success', __('messages.advance_applied'));
    }

    // -------------------------------------------------------
    // Staff: View own advances
    // -------------------------------------------------------
    public function advances()
    {
        $user     = Auth::user();
        $advances = SalaryAdvance::where('user_id', $user->id)
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('created_at', 'desc')
            ->get();
        return view('payroll.advances', compact('advances'));
    }

    // -------------------------------------------------------
    // Admin: Manage all advance requests
    // -------------------------------------------------------
    public function advancesAdmin()
    {
        $tenantId = session('tenant_id') ?? Auth::user()->tenant_id;
        $advances = SalaryAdvance::where('tenant_id', $tenantId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
        return view('payroll.advances-admin', compact('advances'));
    }

    // -------------------------------------------------------
    // Admin: Approve / Reject advance
    // -------------------------------------------------------
    public function advanceReview(Request $request, SalaryAdvance $advance)
    {
        $tenantId = session('tenant_id') ?? Auth::user()->tenant_id;
        abort_unless($advance->tenant_id == $tenantId, 403);

        $request->validate([
            'action'      => 'required|in:approved,rejected',
            'review_note' => 'nullable|string|max:500',
        ]);

        $advance->update([
            'status'      => $request->action,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_note' => $request->review_note,
        ]);

        return redirect()->route('payroll.advances.admin')
            ->with('success', __('messages.advance_reviewed'));
    }
}
