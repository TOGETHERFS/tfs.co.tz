<?php
/**
 * Tenant Isolation Diagnostic Tool
 * Access via: https://phidlms.co.tz/diagnose-tenant.php
 */

// Bootstrap Laravel
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain');

echo "=== TENANT ISOLATION DIAGNOSTIC ===\n\n";

// Check if user is authenticated
if (!auth()->check()) {
    echo "❌ NOT AUTHENTICATED\n";
    echo "Please log in first, then access this page.\n";
    exit;
}

$user = auth()->user();

echo "✓ Authenticated User:\n";
echo "  - ID: {$user->id}\n";
echo "  - Name: {$user->name}\n";
echo "  - Email: {$user->email}\n";
echo "  - Role: {$user->role}\n";
echo "  - Tenant ID: {$user->tenant_id}\n\n";

// Check session
echo "✓ Session Data:\n";
echo "  - Session tenant_id: " . (session('tenant_id') ?? 'NOT SET') . "\n";
echo "  - Session ID: " . session()->getId() . "\n\n";

// Check tenant
if ($user->tenant_id) {
    $tenant = \App\Models\Tenant::find($user->tenant_id);
    if ($tenant) {
        echo "✓ Tenant Details:\n";
        echo "  - ID: {$tenant->id}\n";
        echo "  - Name: {$tenant->name}\n";
        echo "  - Subdomain: {$tenant->subdomain}\n";
        echo "  - Status: {$tenant->status}\n\n";
    } else {
        echo "❌ Tenant NOT FOUND (ID: {$user->tenant_id})\n\n";
    }
}

// Check loans visibility
echo "✓ Loan Query Test:\n";

// Direct query without scope
$allLoansCount = \App\Models\Loan::withoutGlobalScope('tenant')->count();
echo "  - Total loans in database (all tenants): {$allLoansCount}\n";

// Query with tenant scope (should use user's tenant_id)
$tenantLoansCount = \App\Models\Loan::count();
echo "  - Loans visible to this user (with scope): {$tenantLoansCount}\n";

// Manual query with user's tenant_id
$manualCount = \App\Models\Loan::withoutGlobalScope('tenant')
    ->where('tenant_id', $user->tenant_id)
    ->count();
echo "  - Loans for tenant_id={$user->tenant_id} (manual): {$manualCount}\n\n";

// Check if BaseModel fix is applied
echo "✓ BaseModel Scope Check:\n";
$reflection = new ReflectionClass(\App\Models\BaseModel::class);
$method = $reflection->getMethod('booted');
$source = file_get_contents($method->getFileName());
if (strpos($source, 'Always prioritize authenticated user') !== false) {
    echo "  - ✓ BaseModel fix is APPLIED (prioritizes auth user)\n";
} else {
    echo "  - ❌ BaseModel fix NOT APPLIED (old version)\n";
}

// Test actual loan retrieval
echo "\n✓ Sample Loans (first 5):\n";
$loans = \App\Models\Loan::with('client')->take(5)->get();
if ($loans->count() > 0) {
    foreach ($loans as $loan) {
        echo "  - Loan #{$loan->loan_number}: ";
        echo "Client: " . ($loan->client ? $loan->client->first_name . ' ' . $loan->client->last_name : 'N/A');
        echo ", Status: {$loan->status}";
        echo ", Tenant ID: {$loan->tenant_id}\n";
    }
} else {
    echo "  - No loans found\n";
}

// Check clients
echo "\n✓ Client Count:\n";
$clientsCount = \App\Models\Client::count();
echo "  - Clients visible to this user: {$clientsCount}\n";

// Git version check
echo "\n✓ Git Version:\n";
$gitHash = trim(shell_exec('cd ' . base_path() . ' && git rev-parse --short HEAD 2>&1'));
$gitBranch = trim(shell_exec('cd ' . base_path() . ' && git rev-parse --abbrev-ref HEAD 2>&1'));
echo "  - Branch: {$gitBranch}\n";
echo "  - Commit: {$gitHash}\n";

echo "\n=== END DIAGNOSTIC ===\n";
