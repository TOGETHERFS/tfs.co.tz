<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Loan;
use App\Models\Repayment;
use Illuminate\Support\Facades\DB;

// Check for a specific tenant - get all tenants first
$tenants = DB::table('tenants')->select('id','name')->get();

foreach ($tenants as $tenant) {
    $tid = $tenant->id;

    // Active loan IDs for this tenant
    $activeLoanIds = DB::table('loans')
        ->where('tenant_id', $tid)
        ->whereIn('status', ['disbursed', 'active'])
        ->pluck('id');

    $allLoanIds = DB::table('loans')
        ->where('tenant_id', $tid)
        ->pluck('id');

    $totalAmount = DB::table('loans')
        ->where('tenant_id', $tid)
        ->whereIn('status', ['disbursed', 'active'])
        ->sum('total_amount');

    $totalPrincipal = DB::table('loans')
        ->where('tenant_id', $tid)
        ->whereIn('status', ['disbursed', 'active'])
        ->sum('principal');

    $totalRepaidActive = DB::table('repayments')
        ->whereIn('loan_id', $activeLoanIds)
        ->sum('amount');

    $totalRepaidAll = DB::table('repayments')
        ->whereIn('loan_id', $allLoanIds)
        ->sum('amount');

    $portfolioValue = $totalAmount - $totalRepaidActive;
    $outstanding    = $portfolioValue;

    $loanCount      = DB::table('loans')->where('tenant_id', $tid)->count();
    $activeCount    = DB::table('loans')->where('tenant_id', $tid)->whereIn('status', ['disbursed','active'])->count();
    $clientCount    = DB::table('clients')->where('tenant_id', $tid)->count();

    echo "=== Tenant {$tid}: {$tenant->name} ===\n";
    echo "  Clients: {$clientCount}\n";
    echo "  Total Loans: {$loanCount}  |  Active/Disbursed: {$activeCount}\n";
    echo "  SUM principal (active): " . number_format($totalPrincipal, 2) . "\n";
    echo "  SUM total_amount (active): " . number_format($totalAmount, 2) . "\n";
    echo "  SUM repayments on active loans: " . number_format($totalRepaidActive, 2) . "\n";
    echo "  SUM repayments on ALL loans: " . number_format($totalRepaidAll, 2) . "\n";
    echo "  Portfolio Value (total_amount - repaid): " . number_format($portfolioValue, 2) . "\n";
    echo "  Outstanding: " . number_format($outstanding, 2) . "\n";
    echo "\n";
}
