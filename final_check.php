<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FINAL DATA VERIFICATION ===\n\n";

// Totals
$tables = ['tenants','users','clients','loans','repayments','loan_schedules','branches','loan_products','plans'];
echo "Table totals:\n";
foreach ($tables as $t) {
    echo "  " . str_pad($t, 20) . ": " . DB::table($t)->count() . "\n";
}

// Clients per tenant
echo "\nClients per tenant:\n";
$rows = DB::select("SELECT l.tenant_id, t.name, COUNT(*) as cnt FROM clients l LEFT JOIN tenants t ON t.id=l.tenant_id GROUP BY l.tenant_id, t.name ORDER BY l.tenant_id");
foreach ($rows as $r) {
    echo "  tenant {$r->tenant_id} ({$r->name}): {$r->cnt}\n";
}

// Loans per tenant
echo "\nLoans per tenant:\n";
$rows = DB::select("SELECT l.tenant_id, t.name, COUNT(*) as cnt FROM loans l LEFT JOIN tenants t ON t.id=l.tenant_id GROUP BY l.tenant_id, t.name ORDER BY l.tenant_id");
foreach ($rows as $r) {
    echo "  tenant {$r->tenant_id} ({$r->name}): {$r->cnt}\n";
}

// Loan indexes
echo "\nLoans unique indexes:\n";
$indexes = DB::select("SHOW INDEX FROM loans WHERE Non_unique = 0 AND Key_name != 'PRIMARY'");
foreach ($indexes as $idx) {
    echo "  {$idx->Key_name}: {$idx->Column_name}\n";
}

// Site health
echo "\nHTTP checks:\n";
$urls = ['https://phidlms.co.tz/', 'https://phidlms.co.tz/login', 'https://phidlms.co.tz/dashboard'];
foreach ($urls as $url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 5]);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "  $url => HTTP $code\n";
}
