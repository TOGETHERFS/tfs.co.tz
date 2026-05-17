<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\AccountingAuditTrail;
use App\Models\User;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->get('user_id');
        $action = $request->get('action');
        $modelType = $request->get('model_type');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = AccountingAuditTrail::with('user')
            ->orderBy('created_at', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($action) {
            $query->where('action', $action);
        }

        if ($modelType) {
            $query->where('model_type', 'like', '%' . $modelType . '%');
        }

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }

        $auditLogs = $query->paginate(50);
        $users = User::orderBy('name')->get();
        
        $actions = AccountingAuditTrail::select('action')
            ->distinct()
            ->pluck('action');

        return view('accounting.audit-trail.index', compact('auditLogs', 'users', 'actions', 'userId', 'action', 'modelType', 'startDate', 'endDate'));
    }

    public function show(AccountingAuditTrail $auditTrail)
    {
        $auditTrail->load('user');

        return view('accounting.audit-trail.show', compact('auditTrail'));
    }
}
