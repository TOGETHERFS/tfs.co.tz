<?php
/**
 * Force reload all PHP files by touching them
 * Access via: https://phidlms.co.tz/force-reload.php
 */

header('Content-Type: text/plain');

echo "=== FORCE RELOAD PHP FILES ===\n\n";

$basePath = dirname(__DIR__);

// Touch critical files to force OPcache reload
$filesToTouch = [
    'app/Models/BaseModel.php',
    'app/Http/Middleware/TenantMiddleware.php',
    'app/Http/Controllers/DashboardController.php',
    'app/Http/Controllers/UserDashboardController.php',
    'app/Http/Controllers/LoanController.php',
    'app/Http/Controllers/Api/LoanController.php',
];

foreach ($filesToTouch as $file) {
    $fullPath = $basePath . '/' . $file;
    if (file_exists($fullPath)) {
        touch($fullPath);
        echo "✓ Touched: $file\n";
    } else {
        echo "✗ Not found: $file\n";
    }
}

echo "\n";

// Try to clear OPcache
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "✓ OPcache cleared successfully!\n";
    } else {
        echo "✗ Failed to clear OPcache\n";
    }
} else {
    echo "ℹ️  OPcache function not available\n";
}

// Clear Laravel caches
echo "\nClearing Laravel caches...\n";
$commands = [
    'php artisan cache:clear',
    'php artisan view:clear',
    'php artisan config:clear',
];

foreach ($commands as $cmd) {
    $output = [];
    $returnVar = 0;
    exec("cd $basePath && $cmd 2>&1", $output, $returnVar);
    if ($returnVar === 0) {
        echo "✓ $cmd\n";
    } else {
        echo "✗ $cmd failed\n";
    }
}

echo "\n=== DONE ===\n";
echo "All users should now log out and log back in.\n";
echo "Wait 30 seconds before testing.\n";
