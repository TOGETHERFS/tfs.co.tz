<?php
// Script to regenerate loan schedules with the updated calculation logic

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Http\Controllers\LoanController;

// Get all loans with reducing balance interest type
$loans = Loan::with('product')
    ->whereHas('product', function($query) {
        $query->where('interest_type', 'reducing');
    })
    ->orWhere('interest_type', 'reducing')
    ->get();

echo "Found " . count($loans) . " loans with reducing balance interest type\n";

foreach ($loans as $index => $loan) {
    echo ($index + 1) . ". Regenerating schedule for loan ID: {$loan->id} ({$loan->loan_number})\n";
    
    try {
        // Delete existing schedules
        LoanSchedule::where('loan_id', $loan->id)->delete();
        echo "   - Deleted existing schedules\n";
        
        // Regenerate schedule using updated logic
        $controller = new LoanController();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('generateLoanSchedule');
        $method->setAccessible(true);
        $method->invoke($controller, $loan);
        
        echo "   - Successfully regenerated schedule\n";
        
        // Verify the schedule
        $scheduleCount = $loan->schedules()->count();
        $totalScheduled = $loan->schedules()->sum('total_amount');
        echo "   - Schedule items: {$scheduleCount}, Total scheduled: " . number_format($totalScheduled, 2) . "\n";
        
    } catch (Exception $e) {
        echo "   - ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Also update loans with flat interest to ensure they're correct too
$flatLoans = Loan::with('product')
    ->whereHas('product', function($query) {
        $query->where('interest_type', 'flat');
    })
    ->orWhere('interest_type', 'flat')
    ->get();

echo "Found " . count($flatLoans) . " loans with flat interest type to verify\n";

foreach ($flatLoans as $index => $loan) {
    echo ($index + 1) . ". Verifying schedule for flat interest loan ID: {$loan->id} ({$loan->loan_number})\n";
    
    try {
        // Delete and regenerate flat interest schedules too
        LoanSchedule::where('loan_id', $loan->id)->delete();
        
        $controller = new LoanController();
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('generateLoanSchedule');
        $method->setAccessible(true);
        $method->invoke($controller, $loan);
        
        echo "   - Successfully regenerated flat interest schedule\n";
    } catch (Exception $e) {
        echo "   - ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "Schedule regeneration completed!\n";