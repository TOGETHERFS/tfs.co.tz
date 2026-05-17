<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Accounting\AccountingDashboardController;
use App\Http\Controllers\Accounting\ChartOfAccountsController;
use App\Http\Controllers\Accounting\JournalEntryController;
use App\Http\Controllers\Accounting\FinancialReportsController;
use App\Http\Controllers\Accounting\ExpenseController;
use App\Http\Controllers\Accounting\FixedAssetController;
use App\Http\Controllers\Accounting\FiscalYearController;
use App\Http\Controllers\Accounting\AuditTrailController;

Route::prefix('accounting')->name('accounting.')->middleware(['auth', 'resolve.tenant', 'tenant.access', 'perm:accounts.view'])->group(function () {
    
    // Dashboard
    Route::get('/', [AccountingDashboardController::class, 'index'])->name('dashboard');

    // Chart of Accounts
    Route::prefix('chart-of-accounts')->name('chart-of-accounts.')->group(function () {
        Route::get('/', [ChartOfAccountsController::class, 'index'])->name('index');
        Route::get('/create', [ChartOfAccountsController::class, 'create'])->name('create');
        Route::post('/', [ChartOfAccountsController::class, 'store'])->name('store');
        Route::post('/setup-defaults', [ChartOfAccountsController::class, 'setupDefaults'])->name('setup-defaults');
        Route::post('/reset-balances', [ChartOfAccountsController::class, 'resetBalances'])->name('reset-balances');
        Route::get('/opening-balance', [ChartOfAccountsController::class, 'openingBalance'])->name('opening-balance');
        Route::post('/opening-balance', [ChartOfAccountsController::class, 'storeOpeningBalance'])->name('opening-balance.store');
        Route::get('/{chartOfAccount}/edit', [ChartOfAccountsController::class, 'edit'])->name('edit');
        Route::get('/{chartOfAccount}', [ChartOfAccountsController::class, 'edit'])->name('show');
        Route::put('/{chartOfAccount}', [ChartOfAccountsController::class, 'update'])->name('update');
        Route::delete('/{chartOfAccount}', [ChartOfAccountsController::class, 'destroy'])->name('destroy');
    });

    // Journal Entries
    Route::prefix('journal-entries')->name('journal-entries.')->group(function () {
        Route::get('/', [JournalEntryController::class, 'index'])->name('index');
        Route::get('/pending', [JournalEntryController::class, 'pending'])->name('pending');
        Route::get('/create', [JournalEntryController::class, 'create'])->name('create');
        Route::post('/', [JournalEntryController::class, 'store'])->name('store');
        Route::get('/{journalEntry}', [JournalEntryController::class, 'show'])->name('show');
        Route::get('/{journalEntry}/edit', [JournalEntryController::class, 'edit'])->name('edit');
        Route::put('/{journalEntry}', [JournalEntryController::class, 'update'])->name('update');
        Route::post('/{journalEntry}/approve', [JournalEntryController::class, 'approve'])->name('approve');
        Route::post('/{journalEntry}/post', [JournalEntryController::class, 'post'])->name('post');
        Route::post('/{journalEntry}/reject', [JournalEntryController::class, 'reject'])->name('reject');
        Route::post('/{journalEntry}/reverse', [JournalEntryController::class, 'reverse'])->name('reverse');
    });

    // Financial Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [FinancialReportsController::class, 'index'])->name('index');
        Route::get('/trial-balance', [FinancialReportsController::class, 'trialBalance'])->name('trial-balance');
        Route::get('/balance-sheet', [FinancialReportsController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('/income-statement', [FinancialReportsController::class, 'incomeStatement'])->name('income-statement');
        Route::get('/cash-flow', [FinancialReportsController::class, 'cashFlow'])->name('cash-flow');
        Route::get('/general-ledger', [FinancialReportsController::class, 'generalLedger'])->name('general-ledger');
        Route::get('/account-statement/{account}', [FinancialReportsController::class, 'accountStatement'])->name('account-statement');
        Route::get('/books-of-accounts', [FinancialReportsController::class, 'booksOfAccounts'])->name('books-of-accounts');
    });

    // Expenses
    Route::prefix('expenses')->name('expenses.')->group(function () {
        Route::get('/', [ExpenseController::class, 'index'])->name('index');
        Route::get('/pending', [ExpenseController::class, 'pending'])->name('pending');
        Route::get('/categories', [ExpenseController::class, 'categories'])->name('categories');
        Route::post('/categories', [ExpenseController::class, 'storeCategory'])->name('categories.store');
        Route::post('/categories/quick-add', [ExpenseController::class, 'quickAddCategory'])->name('categories.quick-add');
        Route::delete('/categories/{category}', [ExpenseController::class, 'destroyCategory'])->name('categories.destroy');
        Route::get('/create', [ExpenseController::class, 'create'])->name('create');
        Route::post('/', [ExpenseController::class, 'store'])->name('store');
        Route::get('/{expense}', [ExpenseController::class, 'show'])->name('show');
        Route::get('/{expense}/edit', [ExpenseController::class, 'edit'])->name('edit');
        Route::put('/{expense}', [ExpenseController::class, 'update'])->name('update');
        Route::post('/{expense}/approve', [ExpenseController::class, 'approve'])->name('approve');
        Route::post('/{expense}/reject', [ExpenseController::class, 'reject'])->name('reject');
        Route::delete('/{expense}', [ExpenseController::class, 'destroy'])->name('destroy');
    });

    // Fixed Assets
    Route::prefix('fixed-assets')->name('fixed-assets.')->group(function () {
        Route::get('/', [FixedAssetController::class, 'index'])->name('index');
        Route::get('/categories', [FixedAssetController::class, 'categories'])->name('categories');
        Route::post('/categories', [FixedAssetController::class, 'storeCategory'])->name('categories.store');
        Route::delete('/categories/{category}', [FixedAssetController::class, 'destroyCategory'])->name('categories.destroy');
        Route::post('/run-depreciation', [FixedAssetController::class, 'runMonthlyDepreciation'])->name('run-depreciation');
        Route::get('/create', [FixedAssetController::class, 'create'])->name('create');
        Route::post('/', [FixedAssetController::class, 'store'])->name('store');
        Route::get('/{fixedAsset}', [FixedAssetController::class, 'show'])->name('show');
        Route::get('/{fixedAsset}/edit', [FixedAssetController::class, 'edit'])->name('edit');
        Route::put('/{fixedAsset}', [FixedAssetController::class, 'update'])->name('update');
        Route::post('/{fixedAsset}/depreciate', [FixedAssetController::class, 'depreciate'])->name('depreciate');
        Route::post('/{fixedAsset}/dispose', [FixedAssetController::class, 'dispose'])->name('dispose');
    });

    // Fiscal Years & Periods
    Route::prefix('fiscal-years')->name('fiscal-years.')->group(function () {
        Route::get('/', [FiscalYearController::class, 'index'])->name('index');
        Route::get('/create', [FiscalYearController::class, 'create'])->name('create');
        Route::post('/', [FiscalYearController::class, 'store'])->name('store');
        Route::get('/{fiscalYear}', [FiscalYearController::class, 'show'])->name('show');
        Route::post('/{fiscalYear}/close', [FiscalYearController::class, 'closeFiscalYear'])->name('close');
        Route::post('/{fiscalYear}/reopen', [FiscalYearController::class, 'reopenFiscalYear'])->name('reopen');
        Route::post('/periods/{period}/close', [FiscalYearController::class, 'closePeriod'])->name('periods.close');
        Route::post('/periods/{period}/reopen', [FiscalYearController::class, 'reopenPeriod'])->name('periods.reopen');
    });

    // Audit Trail
    Route::prefix('audit-trail')->name('audit-trail.')->group(function () {
        Route::get('/', [AuditTrailController::class, 'index'])->name('index');
        Route::get('/{auditTrail}', [AuditTrailController::class, 'show'])->name('show');
    });
});
