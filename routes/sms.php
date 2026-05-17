<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\Admin\SmsAdminController;

/*
|--------------------------------------------------------------------------
| SMS Management Routes
|--------------------------------------------------------------------------
|
| Routes for the SMS Management Module - Reseller Architecture
| - Tenant routes for sending SMS, managing sender IDs, and purchasing packages
| - Admin routes for provider settings, package management, and reporting
|
*/

// Tenant SMS Routes
Route::middleware(['auth', 'resolve.tenant', 'tenant.access'])->group(function () {
    Route::prefix('messages')->name('messages.')->group(function () {
        // Dashboard
        Route::get('/', [SmsController::class, 'index'])->name('index');
        Route::get('/debug-balance', [SmsController::class, 'debugBalance'])->name('debug-balance');
        
        // Compose & Send SMS
        Route::get('/compose', [SmsController::class, 'compose'])->name('compose');
        Route::post('/send', [SmsController::class, 'send'])->name('send');
        Route::post('/quick-send', [SmsController::class, 'quickSend'])->name('quick-send');
        
        // Message History
        Route::get('/history', [SmsController::class, 'history'])->name('history');
        
        // Sender ID Management
        Route::get('/sender-ids', [SmsController::class, 'senderIds'])->name('sender-ids');
        Route::get('/sender-ids/request', [SmsController::class, 'requestSenderId'])->name('request-sender-id');
        Route::post('/sender-ids/request', [SmsController::class, 'storeSenderIdRequest'])->name('store-sender-id');
        Route::get('/sender-ids/{id}/edit', [SmsController::class, 'editSenderIdRequest'])->name('edit-sender-id');
        Route::put('/sender-ids/{id}', [SmsController::class, 'updateSenderIdRequest'])->name('update-sender-id');
        
        // SMS Packages & Purchase
        Route::get('/packages', [SmsController::class, 'packages'])->name('packages');
        Route::post('/packages/purchase', [SmsController::class, 'purchasePackage'])->name('package.purchase');
        Route::get('/payment/{purchaseRequest}', [SmsController::class, 'paymentPage'])->name('payment');
        Route::get('/payment/{purchaseRequest}/success', [SmsController::class, 'paymentSuccess'])->name('payment.success');
        Route::get('/payment/{purchaseRequest}/cancel', [SmsController::class, 'paymentCancel'])->name('payment.cancel');
        
        // Templates
        Route::get('/templates', [SmsController::class, 'templates'])->name('templates');
        Route::post('/templates', [SmsController::class, 'storeTemplate'])->name('store-template');
        Route::delete('/templates/{template}', [SmsController::class, 'deleteTemplate'])->name('delete-template');
        
        // API Endpoints
        Route::get('/api/balance', [SmsController::class, 'getBalance'])->name('api.balance');
        Route::get('/api/clients', [SmsController::class, 'getClients'])->name('api.clients');
    });
});

// Admin SMS Management Routes (Super Admin Only)
Route::middleware(['auth', 'resolve.tenant', 'role:super_admin'])->group(function () {
    Route::prefix('admin/sms')->name('admin.sms.')->group(function () {
        // Dashboard
        Route::get('/', [SmsAdminController::class, 'dashboard'])->name('dashboard');
        
        // Provider Settings
        Route::get('/settings', [SmsAdminController::class, 'settings'])->name('settings');
        Route::post('/settings', [SmsAdminController::class, 'updateSettings'])->name('update-settings');
        Route::post('/sync-balance', [SmsAdminController::class, 'syncBalance'])->name('sync-balance');
        Route::post('/sync-sender-ids', [SmsAdminController::class, 'syncSenderIds'])->name('sync-sender-ids');
        
        // SMS Packages
        Route::get('/packages', [SmsAdminController::class, 'packages'])->name('packages');
        Route::get('/packages/create', [SmsAdminController::class, 'createPackage'])->name('create-package');
        Route::post('/packages', [SmsAdminController::class, 'storePackage'])->name('store-package');
        Route::get('/packages/{package}/edit', [SmsAdminController::class, 'editPackage'])->name('edit-package');
        Route::put('/packages/{package}', [SmsAdminController::class, 'updatePackage'])->name('update-package');
        Route::post('/packages/{package}/toggle', [SmsAdminController::class, 'togglePackage'])->name('toggle-package');
        
        // Sender ID Requests
        Route::get('/sender-id-requests', [SmsAdminController::class, 'senderIdRequests'])->name('sender-id-requests');
        Route::post('/sender-id-requests/{senderIdRequest}/approve', [SmsAdminController::class, 'approveSenderId'])->name('approve-sender-id');
        Route::post('/sender-id-requests/{senderIdRequest}/reject', [SmsAdminController::class, 'rejectSenderId'])->name('reject-sender-id');
        
        // Tenant Balances
        Route::get('/tenant-balances', [SmsAdminController::class, 'tenantBalances'])->name('tenant-balances');
        Route::post('/tenant-balances/credit', [SmsAdminController::class, 'creditTenant'])->name('credit-tenant');
        Route::post('/tenant-balances/debit', [SmsAdminController::class, 'debitTenant'])->name('debit-tenant');
        
        // Reports
        Route::get('/reports', [SmsAdminController::class, 'reports'])->name('reports');
        Route::get('/all-messages', [SmsAdminController::class, 'allMessages'])->name('all-messages');
    });
});

// SMS Payment Callback (no auth - called by Selcom)
Route::post('/webhooks/sms-purchase', [SmsController::class, 'paymentCallback'])->name('webhooks.sms-purchase');
