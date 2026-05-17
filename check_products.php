<?php
require '/var/www/phidlms/vendor/autoload.php';
$app = require '/var/www/phidlms/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo 'LOAN PRODUCTS:' . PHP_EOL;
$products = DB::table('loan_products')->get(['id','name','repayment_type','interest_type','is_active']);
foreach ($products as $p) {
    echo $p->id . ' | ' . $p->name . ' | repayment_type=' . ($p->repayment_type ?? 'NULL') . ' | active=' . $p->is_active . PHP_EOL;
}

echo PHP_EOL . 'LOAN 482:' . PHP_EOL;
$loan = DB::table('loans')->where('id', 482)->first(['id','principal','term','interest_rate','product_id','repayment_schedule']);
if ($loan) {
    echo 'product_id=' . $loan->product_id . ' principal=' . $loan->principal . ' term=' . $loan->term . PHP_EOL;
    $prod = DB::table('loan_products')->where('id', $loan->product_id)->first(['id','name','repayment_type']);
    echo 'Product: ' . ($prod ? $prod->name . ' repayment_type=' . ($prod->repayment_type ?? 'NULL') : 'NOT FOUND') . PHP_EOL;
}

echo PHP_EOL . 'INTEREST-ONLY PRODUCTS:' . PHP_EOL;
$ioProducts = DB::table('loan_products')->where('repayment_type', 'interest_only')->get(['id','name']);
foreach ($ioProducts as $p) {
    echo $p->id . ' | ' . $p->name . PHP_EOL;
}

echo PHP_EOL . 'LOANS WITH INTEREST-ONLY PRODUCTS:' . PHP_EOL;
$ioIds = DB::table('loan_products')->where('repayment_type', 'interest_only')->pluck('id');
$loans = DB::table('loans')->whereIn('product_id', $ioIds)->get(['id','product_id','principal','term']);
foreach ($loans as $l) {
    echo 'loan_id=' . $l->id . ' product_id=' . $l->product_id . ' principal=' . $l->principal . PHP_EOL;
}
