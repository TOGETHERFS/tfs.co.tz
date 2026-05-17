<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

// Find Rehema's schedule with due date 2025-04-03
$schedules = \App\Models\LoanSchedule::with(['loan.client'])
    ->whereHas('loan.client', function($q) {
        $q->where('first_name', 'LIKE', '%Rehema%')
           ->where('last_name', 'LIKE', '%Ally%');
    })
    ->where('due_date', '2025-04-03')
    ->get();

echo "Searching for Rehema's installment with due date 2025-04-03...\n";
echo "Found " . $schedules->count() . " schedule(s)\n\n";

foreach($schedules as $schedule) {
    echo "Client: " . $schedule->loan->client->first_name . " " . $schedule->loan->client->last_name . "\n";
    echo "Loan Number: " . $schedule->loan->loan_number . "\n";
    echo "Installment: " . $schedule->installment_number . "\n";
    echo "Due Date: " . $schedule->due_date . "\n";
    echo "Current Status: " . $schedule->status . "\n";
    echo "Paid Amount: " . $schedule->paid_amount . "\n";
    echo "Total Amount: " . $schedule->total_amount . "\n";
    echo "Paid Date: " . ($schedule->paid_date ?? 'NULL') . "\n";
    echo "---\n";
    
    // Update to unpaid
    $schedule->status = 'pending';
    $schedule->paid_amount = 0;
    $schedule->paid_date = null;
    $schedule->payment_method = null;
    
    if ($schedule->save()) {
        echo "UPDATED: Marked as unpaid\n";
        echo "New Status: " . $schedule->fresh()->status . "\n";
        echo "New Paid Amount: " . $schedule->fresh()->paid_amount . "\n";
        echo "New Paid Date: " . ($schedule->fresh()->paid_date ?? 'NULL') . "\n";
    } else {
        echo "ERROR: Failed to update\n";
    }
    echo "===\n\n";
}

echo "Done!\n";
