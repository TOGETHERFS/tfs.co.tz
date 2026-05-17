<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\RepaymentController;
use App\Http\Controllers\Api\LoanProductController;
use App\Http\Controllers\Api\LoanApprovalController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Api\TenantBillingController;
use App\Http\Controllers\Api\WorkflowConfigController;
use App\Http\Controllers\Api\LoanWorkflowController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Routes for API; authenticated endpoints inside sanctum middleware.
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API routes for authenticated users (supporting both web session and sanctum token auth)
Route::middleware(['web', 'auth', 'resolve.tenant'])->group(function () {
    // Client API endpoints
    Route::prefix('clients')->group(function () {
        Route::get('/', [ClientController::class, 'index']);
        Route::get('/search', [ClientController::class, 'search']);
        Route::get('/stats', [ClientController::class, 'statistics']);
        Route::get('/{id}', [ClientController::class, 'show']);
        Route::post('/', [ClientController::class, 'store']);
        Route::put('/{id}', [ClientController::class, 'update']);
        Route::delete('/{id}', [ClientController::class, 'destroy']);
    });
    
    // Loan API endpoints
    Route::prefix('loans')->group(function () {
        Route::get('/', [LoanController::class, 'index']);
        Route::get('/stats', [LoanController::class, 'statistics']);
        Route::get('/{id}', [LoanController::class, 'show']);
        Route::post('/', [LoanController::class, 'store']);
        Route::put('/{id}', [LoanController::class, 'update']);
        Route::patch('/{id}/approve', [LoanController::class, 'approve']);
        Route::patch('/{id}/disburse', [LoanController::class, 'disburse']);
        // Staged approval endpoints
        Route::post('/{id}/stage/approve', [LoanApprovalController::class, 'approveStage']);
        Route::post('/{id}/stage/reject', [LoanApprovalController::class, 'rejectStage']);
        Route::get('/{id}/approvals', [LoanApprovalController::class, 'history']);
        Route::post('/{id}/sync-schedules', [LoanController::class, 'syncSchedules']);
    });

    // Loan Products API endpoints
    Route::get('/loan-products', [LoanProductController::class, 'index']);
    
    // Repayment API endpoints
    Route::prefix('repayments')->group(function () {
        Route::get('/', [RepaymentController::class, 'index']);
        Route::get('/stats', [RepaymentController::class, 'statistics']);
        Route::get('/search-loans', [RepaymentController::class, 'searchLoans']);
        Route::get('/{id}', [RepaymentController::class, 'show']);
        Route::post('/', [RepaymentController::class, 'store']);
        Route::put('/{id}', [RepaymentController::class, 'update']);
        Route::put('/schedules/{scheduleId}/correct', [RepaymentController::class, 'correctPayment']);
        Route::delete('/{id}', [RepaymentController::class, 'destroy']);
        
        // Selcom payment endpoints
        Route::post('/selcom/initiate', [RepaymentController::class, 'initiateSelcomPayment']);
        Route::get('/selcom/status/{transactionId}', [RepaymentController::class, 'checkSelcomStatus']);
        Route::post('/selcom/callback', [RepaymentController::class, 'selcomCallback']);
    });
    
    // Notifications API endpoints
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'getNotifications']);
        Route::get('/types', [NotificationController::class, 'getNotificationTypes']);
        Route::get('/types/{category}', [NotificationController::class, 'getNotificationTypesByCategory']);
        Route::get('/templates/{notificationTypeId}', [NotificationController::class, 'getNotificationTemplates']);
        Route::get('/preferences', [NotificationController::class, 'getNotificationPreferences']);
        Route::get('/stats', [NotificationController::class, 'getNotificationStats']);
        Route::post('/send', [NotificationController::class, 'sendNotification']);
        Route::post('/send-bulk', [NotificationController::class, 'sendBulkNotification']);
        Route::post('/send-admin', [NotificationController::class, 'sendAdminNotification']);
        Route::post('/test', [NotificationController::class, 'testNotification']);
        Route::put('/preferences', [NotificationController::class, 'updateNotificationPreferences']);
    });

    // Billing API endpoints
    Route::prefix('billing')->group(function () {
        Route::get('/summary', [TenantBillingController::class, 'summary'])->middleware('perm:view-billing');
        Route::get('/plans', [TenantBillingController::class, 'plans'])->middleware('perm:view-billing');
        Route::get('/subscription', [TenantBillingController::class, 'subscription'])->middleware('perm:view-billing');
        Route::post('/change-plan', [TenantBillingController::class, 'changePlan'])->middleware('perm:manage-billing');
        Route::post('/addons', [TenantBillingController::class, 'updateAddons'])->middleware('perm:manage-billing');
        Route::post('/cancel', [TenantBillingController::class, 'cancel'])->middleware('perm:manage-billing');
        Route::post('/resume', [TenantBillingController::class, 'resume'])->middleware('perm:manage-billing');
        Route::post('/initiate-payment', [TenantBillingController::class, 'initiatePayment'])->middleware('perm:manage-billing');
    });

    // Workflow Configuration API endpoints
    Route::prefix('workflows')->group(function () {
        Route::get('/', [WorkflowConfigController::class, 'index']);
        Route::get('/global', [WorkflowConfigController::class, 'getGlobal']);
        Route::get('/active', [WorkflowConfigController::class, 'getActive']);
        Route::get('/roles', [WorkflowConfigController::class, 'getAvailableRoles']);
        Route::get('/{workflowConfig}', [WorkflowConfigController::class, 'show']);
        Route::post('/', [WorkflowConfigController::class, 'store']);
        Route::put('/{workflowConfig}', [WorkflowConfigController::class, 'update']);
        Route::delete('/{workflowConfig}', [WorkflowConfigController::class, 'destroy']);
        Route::post('/toggle-custom', [WorkflowConfigController::class, 'toggleCustomWorkflow']);
        Route::post('/global', [WorkflowConfigController::class, 'upsertGlobal']); // Super admin only
    });

    // Loan Workflow API endpoints (approval/disbursement actions)
    Route::prefix('loans/{loan}/workflow')->group(function () {
        Route::post('/initialize', [LoanWorkflowController::class, 'initializeWorkflow']);
        Route::get('/status', [LoanWorkflowController::class, 'getStatus']);
        Route::post('/approve', [LoanWorkflowController::class, 'approve']);
        Route::post('/reject', [LoanWorkflowController::class, 'reject']);
        Route::post('/return', [LoanWorkflowController::class, 'returnForCorrections']);
        Route::post('/resubmit', [LoanWorkflowController::class, 'resubmit']);
        Route::post('/disburse', [LoanWorkflowController::class, 'disburse']);
        Route::get('/audit-trail', [LoanWorkflowController::class, 'getAuditTrail']);
        Route::get('/can-edit', [LoanWorkflowController::class, 'canEdit']);
    });

    // Workflow dashboard endpoints
    Route::get('/workflow/pending-approvals', [LoanWorkflowController::class, 'getPendingApprovals']);
    Route::get('/workflow/statistics', [LoanWorkflowController::class, 'getStatistics']);

    // Reports API endpoints
    Route::prefix('reports')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\ReportsController::class, 'dashboardApi']);
        Route::get('/generate', [App\Http\Controllers\ReportsController::class, 'generateApi']);
        Route::get('/export-all', [App\Http\Controllers\ReportsController::class, 'exportAllApi']);
    });
});

// Public API endpoints (no authentication required)
Route::prefix('public')->group(function () {
    Route::get('/loan-calculator', function (Request $request) {
        $request->validate([
            'principal' => 'required|numeric|min:1',
            'rate' => 'required|numeric|min:0',
            'term' => 'required|integer|min:1',
            'frequency' => 'required|in:weekly,monthly'
        ]);
        
        $principal = $request->principal;
        $monthlyRate = $request->rate / 100; // monthly rate
        $term = $request->term;
        $frequency = $request->frequency;
        
        // Calculate based on frequency with monthly semantics
        if ($frequency === 'weekly') {
            $periodicRate = pow(1 + $monthlyRate, 1/4.345) - 1; // derive effective weekly from monthly
            $totalPayments = $term;
        } else {
            $periodicRate = $monthlyRate; // monthly already
            $totalPayments = $term;
        }
        
        // Calculate payment using PMT formula
        if ($periodicRate > 0) {
            $payment = $principal * ($periodicRate * pow(1 + $periodicRate, $totalPayments)) / 
                      (pow(1 + $periodicRate, $totalPayments) - 1);
        } else {
            $payment = $principal / $totalPayments;
        }
        
        $totalAmount = $payment * $totalPayments;
        $totalInterest = $totalAmount - $principal;
        
        return response()->json([
            'payment' => round($payment, 2),
            'total_amount' => round($totalAmount, 2),
            'total_interest' => round($totalInterest, 2),
            'principal' => $principal,
            'rate' => $request->rate,
            'term' => $term,
            'frequency' => $frequency
        ]);
    });
    
    Route::get('/exchange-rates', function () {
        // Mock exchange rates - in production, fetch from external API
        return response()->json([
            'USD' => 1.0,
            'TZS' => 2500.0,
            'KES' => 150.0,
            'UGX' => 3700.0,
            'last_updated' => now()->toISOString()
        ]);
    });
});
