<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\LoanProductController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RepaymentController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoanStageController;
use App\Http\Controllers\PublicPricingController;
use App\Http\Controllers\UserBranchController;
use App\Http\Controllers\SubscribeController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LanguageController;

use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Artisan;

// TEMPORARY: Cache clearing route for production deployment
// Remove this after cache is cleared on live server
Route::get('/emergency-clear-cache-2026', function() {
    try {
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        
        return response()->json([
            'status' => 'success',
            'message' => 'All caches cleared successfully!',
            'timestamp' => now()->toDateTimeString(),
            'cleared' => [
                'config' => 'cleared',
                'cache' => 'cleared',
                'routes' => 'cleared',
                'views' => 'cleared'
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

// TEMPORARY: Session testing routes - Remove after debugging
Route::get('/test-session-set', function() {
    session(['test_key' => 'Session is working! Time: ' . now()]);
    return response()->json([
        'status' => 'success',
        'message' => 'Session value set',
        'next_step' => 'Visit /test-session-check to verify'
    ]);
});

Route::get('/test-session-check', function() {
    $value = session('test_key', 'SESSION NOT WORKING - Value not found');
    $sessionPath = config('session.files');
    $sessionDriver = config('session.driver');
    
    return response()->json([
        'session_value' => $value,
        'session_driver' => $sessionDriver,
        'session_path' => $sessionPath,
        'session_id' => session()->getId(),
        'storage_writable' => is_writable(storage_path('framework/sessions')),
        'storage_exists' => file_exists(storage_path('framework/sessions'))
    ]);
});

Route::get('/diagnose-login', function() {
    $sessionPath = storage_path('framework/sessions');
    $diagnostics = [
        'timestamp' => now()->toDateTimeString(),
        'session' => [
            'driver' => config('session.driver'),
            'lifetime' => config('session.lifetime'),
            'encrypt' => config('session.encrypt'),
            'path' => config('session.path'),
            'domain' => config('session.domain'),
            'secure' => config('session.secure'),
            'same_site' => config('session.same_site'),
            'http_only' => config('session.http_only'),
            'cookie_name' => config('session.cookie'),
        ],
        'storage' => [
            'path' => $sessionPath,
            'exists' => file_exists($sessionPath),
            'writable' => is_writable($sessionPath),
            'permissions' => file_exists($sessionPath) ? substr(sprintf('%o', fileperms($sessionPath)), -4) : 'N/A',
            'files_count' => file_exists($sessionPath) ? count(glob($sessionPath . '/*')) : 0,
        ],
        'environment' => [
            'app_key_set' => !empty(config('app.key')),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'app_url' => config('app.url'),
        ],
        'php' => [
            'version' => PHP_VERSION,
            'session_save_path' => session_save_path(),
            'session_name' => session_name(),
        ],
        'recommendations' => []
    ];

    if (!$diagnostics['storage']['exists']) {
        $diagnostics['recommendations'][] = 'CRITICAL: Session storage directory does not exist. Run: mkdir -p storage/framework/sessions';
    }
    if (!$diagnostics['storage']['writable']) {
        $diagnostics['recommendations'][] = 'CRITICAL: Session storage directory is not writable. Run: chmod -R 775 storage';
    }
    if (!$diagnostics['environment']['app_key_set']) {
        $diagnostics['recommendations'][] = 'CRITICAL: APP_KEY is not set. Run: php artisan key:generate';
    }
    if (config('session.secure') === true && strpos(config('app.url'), 'https://') !== 0) {
        $diagnostics['recommendations'][] = 'WARNING: SESSION_SECURE_COOKIE is true but APP_URL is not HTTPS. Set SESSION_SECURE_COOKIE=false in .env';
    }
    if (config('session.encrypt') === true) {
        $diagnostics['recommendations'][] = 'INFO: Session encryption is enabled. If having issues, try SESSION_ENCRYPT=false';
    }
    if (!empty(config('session.domain'))) {
        $diagnostics['recommendations'][] = 'INFO: SESSION_DOMAIN is set. For most cases, leave it empty in .env';
    }

    if (empty($diagnostics['recommendations'])) {
        $diagnostics['recommendations'][] = 'All checks passed! If login still fails, clear cache: php artisan config:clear';
    }

    return response()->json($diagnostics, 200, [], JSON_PRETTY_PRINT);
});

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Public routes (accessible to everyone)
Route::get('/', [HomeController::class, 'index'])->name('home');

// Language switch route
Route::get('/language/{locale}', [LanguageController::class, 'switch'])->name('language.switch');

// Guest routes (authentication)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])
        ->name('login');
    
    Route::post('/login', [LoginController::class, 'login']);
    
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])
        ->name('register');
    
    Route::post('/register', [RegisterController::class, 'register']);

    // Password Reset Routes
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])
        ->name('password.request');
    
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->name('password.email');
    
    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])
        ->name('password.reset');
    
    Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])
        ->name('password.update');
});

// Email Verification Routes
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('user.dashboard')->with('success', 'Email verified successfully!');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }
        $user->sendEmailVerificationNotification();
        return back()->with('status', 'Verification link sent!');
    })->middleware('throttle:6,1')->name('verification.send');
});

// Authenticated routes with tenant resolution
Route::middleware(['auth', 'resolve.tenant', 'tenant.access'])->group(function () {
    
    // Dashboard - Role-based routing
    Route::get('/dashboard', function () {
        $user = auth()->user();

        \Log::info('Dashboard route accessed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'position' => $user->position,
            'tenant_id' => $user->tenant_id,
            'admin_role_id' => $user->admin_role_id,
        ]);

        // Route admin staff to admin dashboard, everyone else to user dashboard
        if ($user->admin_role_id) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('user.dashboard');
    })->name('dashboard');
    
    // Admin root route - redirect to dashboard
    Route::get('/admin', function () {
        return redirect()->route('admin.dashboard');
    })->middleware('role:super_admin');
    
    // Admin Dashboard
    Route::get('/admin/dashboard', [App\Http\Controllers\AdminDashboardController::class, 'index'])
        ->name('admin.dashboard');
    
    // User Dashboard - accessible to all tenant staff roles
    Route::get('/user/dashboard', [App\Http\Controllers\UserDashboardController::class, 'index'])
        ->middleware(['tenant.access'])
        ->name('user.dashboard');
    
    // Staff Management (accessible to admin, managers and officers)
    Route::prefix('user/staff')->name('user.staff')->middleware(['tenant.access'])->group(function () {
        Route::get('/', [App\Http\Controllers\UserStaffController::class, 'index'])->name('');
        Route::get('/create', [App\Http\Controllers\UserStaffController::class, 'create'])->name('.create');
        Route::post('/', [App\Http\Controllers\UserStaffController::class, 'store'])->name('.store');
        Route::delete('/{user}', [App\Http\Controllers\UserStaffController::class, 'destroy'])->name('.destroy');
        Route::get('/{user}/edit', [App\Http\Controllers\UserStaffController::class, 'edit'])->name('.edit');
        Route::put('/{user}', [App\Http\Controllers\UserStaffController::class, 'update'])->name('.update');
    });
    
    // Branch Management (accessible to admin, managers and officers)
    Route::get('/user/branches', [UserBranchController::class, 'index'])
        ->middleware(['role:admin,manager,officer', 'tenant.access'])
        ->name('user.branches.index');
    Route::get('/user/branches/create', [UserBranchController::class, 'create'])
        ->middleware(['role:admin,manager,officer', 'tenant.access'])
        ->name('user.branches.create');
    Route::post('/user/branches', [UserBranchController::class, 'store'])
        ->middleware(['role:admin,manager,officer', 'enforce.branch.limit', 'tenant.access'])
        ->name('user.branches.store');
    Route::put('/user/branches/{branch}', [UserBranchController::class, 'update'])
        ->middleware(['role:admin,manager,officer', 'tenant.access'])
        ->name('user.branches.update');
    Route::delete('/user/branches/{branch}', [UserBranchController::class, 'destroy'])
        ->middleware(['role:admin,manager,officer', 'tenant.access'])
        ->name('user.branches.destroy');
    
    // Role Management (accessible to admin, managers)
    Route::get('/user/roles/manage', [App\Http\Controllers\UserRoleManageController::class, 'index'])
        ->middleware(['role:admin,manager', 'tenant.access'])
        ->name('user.roles.manage');
    Route::get('/user/roles/create', [App\Http\Controllers\UserRoleManageController::class, 'create'])
        ->middleware(['role:admin,manager', 'tenant.access'])
        ->name('user.roles.create');
    Route::post('/user/roles', [App\Http\Controllers\UserRoleManageController::class, 'store'])
        ->middleware(['role:admin,manager', 'tenant.access'])
        ->name('user.roles.store');
    Route::delete('/user/roles/{role}', [App\Http\Controllers\UserRoleManageController::class, 'destroy'])
        ->middleware(['role:admin,manager', 'tenant.access'])
        ->name('user.roles.destroy');
    
    // Admin-specific routes
    Route::prefix('admin')->name('admin.')->group(function () {
        // Staff & Roles Management (accessible to all admins)
        Route::prefix('staff')->name('staff.')->middleware(['role:admin,super_admin', 'tenant.access'])->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\AdminStaffController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\AdminStaffController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\AdminStaffController::class, 'store'])->name('store');
            Route::get('/{staff}/edit', [App\Http\Controllers\Admin\AdminStaffController::class, 'edit'])->name('edit');
            Route::put('/{staff}', [App\Http\Controllers\Admin\AdminStaffController::class, 'update'])->name('update');
            Route::delete('/{staff}', [App\Http\Controllers\Admin\AdminStaffController::class, 'destroy'])->name('destroy');
            Route::get('/roles', [App\Http\Controllers\Admin\AdminStaffController::class, 'roles'])->name('roles');
        });
    });

    // Super Admin only routes
    Route::prefix('admin')->name('admin.')->middleware('role:super_admin')->group(function () {
        // System health
        Route::get('/system-health', [App\Http\Controllers\AdminDashboardController::class, 'systemHealth'])
            ->name('system.health');
        
        // User management (admin can manage all users across tenants)
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\UserController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\UserController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\UserController::class, 'store'])->name('store');
            Route::get('/{user}', [App\Http\Controllers\Admin\UserController::class, 'show'])->name('show');
            Route::get('/{user}/edit', [App\Http\Controllers\Admin\UserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [App\Http\Controllers\Admin\UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('destroy');
            Route::post('/{user}/ban', [App\Http\Controllers\Admin\UserController::class, 'ban'])->name('ban');
            Route::post('/{user}/unban', [App\Http\Controllers\Admin\UserController::class, 'unban'])->name('unban');
        });
        
        // Tenant management
        Route::prefix('tenants')->name('tenants.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\TenantController::class, 'index'])->name('index');
            Route::get('/{tenant}', [App\Http\Controllers\Admin\TenantController::class, 'show'])->name('show');
            Route::get('/{tenant}/edit', [App\Http\Controllers\Admin\TenantController::class, 'edit'])->name('edit');
            Route::put('/{tenant}', [App\Http\Controllers\Admin\TenantController::class, 'update'])->name('update');
            Route::patch('/{tenant}/suspend', [App\Http\Controllers\Admin\TenantController::class, 'suspend'])->name('suspend');
            Route::patch('/{tenant}/activate', [App\Http\Controllers\Admin\TenantController::class, 'activate'])->name('activate');
            Route::delete('/{tenant}', [App\Http\Controllers\Admin\TenantController::class, 'destroy'])->name('destroy');
        });

        // Subscription Management (Super Admin)
        Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'index'])->name('index');
            Route::get('/{tenant}', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'show'])->name('show');
            Route::post('/{tenant}/record-payment', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'recordPayment'])->name('record-payment');
            Route::post('/{tenant}/extend-trial', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'extendTrial'])->name('extend-trial');
            Route::post('/{tenant}/reduce-plan', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'reducePlan'])->name('reduce-plan');
            Route::post('/{tenant}/update-plan', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'updatePlan'])->name('update-plan');
            Route::post('/{tenant}/edit-revenue', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'editRevenue'])->name('edit-revenue');
            Route::post('/{tenant}/suspend', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'suspend'])->name('suspend');
            Route::post('/{tenant}/reactivate', [App\Http\Controllers\Admin\SubscriptionManagementController::class, 'reactivate'])->name('reactivate');
        });
        
        // System settings
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\SettingsController::class, 'index'])->name('index');
            Route::put('/', [App\Http\Controllers\Admin\SettingsController::class, 'update'])->name('update');
        });

        // Superadmin Accounts (Revenue, Expenses, P&L)
        Route::prefix('accounts')->name('accounts.')->group(function () {
            Route::get('/revenue',  [App\Http\Controllers\Admin\SuperadminAccountsController::class, 'revenue'])->name('revenue');
            Route::get('/expenses', [App\Http\Controllers\Admin\SuperadminAccountsController::class, 'expenses'])->name('expenses');
            Route::post('/expenses', [App\Http\Controllers\Admin\SuperadminAccountsController::class, 'storeExpense'])->name('expenses.store');
            Route::delete('/expenses/{expense}', [App\Http\Controllers\Admin\SuperadminAccountsController::class, 'destroyExpense'])->name('expenses.destroy');
            Route::get('/profit-loss', [App\Http\Controllers\Admin\SuperadminAccountsController::class, 'profitLoss'])->name('profit-loss');
        });

        // Plans management (admin)
        Route::prefix('plans')->name('plans.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\PlanController::class, 'index'])->name('index');
            Route::get('/{plan}/edit', [App\Http\Controllers\Admin\PlanController::class, 'edit'])->name('edit');
            Route::put('/{plan}', [App\Http\Controllers\Admin\PlanController::class, 'update'])->name('update');
            Route::post('/update-subscription', [App\Http\Controllers\Admin\PlanController::class, 'updateTenantSubscription'])->name('update-subscription');
            Route::delete('/subscription/{subscription}', [App\Http\Controllers\Admin\PlanController::class, 'deleteSubscription'])->name('delete-subscription');
        });

        // Messages control (admin)
        Route::prefix('messages')->name('messages.')->group(function () {
            Route::get('/control', [App\Http\Controllers\Admin\MessageController::class, 'control'])->name('control');
            Route::patch('/{tenant}/toggle', [App\Http\Controllers\Admin\MessageController::class, 'toggle'])->name('toggle');
        });

        // Send SMS (admin)
        Route::prefix('sms')->name('sms.')->group(function () {
            Route::get('/send', [App\Http\Controllers\Admin\SendSmsController::class, 'index'])->name('send');
            Route::post('/send', [App\Http\Controllers\Admin\SendSmsController::class, 'send'])->name('send.post');
        });

        // SMS Provider Management
        Route::prefix('sms-providers')->name('sms-providers.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\SmsProviderController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\SmsProviderController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\SmsProviderController::class, 'store'])->name('store');
            Route::get('/{smsProvider}', [App\Http\Controllers\Admin\SmsProviderController::class, 'show'])->name('show');
            Route::get('/{smsProvider}/edit', [App\Http\Controllers\Admin\SmsProviderController::class, 'edit'])->name('edit');
            Route::put('/{smsProvider}', [App\Http\Controllers\Admin\SmsProviderController::class, 'update'])->name('update');
            Route::delete('/{smsProvider}', [App\Http\Controllers\Admin\SmsProviderController::class, 'destroy'])->name('destroy');
            Route::post('/{smsProvider}/test', [App\Http\Controllers\Admin\SmsProviderController::class, 'testConnection'])->name('test');
            Route::get('/{smsProvider}/balance', [App\Http\Controllers\Admin\SmsProviderController::class, 'getBalance'])->name('balance');
            Route::patch('/{smsProvider}/primary', [App\Http\Controllers\Admin\SmsProviderController::class, 'setPrimary'])->name('primary');
            Route::patch('/{smsProvider}/toggle', [App\Http\Controllers\Admin\SmsProviderController::class, 'toggleStatus'])->name('toggle');
            Route::post('/sync-sender-ids', [App\Http\Controllers\Admin\SmsProviderController::class, 'syncSenderIds'])->name('sync-sender-ids');
        });

        // SMS Packages Management
        Route::prefix('sms/packages')->name('sms.packages.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\SmsPackageController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\SmsPackageController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\SmsPackageController::class, 'store'])->name('store');
            Route::get('/{package}/edit', [App\Http\Controllers\Admin\SmsPackageController::class, 'edit'])->name('edit');
            Route::put('/{package}', [App\Http\Controllers\Admin\SmsPackageController::class, 'update'])->name('update');
            Route::delete('/{package}', [App\Http\Controllers\Admin\SmsPackageController::class, 'destroy'])->name('destroy');
        });

        // SMS Diagnostics
        Route::get('/sms-diagnostics', function() {
            return view('admin.sms-diagnostics');
        })->name('sms-diagnostics');
        
        Route::post('/sms-diagnostics/test', function(\Illuminate\Http\Request $request) {
            $phone = $request->input('phone');
            $smsService = app(\App\Services\Sms\SmsService::class);
            $smsService->setContext(1, auth()->id()); // Use tenant 1 for test
            
            $result = $smsService->sendSingle(
                $phone,
                'Test SMS from PHIDLMS. If you receive this, SMS is working correctly.',
                null,
                ['type' => \App\Models\Sms\SmsMessage::TYPE_SINGLE]
            );
            
            return back()->with('test_result', [
                'success' => $result['success'],
                'message' => $result['success'] ? 'Test SMS sent successfully!' : ($result['error'] ?? 'Failed to send test SMS')
            ]);
        })->name('sms-diagnostics.test');

        // SMS Wallet Management
        Route::prefix('sms-wallets')->name('sms-wallets.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\SmsWalletController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\SmsWalletController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\SmsWalletController::class, 'store'])->name('store');
            Route::get('/{smsWallet}', [App\Http\Controllers\Admin\SmsWalletController::class, 'show'])->name('show');
            Route::get('/{smsWallet}/edit', [App\Http\Controllers\Admin\SmsWalletController::class, 'edit'])->name('edit');
            Route::put('/{smsWallet}', [App\Http\Controllers\Admin\SmsWalletController::class, 'update'])->name('update');
            Route::post('/{smsWallet}/add-credits', [App\Http\Controllers\Admin\SmsWalletController::class, 'addCredits'])->name('add-credits');
            Route::post('/{smsWallet}/deduct-credits', [App\Http\Controllers\Admin\SmsWalletController::class, 'deductCredits'])->name('deduct-credits');
            Route::get('/stats', [App\Http\Controllers\Admin\SmsWalletController::class, 'getStats'])->name('stats');
            Route::post('/bulk-operation', [App\Http\Controllers\Admin\SmsWalletController::class, 'bulkOperation'])->name('bulk-operation');
        });

        // SMS Credits Management
        Route::prefix('sms-credits')->name('sms-credits.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\SmsCreditsController::class, 'index'])->name('index');
            Route::post('/add', [App\Http\Controllers\Admin\SmsCreditsController::class, 'addCredits'])->name('add');
        });

        // Sender ID Management (SmsSenderIdRequest)
        Route::prefix('sender-ids')->name('sender-ids.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\SenderIdController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\Admin\SenderIdController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\Admin\SenderIdController::class, 'store'])->name('store');
            Route::get('/{id}', [App\Http\Controllers\Admin\SenderIdController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [App\Http\Controllers\Admin\SenderIdController::class, 'edit'])->name('edit');
            Route::put('/{id}', [App\Http\Controllers\Admin\SenderIdController::class, 'update'])->name('update');
            Route::post('/{id}/approve', [App\Http\Controllers\Admin\SenderIdController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [App\Http\Controllers\Admin\SenderIdController::class, 'reject'])->name('reject');
            Route::patch('/{id}/suspend', [App\Http\Controllers\Admin\SenderIdController::class, 'suspend'])->name('suspend');
            Route::patch('/{id}/activate', [App\Http\Controllers\Admin\SenderIdController::class, 'activate'])->name('activate');
            Route::delete('/{id}/document', [App\Http\Controllers\Admin\SenderIdController::class, 'deleteDocument'])->name('delete-document');
            Route::post('/bulk-operation', [App\Http\Controllers\Admin\SenderIdController::class, 'bulkOperation'])->name('bulk-operation');
        });

        // SMS Campaign Management
        Route::prefix('sms-campaigns')->name('sms-campaigns.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\SmsCampaignController::class, 'index'])->name('index');
            Route::get('/{smsCampaign}', [App\Http\Controllers\Admin\SmsCampaignController::class, 'show'])->name('show');
            Route::patch('/{smsCampaign}/pause', [App\Http\Controllers\Admin\SmsCampaignController::class, 'pause'])->name('pause');
            Route::patch('/{smsCampaign}/resume', [App\Http\Controllers\Admin\SmsCampaignController::class, 'resume'])->name('resume');
            Route::patch('/{smsCampaign}/cancel', [App\Http\Controllers\Admin\SmsCampaignController::class, 'cancel'])->name('cancel');
            Route::post('/{smsCampaign}/retry-failed', [App\Http\Controllers\Admin\SmsCampaignController::class, 'retryFailed'])->name('retry-failed');
            Route::get('/analytics', [App\Http\Controllers\Admin\SmsCampaignController::class, 'analytics'])->name('analytics');
            Route::post('/export', [App\Http\Controllers\Admin\SmsCampaignController::class, 'export'])->name('export');
        });

        // Reports & Analytics (admin)
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [App\Http\Controllers\Admin\ReportsController::class, 'index'])->name('index');
            Route::get('/profit-loss', [App\Http\Controllers\Admin\ReportsController::class, 'profitLoss'])->name('profit-loss');
            Route::get('/loan-portfolio', [App\Http\Controllers\Admin\ReportsController::class, 'loanPortfolio'])->name('loan-portfolio');
            Route::get('/arrears-aging', [App\Http\Controllers\Admin\ReportsController::class, 'arrearsAging'])->name('arrears-aging');
            Route::get('/collections', [App\Http\Controllers\Admin\ReportsController::class, 'collections'])->name('collections');
            Route::get('/balance-sheet', [App\Http\Controllers\Admin\ReportsController::class, 'balanceSheet'])->name('balance-sheet');
            Route::get('/client-analysis', [App\Http\Controllers\Admin\ReportsController::class, 'clientAnalysis'])->name('client-analysis');
            Route::get('/export/{type}', [App\Http\Controllers\Admin\ReportsController::class, 'exportPdf'])->name('export');

            // Accounts section
            Route::prefix('accounts')->name('accounts.')->group(function () {
                Route::get('/', [App\Http\Controllers\Admin\ReportsController::class, 'accountsIndex'])->name('index');
                Route::get('/general-ledger', [App\Http\Controllers\Admin\ReportsController::class, 'generalLedger'])->name('general-ledger');
                Route::get('/trial-balance', [App\Http\Controllers\Admin\ReportsController::class, 'trialBalance'])->name('trial-balance');
                Route::get('/cashbook', [App\Http\Controllers\Admin\ReportsController::class, 'cashBook'])->name('cashbook');
                Route::get('/bankbook', [App\Http\Controllers\Admin\ReportsController::class, 'bankBook'])->name('bankbook');
                Route::get('/client-ledger', [App\Http\Controllers\Admin\ReportsController::class, 'clientLedger'])->name('client-ledger');
                Route::get('/income-categories', [App\Http\Controllers\Admin\ReportsController::class, 'incomeCategories'])->name('income-categories');
                Route::get('/expenditure-categories', [App\Http\Controllers\Admin\ReportsController::class, 'expenditureCategories'])->name('expenditure-categories');
                Route::get('/assets', [App\Http\Controllers\Admin\ReportsController::class, 'assetsIndex'])->name('assets');
                Route::post('/assets', [App\Http\Controllers\Admin\ReportsController::class, 'storeAsset'])->name('assets.store');
                Route::get('/chart-of-accounts', [App\Http\Controllers\Admin\ReportsController::class, 'chartOfAccounts'])->name('chart-of-accounts');
                Route::get('/journal-entries', [App\Http\Controllers\Admin\ReportsController::class, 'journalEntries'])->name('journal-entries');
            });
        });
    });
    
    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])
        ->name('logout');
    
    // Client Management
    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('/', [ClientController::class, 'index'])->name('index');
        Route::get('/create', [ClientController::class, 'create'])->name('create');
        Route::post('/', [ClientController::class, 'store'])->name('store');
        // Import Clients (Excel/CSV) — must be BEFORE parameterized routes
        Route::get('/import', [ClientController::class, 'importForm'])->name('import');
        Route::post('/import', [ClientController::class, 'importProcess'])->name('import.process');
        Route::get('/import/template', [ClientController::class, 'downloadTemplate'])->name('import.template');
        // Purge all borrowers — destructive
        Route::delete('/purge', [ClientController::class, 'purge'])->name('purge');
        // New: JSON data endpoint for borrowers list
        Route::get('/data', [ClientController::class, 'data'])->name('data');
        // Search and JSON — must be BEFORE {client} wildcard
        Route::get('/search', [ClientController::class, 'search'])->name('search');
        Route::get('/json/{client}', [ClientController::class, 'json'])->name('json');
        Route::get('/{client}', [ClientController::class, 'show'])->name('show');
        Route::get('/{client}/edit', [ClientController::class, 'edit'])->name('edit');
        Route::put('/{client}', [ClientController::class, 'update'])->name('update');
        Route::delete('/{client}', [ClientController::class, 'destroy'])->name('destroy');
        Route::patch('/{client}/toggle-status', [ClientController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/{client}/loans', [ClientController::class, 'loans'])->name('loans');
        Route::get('/{client}/repayments', [ClientController::class, 'repayments'])->name('repayments');
    });

    // Group Management
    Route::prefix('groups')->name('groups.')->group(function () {
        Route::get('/', [GroupController::class, 'index'])->name('index');
        Route::get('/create', [GroupController::class, 'create'])->name('create');
        Route::post('/', [GroupController::class, 'store'])->name('store');
        Route::get('/{group}', [GroupController::class, 'show'])->name('show');
        Route::post('/{group}/clients', [GroupController::class, 'attachClients'])->name('attachClients');
    });
    
    // Loan Management
    Route::prefix('loans')->name('loans.')->group(function () {
        Route::get('/', [LoanController::class, 'index'])->name('index');
        Route::get('/create', [LoanController::class, 'create'])->name('create');
        Route::post('/', [LoanController::class, 'store'])->name('store');
        Route::get('/import', [LoanController::class, 'importForm'])->name('import');
        Route::post('/import', [LoanController::class, 'importProcess'])->name('import.process');
        Route::get('/import/template', [LoanController::class, 'downloadTemplate'])->name('import.template');
        Route::get('/{loan}', [LoanController::class, 'show'])->name('show');
        Route::get('/{loan}/edit', [LoanController::class, 'edit'])->name('edit');
        Route::put('/{loan}', [LoanController::class, 'update'])->name('update');
        Route::delete('/{loan}', [LoanController::class, 'destroy'])->name('destroy');
        Route::patch('/{loan}/approve', [LoanController::class, 'approve'])->name('approve');
        Route::patch('/{loan}/reject', [LoanController::class, 'reject'])->name('reject');
        Route::patch('/{loan}/disburse', [LoanController::class, 'disburse'])->name('disburse');
        Route::get('/{loan}/schedule', [LoanController::class, 'schedule'])->name('schedule');
        Route::get('/{loan}/repayments', [\App\Http\Controllers\RepaymentController::class, 'loanRepayments'])->name('repayments');
        Route::get('/{loan}/documents', [LoanController::class, 'documents'])->name('documents');
        Route::post('/{loan}/documents', [LoanController::class, 'uploadDocument'])->name('documents.upload');
        Route::get('/{loan}/documents/{document}/download', [LoanController::class, 'downloadDocument'])->name('documents.download');
        Route::get('/{loan}/documents/{document}/view', [LoanController::class, 'viewDocument'])->name('documents.view');
        // Stage decision endpoints (session-auth)
        Route::post('/{loan}/stage/approve', [LoanStageController::class, 'approve'])->name('stage.approve');
        Route::post('/{loan}/stage/reject', [LoanStageController::class, 'reject'])->name('stage.reject');
        Route::get('/{loan}/approvals', [LoanStageController::class, 'history'])->name('approvals');
    });
    
    // Repayment Management
    Route::prefix('repayments')->name('repayments.')->group(function () {
        Route::get('/', [RepaymentController::class, 'index'])->name('index');
        Route::get('/create', [RepaymentController::class, 'create'])->name('create');
        Route::get('/history', [RepaymentController::class, 'history'])->name('history');
        Route::post('/', [RepaymentController::class, 'store'])->name('store');
        Route::get('/{repayment}', [RepaymentController::class, 'show'])->name('show');
        Route::get('/{repayment}/edit', [RepaymentController::class, 'edit'])->name('edit');
        Route::put('/{repayment}', [RepaymentController::class, 'update'])->name('update');
        Route::delete('/{repayment}', [RepaymentController::class, 'destroy'])->name('destroy');
        Route::get('/loan/{loan}', [RepaymentController::class, 'loanRepayments'])->name('loan');
        Route::post('/record-payment', [RepaymentController::class, 'recordPayment'])->name('record-payment');
    });
    
    // Loan Products Management
    Route::prefix('loan-products')->name('loan-products.')->group(function () {
        Route::get('/', [LoanProductController::class, 'index'])->name('index');
        Route::get('/create', [LoanProductController::class, 'create'])->name('create');
        Route::post('/', [LoanProductController::class, 'store'])->name('store');
        Route::get('/{product}', [LoanProductController::class, 'show'])->name('show');
        Route::get('/{product}/edit', [LoanProductController::class, 'edit'])->name('edit');
        Route::put('/{product}', [LoanProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [LoanProductController::class, 'destroy'])->name('destroy');
    });
    
    // Notifications Management
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/manage', function () {
            return view('notifications.manage');
        })->name('manage')->middleware('perm:manage-notifications');
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('mark-as-read');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
    });

    // Messages (Beem Africa SMS)
    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [App\Http\Controllers\MessageController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\MessageController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\MessageController::class, 'store'])->name('store');
        // Users: Buy SMS from admin (phidtech)
        Route::get('/buy', [App\Http\Controllers\MessageController::class, 'buy'])->name('buy');
        Route::post('/buy', [App\Http\Controllers\MessageController::class, 'buyStore'])->name('buy.store');
        Route::post('/buy/checkout', [App\Http\Controllers\MessageController::class, 'buyCheckout'])->name('buy.checkout');
        Route::get('/buy/success', [App\Http\Controllers\MessageController::class, 'buySuccess'])->name('buy.success');
        // Superadmin-only: Beem Africa account and purchase management
        Route::get('/balance', [App\Http\Controllers\MessageController::class, 'balance'])
            ->middleware('role:superadmin')
            ->name('balance');
        Route::get('/purchase', [App\Http\Controllers\MessageController::class, 'purchase'])
            ->middleware('role:superadmin')
            ->name('purchase');
        Route::get('/sender-id/request', [App\Http\Controllers\MessageController::class, 'senderIdRequest'])
            ->middleware('role:superadmin')
            ->name('sender-id.request');
        // Superadmin: Approve/reject user purchase requests
        Route::middleware('role:superadmin')->group(function () {
            Route::get('/purchases', [App\Http\Controllers\MessageController::class, 'purchasesIndex'])->name('purchases.index');
            Route::post('/purchases/{id}/approve', [App\Http\Controllers\MessageController::class, 'purchasesApprove'])->name('purchases.approve');
            Route::post('/purchases/{id}/reject', [App\Http\Controllers\MessageController::class, 'purchasesReject'])->name('purchases.reject');
        });
    });

    // Sender ID Applications (Tenant-facing)
    Route::prefix('sender-ids')->name('sender-ids.')->group(function () {
        Route::get('/', [App\Http\Controllers\Tenant\SenderIdApplicationController::class, 'index'])->name('index');
        Route::get('/guidelines', [App\Http\Controllers\Tenant\SenderIdApplicationController::class, 'guidelines'])->name('guidelines');
        Route::get('/create', [App\Http\Controllers\Tenant\SenderIdApplicationController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Tenant\SenderIdApplicationController::class, 'store'])->name('store');
        Route::get('/{senderId}', [App\Http\Controllers\Tenant\SenderIdApplicationController::class, 'show'])->name('show');
        Route::get('/{senderId}/edit', [App\Http\Controllers\Tenant\SenderIdApplicationController::class, 'edit'])->name('edit');
        Route::put('/{senderId}', [App\Http\Controllers\Tenant\SenderIdApplicationController::class, 'update'])->name('update');
        Route::delete('/{senderId}', [App\Http\Controllers\Tenant\SenderIdApplicationController::class, 'destroy'])->name('destroy');
        Route::get('/{senderId}/document/{documentIndex}', [App\Http\Controllers\Tenant\SenderIdApplicationController::class, 'downloadDocument'])->name('download-document');
    });

    // SMS Notifications (Tenant-facing)
    Route::prefix('sms-notifications')->name('sms-notifications.')->group(function () {
        Route::get('/', [App\Http\Controllers\Tenant\SmsNotificationController::class, 'index'])->name('index');
        Route::put('/preferences', [App\Http\Controllers\Tenant\SmsNotificationController::class, 'update'])->name('update');
        Route::post('/test', [App\Http\Controllers\Tenant\SmsNotificationController::class, 'test'])->name('test');
        Route::get('/history', [App\Http\Controllers\Tenant\SmsNotificationController::class, 'history'])->name('history');
        Route::post('/{notification}/read', [App\Http\Controllers\Tenant\SmsNotificationController::class, 'markAsRead'])->name('mark-as-read');
        Route::post('/read-all', [App\Http\Controllers\Tenant\SmsNotificationController::class, 'markAllAsRead'])->name('mark-all-as-read');
        Route::delete('/{notification}', [App\Http\Controllers\Tenant\SmsNotificationController::class, 'delete'])->name('delete');
        
        // AJAX routes for notifications
        Route::get('/ajax/unread-count', [App\Http\Controllers\Tenant\SmsNotificationController::class, 'unreadCount'])->name('ajax.unread-count');
        Route::get('/ajax/recent', [App\Http\Controllers\Tenant\SmsNotificationController::class, 'recent'])->name('ajax.recent');
    });
    
    // -------------------------------------------------------
    // Payroll Module
    // -------------------------------------------------------
    Route::prefix('payroll')->name('payroll.')->middleware(['role:admin,manager,officer', 'tenant.access'])->group(function () {
        // Admin routes
        Route::get('/', [App\Http\Controllers\PayrollController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\PayrollController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\PayrollController::class, 'store'])->name('store');
        Route::get('/{salary}/edit', [App\Http\Controllers\PayrollController::class, 'edit'])->name('edit');
        Route::put('/{salary}', [App\Http\Controllers\PayrollController::class, 'update'])->name('update');
        Route::get('/{salary}/slip/download', [App\Http\Controllers\PayrollController::class, 'downloadSlip'])->name('slip.download');
        // Admin: advance management
        Route::get('/advances/admin', [App\Http\Controllers\PayrollController::class, 'advancesAdmin'])->name('advances.admin');
        Route::patch('/advances/{advance}/review', [App\Http\Controllers\PayrollController::class, 'advanceReview'])->name('advance.review');
        // Staff routes
        Route::get('/my-slips', [App\Http\Controllers\PayrollController::class, 'mySlips'])->name('my-slips');
        Route::get('/my-advances', [App\Http\Controllers\PayrollController::class, 'advances'])->name('advances');
        Route::get('/advance/apply', [App\Http\Controllers\PayrollController::class, 'advanceCreate'])->name('advance.create');
        Route::post('/advance/apply', [App\Http\Controllers\PayrollController::class, 'advanceStore'])->name('advance.store');
    });

    // -------------------------------------------------------
    // Leave Module
    // -------------------------------------------------------
    Route::prefix('leave')->name('leave.')->middleware(['role:admin,manager,officer', 'tenant.access'])->group(function () {
        // Admin routes
        Route::get('/', [App\Http\Controllers\LeaveController::class, 'index'])->name('index');
        Route::patch('/{leave}/review', [App\Http\Controllers\LeaveController::class, 'review'])->name('review');
        Route::get('/balances', [App\Http\Controllers\LeaveController::class, 'balances'])->name('balances');
        // Staff routes
        Route::get('/my', [App\Http\Controllers\LeaveController::class, 'my'])->name('my');
        Route::get('/apply', [App\Http\Controllers\LeaveController::class, 'create'])->name('create');
        Route::post('/apply', [App\Http\Controllers\LeaveController::class, 'store'])->name('store');
    });

    // -------------------------------------------------------
    // Documents Module
    // -------------------------------------------------------
    Route::prefix('documents')->name('documents.')->middleware(['role:admin,manager,officer', 'tenant.access'])->group(function () {
        Route::get('/', [App\Http\Controllers\DocumentController::class, 'index'])->name('index');
        Route::get('/upload', [App\Http\Controllers\DocumentController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\DocumentController::class, 'store'])->name('store');
        Route::get('/{document}', [App\Http\Controllers\DocumentController::class, 'show'])->name('show');
        Route::get('/{document}/download', [App\Http\Controllers\DocumentController::class, 'download'])->name('download');
        Route::delete('/{document}', [App\Http\Controllers\DocumentController::class, 'destroy'])->name('destroy');
    });

    // Reports
    Route::prefix('reports')->name('reports.')->middleware('perm:reports.view')->group(function () {
        Route::get('/', [ReportsController::class, 'index'])->name('index');
        Route::get('/profit-loss', [ReportsController::class, 'profitLoss'])->name('profit-loss');
        Route::get('/loan-portfolio', [ReportsController::class, 'loanPortfolio'])->name('loan-portfolio');
        Route::get('/arrears-aging', [ReportsController::class, 'arrearsAging'])->name('arrears-aging');
        Route::get('/collections', [ReportsController::class, 'collections'])->name('collections');
        Route::get('/balance-sheet', [ReportsController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('/client-analysis', [ReportsController::class, 'clientAnalysis'])->name('client-analysis');
        Route::get('/export/{type}', [ReportsController::class, 'exportPdf'])->middleware('perm:reports.export')->name('export');

        // New microfinance reports
        Route::get('/daily-portfolio', [ReportsController::class, 'dailyPortfolio'])->name('daily-portfolio');
        Route::get('/par-report', [ReportsController::class, 'parReport'])->name('par-report');
        Route::get('/branch-performance', [ReportsController::class, 'branchPerformance'])->name('branch-performance');
        Route::get('/cash-position', [ReportsController::class, 'cashPosition'])->name('cash-position');
        Route::get('/staff-activity', [ReportsController::class, 'staffActivity'])->name('staff-activity');

        // Accounts section
        Route::prefix('accounts')->name('accounts.')->middleware('perm:accounts.view')->group(function () {
            Route::get('/', [ReportsController::class, 'accountsIndex'])->name('index');
            Route::get('/general-ledger', [ReportsController::class, 'generalLedger'])->name('general-ledger');
            Route::get('/trial-balance', [ReportsController::class, 'trialBalance'])->name('trial-balance');
            Route::get('/cashbook', [ReportsController::class, 'cashBook'])->name('cashbook');
            Route::get('/bankbook', [ReportsController::class, 'bankBook'])->name('bankbook');
            Route::get('/client-ledger', [ReportsController::class, 'clientLedger'])->name('client-ledger');
            // Categories & Assets
            Route::get('/income-categories', [ReportsController::class, 'incomeCategories'])->name('income-categories');
            Route::get('/expenditure-categories', [ReportsController::class, 'expenditureCategories'])->name('expenditure-categories');
            Route::get('/assets', [ReportsController::class, 'assetsIndex'])->name('assets');
            Route::post('/assets', [ReportsController::class, 'storeAsset'])->middleware('perm:accounts.manage')->name('assets.store');
            // Admin-only pages
            Route::middleware('role:admin')->group(function () {
                Route::get('/chart-of-accounts', [ReportsController::class, 'chartOfAccounts'])->name('chart-of-accounts');
                Route::get('/journal-entries', [ReportsController::class, 'journalEntries'])->name('journal-entries');
            });
        });
    });
    
    // Billing management (admin-only): control subscription, invoices, edit and payments
    Route::middleware(['can:manage-billing'])->prefix('billing')->name('billing.')->group(function () {
        // Dashboard
        Route::get('/', [BillingController::class, 'index'])->name('index');

        // Subscription management
        Route::get('/subscription', [BillingController::class, 'subscription'])->name('subscription');
        Route::get('/plans', [BillingController::class, 'plans'])->name('plans');
        Route::post('/subscription/upgrade', [BillingController::class, 'upgradeSubscription'])->name('subscription.upgrade');
        Route::post('/subscription/cancel', [BillingController::class, 'cancelSubscription'])->name('subscription.cancel');

        // Invoices management
        Route::get('/invoices', [BillingController::class, 'invoices'])->name('invoices');
        Route::get('/invoices/{invoice}', [BillingController::class, 'showInvoice'])->name('invoices.show');
        Route::get('/invoices/{invoice}/edit', [BillingController::class, 'editInvoice'])->name('invoices.edit');
        Route::put('/invoices/{invoice}', [BillingController::class, 'updateInvoice'])->name('invoices.update');
        Route::post('/invoices/{invoice}/pay', [BillingController::class, 'payInvoice'])->name('invoices.pay');

        // Payment methods (placeholder)
        Route::get('/payment-methods', [BillingController::class, 'paymentMethods'])->name('payment-methods');
        Route::post('/payment-methods', [BillingController::class, 'addPaymentMethod'])->name('payment-methods.add');
        Route::delete('/payment-methods/{method}', [BillingController::class, 'removePaymentMethod'])->name('payment-methods.remove');

        // Admin actions
        Route::post('/invoice/generate', [BillingController::class, 'generateGeneralInvoice'])->name('invoice.generate');
        Route::post('/suspend-nonpayment', [BillingController::class, 'suspendForNonPayment'])->name('suspend.nonpayment');
        Route::post('/credit-goodwill', [BillingController::class, 'creditGoodwill'])->name('credit.goodwill');
    });
    
    // Profile Management
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::patch('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
        Route::patch('/groups', [ProfileController::class, 'updateGroups'])->name('groups.update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });
    
    // Role Assignment Management
    Route::prefix('user/roles')->name('user.roles.')->group(function () {
        // View user's roles and assignments
        Route::get('/', [UserRoleController::class, 'index'])->name('index');
        
        // Admin-only routes for role assignment
        Route::middleware('role:admin')->group(function () {
            Route::get('/create', [UserRoleController::class, 'create'])->name('create');
            Route::post('/', [UserRoleController::class, 'store'])->name('store');
            Route::get('/pending', [UserRoleController::class, 'pending'])->name('pending');
            Route::delete('/{user}/role/{role}', [UserRoleController::class, 'removeRole'])->name('remove');
        });
        
        // Super admin-only routes for approval
        Route::middleware('role:super_admin')->group(function () {
            Route::post('/{assignment}/approve', [UserRoleController::class, 'approve'])->name('approve');
            Route::post('/{assignment}/reject', [UserRoleController::class, 'reject'])->name('reject');
        });
    });
    
    // Tenant settings routes
    Route::get('/tenant/settings', [TenantController::class, 'settings'])->name('tenant.settings');
    Route::post('/tenant/settings', [TenantController::class, 'updateSettings'])->name('tenant.settings.update');

    // Settings
    Route::middleware(['can:manage-settings'])->prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/general', [SettingsController::class, 'general'])->name('general');
        Route::post('/general', [SettingsController::class, 'updateGeneral'])->name('general.update');
        // Loan settings
        Route::get('/loan', [SettingsController::class, 'loan'])->name('loan');
        Route::post('/loan', [SettingsController::class, 'updateLoan'])->name('loan.update');
        // Users management (tenant-scoped)
        // Roles & permissions (placeholder)
        // Account settings (placeholder view)
        Route::get('/account', [SettingsController::class, 'account'])->name('account');
        // Security settings
        Route::get('/security', [SettingsController::class, 'security'])->name('security');
        Route::post('/security', [SettingsController::class, 'updateSecurity'])->name('security.update');
        // Policies management
        Route::get('/policies', [SettingsController::class, 'policies'])->name('policies');
        Route::post('/policies', [SettingsController::class, 'updatePolicies'])->name('policies.update');
        // Branches management (basic)
        Route::get('/branches', [SettingsController::class, 'branches'])->name('branches');
        Route::post('/branches', [SettingsController::class, 'storeBranch'])
            ->middleware('enforce.branch.limit')
            ->name('branches.store');
        Route::delete('/branches/{branch}', [SettingsController::class, 'deleteBranch'])->name('branches.delete');
        // Staffs (placeholder view)
        Route::get('/staffs', [SettingsController::class, 'staffs'])->name('staffs');
        // Login logs
        Route::get('/login-logs', [SettingsController::class, 'loginLogs'])->name('login-logs');

        Route::get('/users', [SettingsController::class, 'users'])->name('users');
        Route::post('/users', [SettingsController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{user}', [SettingsController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{user}', [SettingsController::class, 'deleteUser'])->name('users.delete');
        Route::get('/roles', [SettingsController::class, 'roles'])->name('roles');
        Route::post('/roles', [SettingsController::class, 'storeRole'])->name('roles.store');
        Route::put('/roles/{role}', [SettingsController::class, 'updateRole'])->name('roles.update');
        Route::delete('/roles/{role}', [SettingsController::class, 'deleteRole'])->name('roles.delete');
        Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
        Route::post('/notifications', [SettingsController::class, 'updateNotifications'])->name('notifications.update');
        Route::get('/backup', [SettingsController::class, 'backup'])->name('backup');
        Route::post('/backup', [SettingsController::class, 'updateBackup'])->name('backup.update');
        Route::post('/backup/create', [SettingsController::class, 'createBackup'])->name('backup.create');
        Route::post('/backup/restore', [SettingsController::class, 'restoreBackup'])->name('backup.restore');
    });

    // Loan Products under granular gate (broader access)
    Route::middleware(['can:manage-loan-products'])->prefix('settings')->name('settings.')->group(function () {
        Route::get('/loan-products', [SettingsController::class, 'loanProducts'])->name('loan-products');
        Route::post('/loan-products', [SettingsController::class, 'storeLoanProduct'])->name('loan-products.store');
        Route::put('/loan-products/{product}', [SettingsController::class, 'updateLoanProduct'])->name('loan-products.update');
        Route::delete('/loan-products/{product}', [SettingsController::class, 'deleteLoanProduct'])->name('loan-products.delete');
        Route::patch('/loan-products/{product}/toggle', [SettingsController::class, 'toggleLoanProductStatus'])->name('loan-products.toggle');
        // Handle manual navigation to a single loan-product by redirecting to index
        Route::get('/loan-products/{product}', function () {
            return redirect()->route('settings.loan-products');
        })->name('loan-products.show');
    });
});























// Tenant-facing subscription and billing routes (auth required)
Route::middleware(['auth'])->group(function () {
    Route::get('/subscribe/{plan:code}', [SubscribeController::class, 'show'])->name('subscribe.show');
    Route::post('/subscribe/{plan:code}/intent', [SubscribeController::class, 'intent'])->name('subscribe.intent');
    Route::post('/subscribe/{plan:code}/redirect', [SubscribeController::class, 'redirectToPayment'])->name('subscribe.redirect');
    Route::get('/subscribe/success', [SubscribeController::class, 'success'])->name('subscribe.success');
    Route::get('/subscribe/cancel', [SubscribeController::class, 'cancel'])->name('subscribe.cancel');
    Route::post('/billing/pay', [SubscribeController::class, 'pay'])->name('billing.pay');
    
    // Selcom USSD wallet payment routes
    Route::post('/subscribe/wallet-payment', [SubscribeController::class, 'processWalletPayment'])->name('subscribe.wallet-payment');
    Route::get('/subscribe/payment-status/{orderId}', [SubscribeController::class, 'checkPaymentStatus'])->name('subscribe.payment-status');

    // Billing overview and invoices
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');

    // Tenant invoice listing, show, edit, update, pay, and PDF
    Route::get('/billing/invoices', [BillingController::class, 'invoices'])->name('billing.invoices');
    Route::get('/billing/invoices/{invoice}', [BillingController::class, 'showInvoice'])->name('billing.invoices.show');
    Route::get('/billing/invoices/{invoice}/edit', [BillingController::class, 'editInvoice'])->name('billing.invoices.edit');
    Route::put('/billing/invoices/{invoice}', [BillingController::class, 'updateInvoice'])->name('billing.invoices.update');
    Route::post('/billing/invoices/{invoice}/pay', [BillingController::class, 'payInvoice'])->name('billing.invoices.pay');
    Route::get('/billing/invoices/{invoice}/pdf', [SubscribeController::class, 'invoicePdf'])->name('billing.invoices.pdf');

    // Subscription page
    Route::get('/billing/subscription', [BillingController::class, 'subscription'])->name('billing.subscription');
    
    // Subscription expired page (for trial expiration)
    Route::get('/subscription/expired', [BillingController::class, 'subscriptionExpired'])->name('subscription.expired');

    // Billing summary JSON
    Route::get('/billing/summary', [\App\Http\Controllers\Api\TenantBillingController::class, 'summary'])->name('billing.summary');

    // SMS Topup routes
    Route::prefix('sms')->name('sms.')->group(function () {
        Route::prefix('topup')->name('topup.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Tenant\SmsTopupController::class, 'index'])->name('index');
            Route::get('/history', [\App\Http\Controllers\Tenant\SmsTopupController::class, 'history'])->name('history');
            Route::post('/create', [\App\Http\Controllers\Tenant\SmsTopupController::class, 'create'])->name('create');
            Route::get('/{topup}', [\App\Http\Controllers\Tenant\SmsTopupController::class, 'show'])->name('show');
            Route::post('/{topup}/check-status', [\App\Http\Controllers\Tenant\SmsTopupController::class, 'checkStatus'])->name('check-status');
            Route::post('/{topup}/cancel', [\App\Http\Controllers\Tenant\SmsTopupController::class, 'cancel'])->name('cancel');
            Route::get('/{topup}/success', [\App\Http\Controllers\Tenant\SmsTopupController::class, 'success'])->name('success');
            Route::get('/{topup}/cancelled', [\App\Http\Controllers\Tenant\SmsTopupController::class, 'cancelled'])->name('cancelled');
            Route::get('/{topup}/receipt', [\App\Http\Controllers\Tenant\SmsTopupController::class, 'downloadReceipt'])->name('receipt');
        });
        
        // SMS Wallet AJAX routes
        Route::prefix('wallet')->name('wallet.')->group(function () {
            Route::get('/balance', [\App\Http\Controllers\Tenant\SmsTopupController::class, 'getBalance'])->name('balance');
            Route::get('/transactions', [\App\Http\Controllers\Tenant\SmsTopupController::class, 'getTransactions'])->name('transactions');
            Route::get('/stats', [\App\Http\Controllers\Tenant\SmsTopupController::class, 'getStats'])->name('stats');
            Route::post('/settings', [\App\Http\Controllers\Tenant\SmsTopupController::class, 'updateSettings'])->name('settings');
        });
    });
});

// Public pricing route
Route::get('/pricing', [PublicPricingController::class, 'index'])->name('pricing');

// Webhook routes (no authentication required)
Route::prefix('webhooks')->name('webhooks.')->group(function () {
    Route::post('/payment-gateway', [WebhookController::class, 'paymentGateway'])->name('payment-gateway');
    Route::post('/sms-delivery', [WebhookController::class, 'smsDelivery'])->name('sms-delivery');
    Route::post('/email-delivery', [WebhookController::class, 'emailDelivery'])->name('email-delivery');
    Route::post('/subscription-update', [WebhookController::class, 'subscriptionUpdate'])->name('subscription-update');
    // Billing-specific webhooks (e.g., Selcom)
    Route::post('/billing/{provider}', [BillingController::class, 'webhook'])->name('billing');
    // Alias for Selcom webhook without provider segment
    Route::post('/selcom', function (\Illuminate\Http\Request $request) {
        return app(\App\Http\Controllers\BillingController::class)->webhook($request, 'selcom');
    })->name('selcom');
    
    // Selcom repayment callback
    Route::post('/selcom/repayments', [RepaymentController::class, 'selcomCallback'])->name('selcom-repayments');
});

// SMS webhooks
Route::prefix('webhooks/sms')->group(function () {
    Route::post('/selcom', [\App\Http\Controllers\Webhooks\SelcomSmsWebhookController::class, 'handle'])->name('webhooks.sms.selcom');
    Route::post('/selcom/test', [\App\Http\Controllers\Webhooks\SelcomSmsWebhookController::class, 'test'])->name('webhooks.sms.selcom.test');
    
    // SMS Delivery Report webhooks
    Route::post('/delivery/{provider}', [\App\Http\Controllers\Webhooks\SmsDeliveryWebhookController::class, 'handle'])->name('webhooks.sms.delivery');
    Route::post('/delivery/{provider}/test', [\App\Http\Controllers\Webhooks\SmsDeliveryWebhookController::class, 'test'])->name('webhooks.sms.delivery.test');
});

// Tenant Registration (public routes)
Route::prefix('tenant')->name('tenant.')->group(function () {
    Route::get('/register', [App\Http\Controllers\TenantController::class, 'showRegistration'])->name('register');
    Route::post('/register', [App\Http\Controllers\TenantController::class, 'register']);
});

// Health check route
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'version' => config('app.version', '1.0.0')
    ]);
})->name('health');










Route::middleware(['web', 'auth', 'resolve.tenant', 'tenant.access', 'role:manager,loan_officer,officer,teller,accountant,gm,admin'])
    ->group(function () {
        // New: session-auth borrower search and JSON show for loan create page
        Route::get('/clients/search', [ClientController::class, 'search'])->name('clients.search');
        Route::get('/clients/json/{client}', [ClientController::class, 'json'])->name('clients.json');
    });

// Checkout routes (authenticated users only)
Route::middleware(['auth', 'resolve.tenant'])->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout/process', [CheckoutController::class, 'process'])->name('checkout.process');
    Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::post('/checkout/callback', [CheckoutController::class, 'callback'])->name('checkout.callback');
});










Route::get('/mail-test', function () {
    try {
         

        \Illuminate\Support\Facades\Mail::raw('Test email from PHID LMS', function ($message) {
            $message->to('phidtechnology@gmail.com')
                    ->subject('PHID LMS Test Email');
        });
        return response()->json(['status' => 'ok', 'message' => 'Mail dispatch attempted', 'mailer' => config('mail.default')], 200);
    } catch (\Throwable $e) {
        \Log::error('Mail test failed', ['error' => $e->getMessage()]);
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
})->name('mail.test');

// Include Accounting Routes
require __DIR__.'/accounting.php';

// Include SMS Management Routes
require __DIR__.'/sms.php';

// Temporary route to fix loan totals - visit /fix-loans-now in your browser
Route::get('/fix-loans-now', function () {
    set_time_limit(300);
    $output = ["Starting loan fixes..."];
    $loans = \App\Models\Loan::whereIn('status', ['pending', 'approved', 'disbursed', 'active'])->get();
    $output[] = "Found {$loans->count()} loans to process\n";
    
    foreach ($loans as $loan) {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($loan, &$output) {
                $output[] = "Processing Loan #{$loan->id} - {$loan->loan_number}";
                $rMonthly = 0.10;
                $interestType = optional($loan->product)->interest_type ?? 'flat';
                $repaymentSchedule = $loan->repayment_schedule ?? 'monthly';
                
                if ($interestType === 'flat') {
                    $termInMonths = $loan->term;
                    if ($repaymentSchedule === 'weekly') $termInMonths = $loan->term / 4;
                    elseif ($repaymentSchedule === 'daily') $termInMonths = $loan->term / 30;
                    
                    $totalInterest = round($loan->principal * $rMonthly * $termInMonths, 2);
                    $interestPerInstallment = round($totalInterest / $loan->term, 2);
                    $principalPerPayment = round($loan->principal / $loan->term, 2);
                    $monthlyPayment = $principalPerPayment + $interestPerInstallment;
                } else {
                    $monthlyPayment = round($loan->principal * $rMonthly / (1 - pow(1 + $rMonthly, -$loan->term)), 2);
                }
                
                $totalAmount = round($monthlyPayment * $loan->term, 2);
                $loan->update(['monthly_payment' => $monthlyPayment, 'total_amount' => $totalAmount]);
                $loan->schedules()->delete();
                
                $schedules = [];
                $paymentDate = \Carbon\Carbon::parse($loan->first_payment_date);
                $principalBalance = $loan->principal;
                
                if ($interestType === 'flat') {
                    $termInMonths = $loan->term;
                    if ($repaymentSchedule === 'weekly') $termInMonths = $loan->term / 4;
                    elseif ($repaymentSchedule === 'daily') $termInMonths = $loan->term / 30;
                    $totalInterest = round($loan->principal * $rMonthly * $termInMonths, 2);
                    $interestPerInstallment = round($totalInterest / $loan->term, 2);
                }
                
                for ($i = 1; $i <= $loan->term; $i++) {
                    if ($interestType === 'reducing' && $rMonthly > 0) {
                        $interestAmount = round($principalBalance * $rMonthly, 2);
                        $principalAmount = round($monthlyPayment - $interestAmount, 2);
                        if ($principalAmount < 0) $principalAmount = 0;
                    } else {
                        $interestAmount = $interestPerInstallment;
                        $principalAmount = round($loan->principal / $loan->term, 2);
                    }
                    
                    $totalPayment = round($principalAmount + $interestAmount, 2);
                    $principalBalance = max(0, round($principalBalance - $principalAmount, 2));
                    
                    $schedules[] = [
                        'tenant_id' => $loan->tenant_id,
                        'loan_id' => $loan->id,
                        'installment_number' => $i,
                        'due_date' => $paymentDate->copy(),
                        'principal_amount' => $principalAmount,
                        'interest_amount' => $interestAmount,
                        'total_amount' => $totalPayment,
                        'paid_amount' => 0,
                        'balance' => $principalBalance,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    
                    switch ($repaymentSchedule) {
                        case 'daily': $paymentDate->addDay(); break;
                        case 'weekly': $paymentDate->addWeek(); break;
                        default: $paymentDate->addMonth(); break;
                    }
                }
                
                \Illuminate\Support\Facades\DB::table('loan_schedules')->insert($schedules);
                
                foreach ($loan->repayments()->orderBy('payment_date')->get() as $repayment) {
                    $remainingAmount = $repayment->amount;
                    foreach ($loan->schedules()->where('status', '!=', 'paid')->orderBy('due_date')->get() as $schedule) {
                        if ($remainingAmount <= 0) break;
                        $unpaidAmount = $schedule->total_amount - $schedule->paid_amount;
                        $paymentForSchedule = min($remainingAmount, $unpaidAmount);
                        $schedule->paid_amount += $paymentForSchedule;
                        $remainingAmount -= $paymentForSchedule;
                        if ($schedule->paid_amount >= $schedule->total_amount) {
                            $schedule->status = 'paid';
                            $schedule->paid_date = $repayment->payment_date;
                        } elseif ($schedule->paid_amount > 0) {
                            $schedule->status = 'partial';
                        }
                        $schedule->save();
                    }
                }
                $output[] = "  ✓ Fixed! Total: {$totalAmount}, Payment: {$monthlyPayment}";
            });
        } catch (\Exception $e) {
            $output[] = "  ✗ Error: " . $e->getMessage();
        }
    }
    
    $output[] = "\n✅ Done! Fixed {$loans->count()} loans.";
    $output[] = "Now refresh your dashboard and repayments pages.";
    return response('<pre style="font-family: monospace; padding: 20px;">' . implode("\n", $output) . '</pre>');
})->name('fix.loans.now');

// Temporary route to fix user RBAC roles - visit /fix-user-roles in your browser
Route::get('/fix-user-roles', function () {
    set_time_limit(300);
    $output = ["Starting user RBAC role fix..."];

    // Map role column values to RBAC role slugs
    $roleSlugMap = [
        'administrator' => 'admin',
        'credit_officer' => 'loan_officer',
        'cashier' => 'accountant',
        'staff' => 'officer',
    ];

    $users = \App\Models\User::all();
    $output[] = "Found {$users->count()} users to process\n";

    $fixed = 0;
    $skipped = 0;
    $errors = 0;

    foreach ($users as $user) {
        try {
            $tenantId = $user->tenant_id;
            if (!$tenantId) {
                $output[] = "⏭ User #{$user->id} ({$user->name}) - No tenant, skipped";
                $skipped++;
                continue;
            }

            $roleSlug = $user->role ?? 'officer';
            $rbacSlug = $roleSlugMap[$roleSlug] ?? $roleSlug;

            // Check if user already has a role assigned
            $existingRole = \Illuminate\Support\Facades\DB::table('user_role')
                ->where('tenant_id', $tenantId)
                ->where('user_id', $user->id)
                ->first();

            if ($existingRole) {
                $output[] = "✓ User #{$user->id} ({$user->name}) - Already has RBAC role, skipped";
                $skipped++;
                continue;
            }

            // Find the RBAC role for this tenant
            $rbacRole = \App\Models\Role::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('slug', $rbacSlug)
                ->first();

            if (!$rbacRole) {
                // Seed RBAC defaults for this tenant
                $tenant = \App\Models\Tenant::find($tenantId);
                if ($tenant) {
                    $seed = \App\Services\RbacService::seedDefaultsForTenant($tenant);
                    $rbacRole = $seed['roles'][$rbacSlug] ?? null;
                    $output[] = "  → Seeded RBAC defaults for tenant #{$tenantId} ({$tenant->name})";
                }
            }

            if ($rbacRole) {
                \App\Services\RbacService::attachUserRole($user, $rbacRole);
                $output[] = "✅ User #{$user->id} ({$user->name}) - Assigned RBAC role '{$rbacSlug}' (from column '{$roleSlug}')";
                $fixed++;
            } else {
                $output[] = "⚠ User #{$user->id} ({$user->name}) - Could not find RBAC role '{$rbacSlug}'";
                $errors++;
            }
        } catch (\Throwable $e) {
            $output[] = "❌ User #{$user->id} ({$user->name}) - Error: {$e->getMessage()}";
            $errors++;
        }
    }

    $output[] = "\n========================================";
    $output[] = "Done! Fixed: {$fixed}, Skipped: {$skipped}, Errors: {$errors}";
    $output[] = "All users should now have proper permissions.";
    $output[] = "Staff can now see loans, repayments, and other data.";

    return response('<pre style="font-family: monospace; padding: 20px;">' . implode("\n", $output) . '</pre>');
})->name('fix.user.roles');

// Temporary route to fix existing loans stuck on wrong approval stages - visit /fix-loan-stages in your browser
Route::get('/fix-loan-stages', function () {
    set_time_limit(300);
    $output = ["Starting loan approval stage fix..."];

    // Get all pending loans
    $pendingLoans = \App\Models\Loan::where('status', 'pending')
        ->where('approval_stage_status', 'pending')
        ->get();

    $output[] = "Found {$pendingLoans->count()} pending loans to check\n";

    $fixed = 0;
    $skipped = 0;

    foreach ($pendingLoans as $loan) {
        $tenantId = $loan->tenant_id;
        $currentStage = $loan->approval_stage;
        $stages = \App\Services\ApprovalPipelineService::getStagesForTenant($tenantId);

        if (empty($stages)) {
            // No approval stages needed for this tenant (only admin) - auto-approve
            $loan->update([
                'status' => 'approved',
                'approval_stage' => 'approved',
                'approval_stage_status' => 'approved',
            ]);
            $output[] = "✅ Loan #{$loan->id} ({$loan->loan_number}) - Auto-approved (tenant has no approval staff)";
            $fixed++;
            continue;
        }

        // Check if current stage is valid for this tenant
        if (!in_array($currentStage, $stages, true)) {
            // Current stage doesn't apply - move to first valid stage
            $firstStage = $stages[0];
            $loan->update([
                'approval_stage' => $firstStage,
                'approval_stage_status' => 'pending',
            ]);
            $output[] = "✅ Loan #{$loan->id} ({$loan->loan_number}) - Moved from '{$currentStage}' to '{$firstStage}'";
            $fixed++;
        } else {
            $output[] = "✓ Loan #{$loan->id} ({$loan->loan_number}) - Stage '{$currentStage}' is valid, skipped";
            $skipped++;
        }
    }

    $output[] = "\n========================================";
    $output[] = "Done! Fixed: {$fixed}, Skipped: {$skipped}";
    $output[] = "Approval stages now match tenant's actual staff roles.";

    return response('<pre style="font-family: monospace; padding: 20px;">' . implode("\n", $output) . '</pre>');
})->name('fix.loan.stages');

// Reset ALL account opening_balance and current_balance to zero - visit /fix-all-balances
Route::get('/fix-all-balances', function () {
    $output = ["Resetting ALL account balances to zero..."];

    // Delete wrongly-posted Opening Balance journal entries
    $deleted = \App\Models\Accounting\JournalEntry::where('entry_type', 'opening_balance')
        ->orWhere('entry_number', 'like', 'OB-%')
        ->count();
    \App\Models\Accounting\JournalEntry::where('entry_type', 'opening_balance')
        ->orWhere('entry_number', 'like', 'OB-%')
        ->delete();
    $output[] = "🗑️  Deleted {$deleted} opening balance journal entries";

    // Reset all account balances to zero
    $accounts = \App\Models\Accounting\ChartOfAccount::all();
    foreach ($accounts as $account) {
        $account->opening_balance = 0;
        $account->current_balance = 0;
        $account->save();
        $output[] = "✅ [{$account->account_code}] {$account->account_name} → 0.00";
    }

    $output[] = "\nDone! All balances reset to zero. Balances will now reflect only actual posted transactions.";
    return response('<pre style="font-family: monospace; padding: 20px;">' . implode("\n", $output) . '</pre>');
})->name('fix.all.balances');

// Backfill missing journal entries for disbursements & repayments - visit /fix-unposted-entries
Route::get('/fix-unposted-entries', function () {
    set_time_limit(600);
    $output = ["Backfilling missing journal entries..."];

    $accountingService = app(\App\Services\Accounting\AutomatedAccountingService::class);

    // 1. Disbursements with no journal entry
    $disbursedLoans = \App\Models\Loan::whereIn('status', ['disbursed', 'active', 'partially_paid', 'completed'])
        ->with(['client'])
        ->get();

    $disbPosted = 0; $disbSkipped = 0;
    foreach ($disbursedLoans as $loan) {
        $exists = \App\Models\Accounting\JournalEntry::where('entry_type', 'loan_disbursement')
            ->where('reference_type', \App\Models\Loan::class)
            ->where('reference_id', $loan->id)
            ->exists();
        if ($exists) { $disbSkipped++; continue; }
        try {
            $accountingService->recordLoanDisbursement($loan);
            $output[] = "✅ Disbursement posted: Loan #{$loan->loan_number} - " . ($loan->client->first_name ?? '') . " " . ($loan->client->last_name ?? '');
            $disbPosted++;
        } catch (\Throwable $e) {
            $output[] = "❌ Disbursement failed: Loan #{$loan->loan_number} - " . $e->getMessage();
        }
    }
    $output[] = "\nDisbursements: Posted={$disbPosted}, Already existed={$disbSkipped}";

    // 2. Repayments with no journal entry
    $repayments = \App\Models\Repayment::with(['loan.client', 'schedule'])->get();
    $repPosted = 0; $repSkipped = 0;
    foreach ($repayments as $repayment) {
        $exists = \App\Models\Accounting\JournalEntry::where('entry_type', 'loan_repayment')
            ->where('reference_type', \App\Models\Repayment::class)
            ->where('reference_id', $repayment->id)
            ->exists();
        if ($exists) { $repSkipped++; continue; }
        try {
            $accountingService->recordLoanRepayment($repayment);
            $output[] = "✅ Repayment posted: {$repayment->receipt_number} - " . ($repayment->loan->client->first_name ?? '') . " " . ($repayment->loan->client->last_name ?? '');
            $repPosted++;
        } catch (\Throwable $e) {
            $output[] = "❌ Repayment failed: {$repayment->receipt_number} - " . $e->getMessage();
        }
    }
    $output[] = "\nRepayments: Posted={$repPosted}, Already existed={$repSkipped}";
    $output[] = "\n✅ Done! Refresh journal entries page to see all posted entries.";

    return response('<pre style="font-family: monospace; padding: 20px;">' . implode("\n", $output) . '</pre>');
})->name('fix.unposted.entries');

// Fix schedule statuses for all loans based on actual repayments - visit /fix-schedule-statuses
Route::get('/fix-schedule-statuses', function () {
    $output = ["Recalculating schedule statuses from actual repayments...\n"];

    $loans = \App\Models\Loan::with(['schedules', 'repayments'])->get();

    foreach ($loans as $loan) {
        $schedules = $loan->schedules->sortBy('due_date')->values();
        if ($schedules->isEmpty()) continue;

        // Reset all paid_amount on schedules to 0 first
        foreach ($schedules as $schedule) {
            $schedule->paid_amount = 0;
            $schedule->status = 'pending';
            $schedule->paid_date = null;
        }

        // Distribute repayments chronologically across schedules
        $repayments = $loan->repayments->sortBy('payment_date');
        $remaining = 0;
        foreach ($repayments as $repayment) {
            $remaining += $repayment->amount;
        }

        $totalRemaining = $remaining;
        foreach ($schedules as $schedule) {
            if ($totalRemaining <= 0) break;

            $unpaid = $schedule->total_amount - $schedule->paid_amount;
            $payment = min($totalRemaining, $unpaid);

            $schedule->paid_amount += $payment;
            $totalRemaining -= $payment;

            if ($schedule->paid_amount >= $schedule->total_amount) {
                $schedule->status = 'paid';
                $schedule->paid_date = now()->toDateString();
            } elseif ($schedule->paid_amount > 0) {
                $schedule->status = 'partial';
            }

            $schedule->save();
        }

        // Update loan status
        $unpaidCount = $loan->schedules()->whereNotIn('status', ['paid'])->count();
        if ($unpaidCount === 0) {
            $loan->update(['status' => 'completed']);
        } elseif ($loan->repayments->count() > 0 && in_array($loan->status, ['disbursed', 'active', 'partially_paid'])) {
            $loan->update(['status' => 'active']);
        }

        $paidCount = $schedules->where('status', 'paid')->count();
        $output[] = "✅ Loan #{$loan->loan_number}: {$paidCount}/{$schedules->count()} installments marked paid";
    }

    $output[] = "\n✅ Done! Refresh the repayment create page to see correct schedule statuses.";
    return response('<pre style="font-family: monospace; padding: 20px;">' . implode("\n", $output) . '</pre>');
})->name('fix.schedule.statuses');

// Zero out Penalty Income account (4300) - visit /fix-penalty-income
Route::get('/fix-penalty-income', function () {
    $output = ["Zeroing Penalty Income account (4300)...\n"];

    $accounts = \App\Models\Accounting\ChartOfAccount::where('account_code', '4300')
        ->orWhere(function($q) {
            $q->where('account_type', 'income')
              ->where('account_name', 'like', '%penalty%');
        })->get();

    if ($accounts->isEmpty()) {
        $output[] = "❌ No Penalty Income account found with code 4300.";
    }

    foreach ($accounts as $account) {
        $old = $account->current_balance;
        $account->opening_balance = 0;
        $account->current_balance = 0;
        $account->save();
        $output[] = "✅ [{$account->account_code}] {$account->account_name}: {$old} → 0.00";
    }

    // Also delete any journal entry lines credited to this account so the income statement shows 0
    foreach ($accounts as $account) {
        $deleted = \App\Models\Accounting\JournalEntryLine::where('account_id', $account->id)->delete();
        $output[] = "🗑️  Deleted {$deleted} journal lines for [{$account->account_code}]";
    }

    $output[] = "\n✅ Done! Penalty Income now shows 0. Future penalties will be posted from actual repayments.";
    return response('<pre style="font-family: monospace; padding: 20px;">' . implode("\n", $output) . '</pre>');
})->name('fix.penalty.income');

// Temporary route to fix Rehema's schedule
Route::get('/temp-fix-rehema', function () {
    $output = [];
    $output[] = "Fixing Rehema Ally Mchezo schedule with due date 2025-04-03...";
    $output[] = "";
    
    // Find Rehema's schedule with due date 2025-04-03
    $schedules = \App\Models\LoanSchedule::with(['loan.client'])
        ->whereHas('loan.client', function($q) {
            $q->where('first_name', 'LIKE', '%Rehema%')
               ->where('last_name', 'LIKE', '%Ally%');
        })
        ->where('due_date', '2025-04-03')
        ->get();

    $output[] = "Found " . $schedules->count() . " schedule(s)";
    $output[] = "";

    foreach($schedules as $schedule) {
        $output[] = "Client: " . $schedule->loan->client->first_name . " " . $schedule->loan->client->last_name;
        $output[] = "Loan Number: " . $schedule->loan->loan_number;
        $output[] = "Installment: " . $schedule->installment_number;
        $output[] = "Due Date: " . $schedule->due_date;
        $output[] = "Current Status: " . $schedule->status;
        $output[] = "Paid Amount: " . $schedule->paid_amount;
        $output[] = "Total Amount: " . $schedule->total_amount;
        $output[] = "Paid Date: " . ($schedule->paid_date ?? 'NULL');
        $output[] = "---";
        
        // Update to unpaid
        $schedule->status = 'pending';
        $schedule->paid_amount = 0;
        $schedule->paid_date = null;
        $schedule->payment_method = null;
        
        if ($schedule->save()) {
            $output[] = "UPDATED: Marked as unpaid";
            $output[] = "New Status: " . $schedule->fresh()->status;
            $output[] = "New Paid Amount: " . $schedule->fresh()->paid_amount;
            $output[] = "New Paid Date: " . ($schedule->fresh()->paid_date ?? 'NULL');
        } else {
            $output[] = "ERROR: Failed to update";
        }
        $output[] = "===";
        $output[] = "";
    }

    $output[] = "Done!";
    
    return response('<pre style="font-family: monospace; padding: 20px;">' . implode("\n", $output) . '</pre>');
})->name('temp.fix.rehema');

// Debug route to check user permissions
Route::get('/debug-admin-access', function () {
    $output = [];
    
    // Show ALL tenant users regardless of login
    $output[] = "=== ALL TENANT USERS IN DATABASE ===";
    $users = \Illuminate\Support\Facades\DB::table('users')
        ->whereNotNull('tenant_id')
        ->orderBy('tenant_id')
        ->get(['id','name','email','role','position','admin_role_id','tenant_id']);
    foreach ($users as $u) {
        $output[] = "ID:{$u->id} | {$u->name} | {$u->email} | role={$u->role} | pos={$u->position} | admin_role_id={$u->admin_role_id} | tenant_id={$u->tenant_id}";
    }
    $output[] = "";

    // Show current logged in user
    if (!auth()->check()) {
        $output[] = "=== NOT LOGGED IN ===";
        return response('<pre style="font-family: monospace; padding: 20px;">' . implode("\n", $output) . '</pre>');
    }
    
    $user = auth()->user();
    $output[] = "=== LOGGED IN USER ===";
    $output[] = "User ID: " . $user->id;
    $output[] = "Name: " . $user->name;
    $output[] = "Email: " . $user->email;
    $output[] = "Role: " . ($user->role ?? 'NULL');
    $output[] = "Position: " . ($user->position ?? 'NULL');
    $output[] = "Tenant ID: " . ($user->tenant_id ?? 'NULL');
    $output[] = "Admin Role ID: " . ($user->admin_role_id ?? 'NULL');
    $output[] = "";
    $output[] = "isAdmin(): " . ($user->isAdmin() ? 'YES' : 'NO');
    $output[] = "isSuperAdmin(): " . ($user->isSuperAdmin() ? 'YES' : 'NO');
    $output[] = "role === admin: " . (strtolower($user->role ?? '') === 'admin' ? 'YES' : 'NO');
    $output[] = "role === gm: " . (strtolower($user->role ?? '') === 'gm' ? 'YES' : 'NO');
    $output[] = "";

    if ($user->tenant_id) {
        $tenant = $user->tenant;
        $output[] = "Tenant Name: " . ($tenant ? $tenant->name : 'Not Found');
        $output[] = "Tenant Status: " . ($tenant ? $tenant->status : 'Not Found');
    }
    
    return response('<pre style="font-family: monospace; padding: 20px;">' . implode("\n", $output) . '</pre>');
})->name('debug.admin.access');

// Delete KWARE MICROFINANCE data
Route::get('/delete-kware-data', function () {
    $output = [];
    $output[] = "=== KWARE MICROFINANCE Data Deletion ===";
    $output[] = "";
    
    // Find KWARE MICROFINANCE tenant
    $tenant = \Illuminate\Support\Facades\DB::table('tenants')
        ->where('name', 'LIKE', '%KWARE%')
        ->orWhere('name', 'LIKE', '%MICROFINANCE%')
        ->first();

    if (!$tenant) {
        $output[] = "❌ KWARE MICROFINANCE tenant not found!";
        return response('<pre style="font-family: monospace; padding: 20px;">' . implode("\n", $output) . '</pre>');
    }

    $output[] = "Found KWARE MICROFINANCE tenant:";
    $output[] = "ID: " . $tenant->id;
    $output[] = "Name: " . $tenant->name;
    $output[] = "Slug: " . $tenant->slug;
    $output[] = "";

    $tenantId = $tenant->id;
    $deletedCount = 0;

    try {
        \Illuminate\Support\Facades\DB::beginTransaction();

        $output[] = "Starting deletion process...";

        // 1. Delete repayments
        $repayments = \Illuminate\Support\Facades\DB::table('repayments')
            ->join('loans', 'repayments.loan_id', '=', 'loans.id')
            ->where('loans.tenant_id', $tenantId)
            ->count();
        \Illuminate\Support\Facades\DB::table('repayments')
            ->join('loans', 'repayments.loan_id', '=', 'loans.id')
            ->where('loans.tenant_id', $tenantId)
            ->delete();
        $deletedCount += $repayments;
        $output[] = "✅ Deleted {$repayments} repayments";

        // 2. Delete loan schedules
        $schedules = \Illuminate\Support\Facades\DB::table('loan_schedules')
            ->join('loans', 'loan_schedules.loan_id', '=', 'loans.id')
            ->where('loans.tenant_id', $tenantId)
            ->count();
        \Illuminate\Support\Facades\DB::table('loan_schedules')
            ->join('loans', 'loan_schedules.loan_id', '=', 'loans.id')
            ->where('loans.tenant_id', $tenantId)
            ->delete();
        $deletedCount += $schedules;
        $output[] = "✅ Deleted {$schedules} loan schedules";

        // 3. Delete loans
        $loans = \Illuminate\Support\Facades\DB::table('loans')
            ->where('tenant_id', $tenantId)
            ->count();
        \Illuminate\Support\Facades\DB::table('loans')
            ->where('tenant_id', $tenantId)
            ->delete();
        $deletedCount += $loans;
        $output[] = "✅ Deleted {$loans} loans";

        // 4. Delete clients
        $clients = \Illuminate\Support\Facades\DB::table('clients')
            ->where('tenant_id', $tenantId)
            ->count();
        \Illuminate\Support\Facades\DB::table('clients')
            ->where('tenant_id', $tenantId)
            ->delete();
        $deletedCount += $clients;
        $output[] = "✅ Deleted {$clients} clients";

        // 5. Delete users (excluding super admin)
        $users = \Illuminate\Support\Facades\DB::table('users')
            ->where('tenant_id', $tenantId)
            ->where('email', '!=', 'phidtechnology@gmail.com')
            ->count();
        \Illuminate\Support\Facades\DB::table('users')
            ->where('tenant_id', $tenantId)
            ->where('email', '!=', 'phidtechnology@gmail.com')
            ->delete();
        $deletedCount += $users;
        $output[] = "✅ Deleted {$users} users";

        // 6. Delete branches
        $branches = \Illuminate\Support\Facades\DB::table('branches')
            ->where('tenant_id', $tenantId)
            ->count();
        \Illuminate\Support\Facades\DB::table('branches')
            ->where('tenant_id', $tenantId)
            ->delete();
        $deletedCount += $branches;
        $output[] = "✅ Deleted {$branches} branches";

        // 7. Delete tenant roles
        $roles = \Illuminate\Support\Facades\DB::table('roles')
            ->where('tenant_id', $tenantId)
            ->count();
        \Illuminate\Support\Facades\DB::table('roles')
            ->where('tenant_id', $tenantId)
            ->delete();
        $deletedCount += $roles;
        $output[] = "✅ Deleted {$roles} roles";

        // 8. Delete permissions
        $permissions = \Illuminate\Support\Facades\DB::table('permissions')
            ->where('tenant_id', $tenantId)
            ->count();
        \Illuminate\Support\Facades\DB::table('permissions')
            ->where('tenant_id', $tenantId)
            ->delete();
        $deletedCount += $permissions;
        $output[] = "✅ Deleted {$permissions} permissions";

        // 9. Delete subscriptions
        $subscriptions = \Illuminate\Support\Facades\DB::table('subscriptions')
            ->where('tenant_id', $tenantId)
            ->count();
        \Illuminate\Support\Facades\DB::table('subscriptions')
            ->where('tenant_id', $tenantId)
            ->delete();
        $deletedCount += $subscriptions;
        $output[] = "✅ Deleted {$subscriptions} subscriptions";

        // 10. Delete the tenant itself
        \Illuminate\Support\Facades\DB::table('tenants')
            ->where('id', $tenantId)
            ->delete();
        $deletedCount += 1;
        $output[] = "✅ Deleted tenant record";

        \Illuminate\Support\Facades\DB::commit();

        $output[] = "";
        $output[] = "=== DELETION COMPLETED ===";
        $output[] = "✅ KWARE MICROFINANCE data deletion completed successfully!";
        $output[] = "📊 Total records deleted: {$deletedCount}";
        $output[] = "🏢 Tenant ID: {$tenantId} has been completely removed from the system.";

        // Log the deletion
        \Illuminate\Support\Facades\Log::warning('KWARE MICROFINANCE data deleted', [
            'tenant_id' => $tenantId,
            'tenant_name' => $tenant->name,
            'deleted_records' => $deletedCount,
            'deleted_by' => auth()->user() ? auth()->user()->email : 'web_route',
            'deleted_at' => now(),
        ]);

    } catch (Exception $e) {
        \Illuminate\Support\Facades\DB::rollBack();
        $output[] = "❌ Error during deletion: " . $e->getMessage();
        
        \Illuminate\Support\Facades\Log::error('KWARE MICROFINANCE deletion failed', [
            'tenant_id' => $tenantId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    return response('<pre style="font-family: monospace; padding: 20px;">' . implode("\n", $output) . '</pre>');
})->name('delete.kware.data');
