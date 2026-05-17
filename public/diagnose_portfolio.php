<?php
// This script should be placed in public directory to run via browser
// Save as: public/diagnose_portfolio.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\Repayment;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain');

echo "=== LOAN PORTFOLIO ISSUE DIAGNOSIS ===\n\n";

// Get a sample active loan
$loan = Loan::whereIn('status', ['disbursed', 'active'])->first();

if (!$loan) {
    echo "No active loans found. Creating test data...\n";
    
    // Create a test loan if none exists
    $client = \App\Models\Client::first();
    $product = \App\Models\LoanProduct::first();
    
    if ($client && $product) {
        $loan = Loan::create([
            'loan_number' => 'TEST001',
            'client_id' => $client->id,
            'product_id' => $product->id,
            'user_id' => 1,
            'principal' => 1000000,
            'interest_rate' => 10,
            'term' => 12,
            'processing_fee' => 10000,
            'total_amount' => 1120000, // Principal + Interest
            'monthly_payment' => 93333.33,
            'first_payment_date' => now()->addDays(30),
            'status' => 'active',
            'outstanding_balance' => 1120000
        ]);
        
        echo "Created test loan #{$loan->id}\n";
    } else {
        echo "No client or product found. Please create test data first.\n";
        exit(1);
    }
}

echo "Analyzing Loan #{$loan->id}:\n";
echo "  Loan Number: {$loan->loan_number}\n";
echo "  Principal: " . number_format($loan->principal, 2) . "\n";
echo "  Total Amount: " . number_format($loan->total_amount, 2) . "\n";
echo "  Outstanding Balance: " . number_format($loan->outstanding_balance, 2) . "\n";
echo "  Status: {$loan->status}\n\n";

// Check schedules
$schedules = $loan->schedules()->orderBy('installment_number')->get();
echo "Schedules ({$schedules->count()} found):\n";
foreach ($schedules as $schedule) {
    echo "  Installment #{$schedule->installment_number}: \n";
    echo "    Due Date: {$schedule->due_date}\n";
    echo "    Principal: " . number_format($schedule->principal_amount, 2) . "\n";
    echo "    Interest: " . number_format($schedule->interest_amount, 2) . "\n";
    echo "    Total: " . number_format($schedule->total_amount, 2) . "\n";
    echo "    Paid: " . number_format($schedule->paid_amount, 2) . "\n";
    echo "    Status: {$schedule->status}\n";
    echo "    ---\n";
}

// Check repayments
$repayments = $loan->repayments;
echo "\nRepayments ({$repayments->count()} found):\n";
$totalRepaid = 0;
foreach ($repayments as $repayment) {
    echo "  Payment on {$repayment->payment_date}: " . number_format($repayment->amount, 2) . "\n";
    $totalRepaid += $repayment->amount;
}
echo "  Total Repaid: " . number_format($totalRepaid, 2) . "\n\n";

// Calculate what dashboard should show
echo "=== DASHBOARD CALCULATION COMPARISON ===\n";

// Method 1: Using outstanding_balance (OLD METHOD - INCORRECT)
$method1 = $loan->outstanding_balance;
echo "Method 1 (outstanding_balance): " . number_format($method1, 2) . "\n";

// Method 2: Using total_amount - paid (CORRECT METHOD)
$method2 = max(0, $loan->total_amount - $totalRepaid);
echo "Method 2 (total_amount - paid): " . number_format($method2, 2) . "\n";

// Method 3: Using schedule totals (MOST ACCURATE)
$scheduleTotal = $schedules->sum('total_amount');
$schedulePaid = $schedules->sum('paid_amount');
$method3 = max(0, $scheduleTotal - $schedulePaid);
echo "Method 3 (schedule totals): " . number_format($method3, 2) . "\n";

echo "\n=== PORTFOLIO SUMMARY ===\n";
$activeLoans = Loan::whereIn('status', ['disbursed', 'active'])->get();

// OLD method (incorrect)
$oldPortfolio = $activeLoans->sum('outstanding_balance');
echo "OLD Portfolio Value (outstanding_balance): " . number_format($oldPortfolio, 2) . "\n";

// NEW method (correct)
$newPortfolio = $activeLoans->sum(function($loan) {
    $totalPaid = $loan->repayments()->sum('amount');
    return max(0, $loan->total_amount - $totalPaid);
});
echo "NEW Portfolio Value (total_amount - paid): " . number_format($newPortfolio, 2) . "\n";

$difference = $newPortfolio - $oldPortfolio;
echo "Difference: " . number_format($difference, 2) . " (" . round(($difference / max($oldPortfolio, 1)) * 100, 2) . "%)\n";

echo "\n=== RECOMMENDATION ===\n";
if (abs($difference) > 1000) {  // If difference is significant
    echo "❌ CRITICAL: Portfolio values are significantly different!\n";
    echo "   You should run the fix commands:\n";
    echo "   php artisan fix:loan-totals\n";
    echo "   php artisan regenerate:loan-schedules\n";
} else {
    echo "✅ Portfolio values are consistent\n";

    // Check if schedules have proper interest data
    $missingInterest = $schedules->filter(function($s) {
        return $s->interest_amount <= 0 && $s->total_amount > $s->principal_amount;
    })->count();
    
    if ($missingInterest > 0) {
        echo "⚠️  WARNING: {$missingInterest} schedules have missing interest data\n";
        echo "   Run: php artisan regenerate:loan-schedules\n";
    } else {
        echo "✅ All schedules have proper interest data\n";
    }
}

echo "\n=== DONE ===\n";

// Clean up test loan if created
if ($loan->loan_number === 'TEST001') {
    echo "\nCleaning up test data...\n";
    $loan->schedules()->delete();
    $loan->delete();
    echo "Test data cleaned up.\n";
}