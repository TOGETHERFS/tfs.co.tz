<?php
require '/var/www/phidlms/vendor/autoload.php';
$app = require '/var/www/phidlms/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo 'Updating Agricultural Loan and Special Loan products to interest_only...' . PHP_EOL;

// Update ALL products named "Agricultural Loan" or "Special Loan" to interest_only
$updated = DB::table('loan_products')
    ->whereIn('name', ['Agricultural Loan', 'Agriculture Loan', 'Special Loan'])
    ->where('repayment_type', '!=', 'interest_only')
    ->update(['repayment_type' => 'interest_only']);

echo "Updated {$updated} loan products to interest_only." . PHP_EOL;

// Verify
$products = DB::table('loan_products')
    ->whereIn('name', ['Agricultural Loan', 'Agriculture Loan', 'Special Loan'])
    ->get(['id', 'name', 'repayment_type', 'is_active']);

echo PHP_EOL . 'Result:' . PHP_EOL;
foreach ($products as $p) {
    echo "  id={$p->id} | {$p->name} | repayment_type={$p->repayment_type} | active={$p->is_active}" . PHP_EOL;
}

// Now find all loans using these products and show counts
$allProductIds = DB::table('loan_products')
    ->whereIn('name', ['Agricultural Loan', 'Agriculture Loan', 'Special Loan'])
    ->pluck('id');

$loanCount = DB::table('loans')->whereIn('product_id', $allProductIds)->count();
echo PHP_EOL . "Total loans linked to these products: {$loanCount}" . PHP_EOL;
echo "Run: php artisan loans:regenerate-schedules --interest-only" . PHP_EOL;
echo "     to fix their repayment schedules." . PHP_EOL;
