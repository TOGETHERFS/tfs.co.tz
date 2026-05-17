<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\LeaveBalance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveController extends Controller
{
    // -------------------------------------------------------
    // Admin: All leave requests
    // -------------------------------------------------------
    public function index(Request $request)
    {
        $tenantId = session('tenant_id') ?? Auth::user()->tenant_id;
        $status   = $request->get('status', 'all');

        $query = LeaveRequest::where('tenant_id', $tenantId)->with('user');
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        $leaves = $query->orderBy('created_at', 'desc')->get();

        return view('leave.index', compact('leaves', 'status'));
    }

    // -------------------------------------------------------
    // Staff: Apply for leave
    // -------------------------------------------------------
    public function create()
    {
        $user    = Auth::user();
        $year    = now()->year;
        $balance = LeaveBalance::firstOrCreate(
            ['tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'year' => $year],
            ['entitled_days' => 28, 'used_days' => 0]
        );
        return view('leave.create', compact('balance'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'nullable|string|max:1000',
        ]);

        $user  = Auth::user();
        $start = \Carbon\Carbon::parse($request->start_date);
        $end   = \Carbon\Carbon::parse($request->end_date);
        $days  = $start->diffInDays($end) + 1;

        $year    = $start->year;
        $balance = LeaveBalance::firstOrCreate(
            ['tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'year' => $year],
            ['entitled_days' => 28, 'used_days' => 0]
        );

        if ($days > $balance->remaining_days) {
            return back()->withErrors(['end_date' => __('messages.leave_exceeds_balance')])->withInput();
        }

        LeaveRequest::create([
            'tenant_id'  => $user->tenant_id,
            'user_id'    => $user->id,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'days'       => $days,
            'reason'     => $request->reason,
            'status'     => 'pending',
        ]);

        return redirect()->route('leave.my')
            ->with('success', __('messages.leave_applied'));
    }

    // -------------------------------------------------------
    // Staff: View own leaves
    // -------------------------------------------------------
    public function my()
    {
        $user    = Auth::user();
        $year    = now()->year;
        $leaves  = LeaveRequest::where('user_id', $user->id)
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('created_at', 'desc')
            ->get();
        $balance = LeaveBalance::firstOrCreate(
            ['tenant_id' => $user->tenant_id, 'user_id' => $user->id, 'year' => $year],
            ['entitled_days' => 28, 'used_days' => 0]
        );
        return view('leave.my', compact('leaves', 'balance', 'year'));
    }

    // -------------------------------------------------------
    // Admin: Approve / Reject leave
    // -------------------------------------------------------
    public function review(Request $request, LeaveRequest $leave)
    {
        $tenantId = session('tenant_id') ?? Auth::user()->tenant_id;
        abort_unless($leave->tenant_id == $tenantId, 403);

        $request->validate([
            'action'      => 'required|in:approved,rejected',
            'review_note' => 'nullable|string|max:500',
        ]);

        $leave->update([
            'status'      => $request->action,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_note' => $request->review_note,
        ]);

        // Update leave balance on approval
        if ($request->action === 'approved') {
            $year = $leave->start_date->year;
            $balance = LeaveBalance::firstOrCreate(
                ['tenant_id' => $leave->tenant_id, 'user_id' => $leave->user_id, 'year' => $year],
                ['entitled_days' => 28, 'used_days' => 0]
            );
            $balance->increment('used_days', $leave->days);
        }

        return redirect()->route('leave.index')
            ->with('success', __('messages.leave_reviewed'));
    }

    // -------------------------------------------------------
    // Admin: Leave balances overview
    // -------------------------------------------------------
    public function balances()
    {
        $tenantId = session('tenant_id') ?? Auth::user()->tenant_id;
        $year     = now()->year;

        $staff = User::where('tenant_id', $tenantId)->get();
        $balances = $staff->map(function ($user) use ($tenantId, $year) {
            return LeaveBalance::firstOrCreate(
                ['tenant_id' => $tenantId, 'user_id' => $user->id, 'year' => $year],
                ['entitled_days' => 28, 'used_days' => 0]
            )->load('user');
        });

        return view('leave.balances', compact('balances', 'year'));
    }
}
