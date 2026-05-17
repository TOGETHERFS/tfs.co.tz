<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "=== KWARE MICROFINANCE Data Deletion ===\n\n";

// Find KWARE MICROFINANCE tenant
$tenant = \Illuminate\Support\Facades\DB::table('tenants')
    ->where('name', 'LIKE', '%KWARE%')
    ->orWhere('name', 'LIKE', '%MICROFINANCE%')
    ->first();

if (!$tenant) {
    echo "❌ KWARE MICROFINANCE tenant not found!\n";
    exit(1);
}

echo "Found KWARE MICROFINANCE tenant:\n";
echo "ID: {$tenant->id}\n";
echo "Name: {$tenant->name}\n";
echo "Slug: {$tenant->slug}\n";
echo "Email: {$tenant->email}\n";
echo "Status: {$tenant->status}\n";
echo "Created: {$tenant->created_at}\n\n";

$tenantId = $tenant->id;
$deletedCount = 0;

try {
    \Illuminate\Support\Facades\DB::beginTransaction();

    echo "Starting deletion process...\n\n";

    // 1. Delete repayments
    $repayments = \Illuminate\Support\Facades\DB::table('repayments')
        ->join('loans', 'repayments.loan_id', '=', 'loans.id')
        ->where('loans.tenant_id', $tenantId)
        ->count();
    \Illuminate\Support\Facades\DB::table('repayments')
        ->join('loans', 'repayments.loan_id', '=', 'loans.id')
        ->where('loans.tenant_id', $tenantId)
        ->delete();
    $deletedCount += $repayments;
    echo "✅ Deleted {$repayments} repayments\n";

    // 2. Delete loan schedules
    $schedules = \Illuminate\Support\Facades\DB::table('loan_schedules')
        ->join('loans', 'loan_schedules.loan_id', '=', 'loans.id')
        ->where('loans.tenant_id', $tenantId)
        ->count();
    \Illuminate\Support\Facades\DB::table('loan_schedules')
        ->join('loans', 'loan_schedules.loan_id', '=', 'loans.id')
        ->where('loans.tenant_id', $tenantId)
        ->delete();
    $deletedCount += $schedules;
    echo "✅ Deleted {$schedules} loan schedules\n";

    // 3. Delete loans
    $loans = \Illuminate\Support\Facades\DB::table('loans')
        ->where('tenant_id', $tenantId)
        ->count();
    \Illuminate\Support\Facades\DB::table('loans')
        ->where('tenant_id', $tenantId)
        ->delete();
    $deletedCount += $loans;
    echo "✅ Deleted {$loans} loans\n";

    // 4. Delete clients
    $clients = \Illuminate\Support\Facades\DB::table('clients')
        ->where('tenant_id', $tenantId)
        ->count();
    \Illuminate\Support\Facades\DB::table('clients')
        ->where('tenant_id', $tenantId)
        ->delete();
    $deletedCount += $clients;
    echo "✅ Deleted {$clients} clients\n";

    // 5. Delete users (excluding super admin)
    $users = \Illuminate\Support\Facades\DB::table('users')
        ->where('tenant_id', $tenantId)
        ->where('email', '!=', 'phidtechnology@gmail.com')
        ->count();
    \Illuminate\Support\Facades\DB::table('users')
        ->where('tenant_id', $tenantId)
        ->where('email', '!=', 'phidtechnology@gmail.com')
        ->delete();
    $deletedCount += $users;
    echo "✅ Deleted {$users} users\n";

    // 6. Delete branches
    $branches = \Illuminate\Support\Facades\DB::table('branches')
        ->where('tenant_id', $tenantId)
        ->count();
    \Illuminate\Support\Facades\DB::table('branches')
        ->where('tenant_id', $tenantId)
        ->delete();
    $deletedCount += $branches;
    echo "✅ Deleted {$branches} branches\n";

    // 7. Delete tenant roles
    $roles = \Illuminate\Support\Facades\DB::table('roles')
        ->where('tenant_id', $tenantId)
        ->count();
    \Illuminate\Support\Facades\DB::table('roles')
        ->where('tenant_id', $tenantId)
        ->delete();
    $deletedCount += $roles;
    echo "✅ Deleted {$roles} roles\n";

    // 8. Delete permissions
    $permissions = \Illuminate\Support\Facades\DB::table('permissions')
        ->where('tenant_id', $tenantId)
        ->count();
    \Illuminate\Support\Facades\DB::table('permissions')
        ->where('tenant_id', $tenantId)
        ->delete();
    $deletedCount += $permissions;
    echo "✅ Deleted {$permissions} permissions\n";

    // 9. Delete subscriptions
    $subscriptions = \Illuminate\Support\Facades\DB::table('subscriptions')
        ->where('tenant_id', $tenantId)
        ->count();
    \Illuminate\Support\Facades\DB::table('subscriptions')
        ->where('tenant_id', $tenantId)
        ->delete();
    $deletedCount += $subscriptions;
    echo "✅ Deleted {$subscriptions} subscriptions\n";

    // 10. Delete the tenant itself
    \Illuminate\Support\Facades\DB::table('tenants')
        ->where('id', $tenantId)
        ->delete();
    $deletedCount += 1;
    echo "✅ Deleted tenant record\n";

    \Illuminate\Support\Facades\DB::commit();

    echo "\n=== DELETION COMPLETED ===\n";
    echo "✅ KWARE MICROFINANCE data deletion completed successfully!\n";
    echo "📊 Total records deleted: {$deletedCount}\n";
    echo "🏢 Tenant ID: {$tenantId} has been completely removed from the system.\n";

    // Log the deletion
    \Illuminate\Support\Facades\Log::warning('KWARE MICROFINANCE data deleted', [
        'tenant_id' => $tenantId,
        'tenant_name' => $tenant->name,
        'deleted_records' => $deletedCount,
        'deleted_by' => 'console_script',
        'deleted_at' => now(),
    ]);

} catch (Exception $e) {
    \Illuminate\Support\Facades\DB::rollBack();
    echo "\n❌ Error during deletion: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    \Illuminate\Support\Facades\Log::error('KWARE MICROFINANCE deletion failed', [
        'tenant_id' => $tenantId,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    exit(1);
}

echo "\nScript finished.\n";
