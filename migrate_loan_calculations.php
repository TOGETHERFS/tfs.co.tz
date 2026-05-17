<?php
/**
 * Data Migration Script
 * Run this script to regenerate all loan schedules with the corrected calculation logic
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Http\Controllers\LoanController;

echo "Starting loan schedule regeneration with corrected calculations...\n\n";

// Count total loans
$totalLoans = Loan::count();
echo "Total loans to process: {$totalLoans}\n\n";

$processed = 0;
$errors = 0;

$loans = Loan::with('product')->get();

foreach ($loans as $loan) {
    try {
        echo "Processing loan #{$loan->id} ({$loan->loan_number})...\n";
        
        // Store original values for comparison
        $originalPrincipal = $loan->principal;
        $originalInterestRate = $loan->interest_rate;
        $originalTerm = $loan->term;
        $originalSchedule = $loan->repayment_schedule;
        $originalInterestType = optional($loan->product)->interest_type ?? 'flat';
        
        echo "  - Principal: " . number_format($originalPrincipal) . "\n";
        echo "  - Interest Rate: {$originalInterestRate}% monthly\n";
        echo "  - Term: {$originalTerm} {$originalSchedule}\n";
        echo "  - Interest Type: {$originalInterestType}\n";
        
        // Delete existing schedule
        $deletedCount = LoanSchedule::where('loan_id', $loan->id)->count();
        LoanSchedule::where('loan_id', $loan->id)->delete();
        echo "  - Deleted {$deletedCount} existing schedule items\n";
        
        // Regenerate schedule using corrected logic
        $controller = new LoanController();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('generateLoanSchedule');
        $method->setAccessible(true);
        $method->invoke($controller, $loan);
        
        // Verify the new schedule
        $newScheduleCount = $loan->schedules()->count();
        $totalInterest = $loan->schedules()->sum('interest_amount');
        $totalPrincipal = $loan->schedules()->sum('principal_amount');
        $totalScheduled = $loan->schedules()->sum('total_amount');
        
        echo "  - Generated {$newScheduleCount} new schedule items\n";
        echo "  - Total Interest in schedule: " . number_format($totalInterest, 2) . "\n";
        echo "  - Total Principal in schedule: " . number_format($totalPrincipal, 2) . "\n";
        echo "  - Total Scheduled Amount: " . number_format($totalScheduled, 2) . "\n";
        
        // Update the loan's total_amount to match the schedule
        $loan->update(['total_amount' => $totalScheduled]);
        echo "  - Updated loan total_amount to: " . number_format($totalScheduled, 2) . "\n";
        
        echo "  - SUCCESS: Schedule regenerated with correct calculations\n\n";
        $processed++;
        
    } catch (Exception $e) {
        echo "  - ERROR processing loan {$loan->id}: " . $e->getMessage() . "\n\n";
        $errors++;
    }
}

echo "==========================================\n";
echo "Migration Summary:\n";
echo "  - Total loans processed: {$processed}\n";
echo "  - Errors encountered: {$errors}\n";
echo "  - Expected to complete: " . ($processed + $errors) . "/" . $totalLoans . "\n";
echo "==========================================\n\n";

echo "Data migration completed. Loan schedules now use the corrected calculation logic.\n";
echo "Portfolio dashboard should now show accurate totals reflecting proper interest calculations.\n";