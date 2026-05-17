<?php

use App\Models\Loan;

$loans = Loan::whereIn('status', ['disbursed', 'active'])->with('product')->get();

foreach ($loans as $loan) {
    try {
        $loan->schedules()->delete();
        $loan->generateSchedule();
        echo 'Updated: ' . $loan->loan_number . PHP_EOL;
    } catch (Exception $e) {
        echo 'Failed: ' . $loan->loan_number . ' - ' . $e->getMessage() . PHP_EOL;
    }
}

echo 'Done. Total loans processed: ' . $loans->count() . PHP_EOL;
