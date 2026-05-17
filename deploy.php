<?php
/**
 * PHID Microfinance Production Deployment Script
 * 
 * This script automates the deployment process for production environment
 * Run this script after uploading files to your production server
 */

echo "🚀 PHID Microfinance Production Deployment Script\n";
echo "================================================\n\n";

// Check if running in CLI
if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line\n");
}

// Configuration
$commands = [
    'Clear all caches' => [
        'php artisan config:clear',
        'php artisan route:clear',
        'php artisan view:clear',
        'php artisan cache:clear',
        'php artisan event:clear'
    ],
    'Install/Update dependencies' => [
        'composer install --no-dev --optimize-autoloader'
    ],
    'Generate application key' => [
        'php artisan key:generate --force'
    ],
    'Run database migrations' => [
        'php artisan migrate --force'
    ],
    'Seed database (if needed)' => [
        'php artisan db:seed --force'
    ],
    'Cache configurations for production' => [
        'php artisan config:cache',
        'php artisan route:cache',
        'php artisan view:cache',
        'php artisan event:cache'
    ],
    'Set proper permissions' => [
        'chmod -R 755 storage',
        'chmod -R 755 bootstrap/cache'
    ],
    'Create symbolic link for storage' => [
        'php artisan storage:link'
    ]
];

// Execute deployment commands
foreach ($commands as $step => $stepCommands) {
    echo "📋 $step\n";
    echo str_repeat('-', strlen($step) + 4) . "\n";
    
    foreach ($stepCommands as $command) {
        echo "   Executing: $command\n";
        
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode === 0) {
            echo "   ✅ Success\n";
        } else {
            echo "   ❌ Failed: " . implode("\n", $output) . "\n";
        }
    }
    echo "\n";
}

echo "🎉 Deployment completed!\n\n";

echo "📝 Post-deployment checklist:\n";
echo "=============================\n";
echo "1. ✅ Update .env file with production credentials\n";
echo "2. ✅ Configure web server (Apache/Nginx)\n";
echo "3. ✅ Set up SSL certificate\n";
echo "4. ✅ Configure database backup\n";
echo "5. ✅ Set up monitoring and logging\n";
echo "6. ✅ Test all critical functionality\n";
echo "7. ✅ Configure payment gateway credentials\n";
echo "8. ✅ Set up SMS service credentials\n\n";

echo "🔗 Important URLs to test:\n";
echo "- Homepage: https://yourdomain.com\n";
echo "- Login: https://yourdomain.com/login\n";
echo "- Registration: https://yourdomain.com/tenant/register\n";
echo "- Dashboard: https://yourdomain.com/user/dashboard\n";
echo "- Payment webhook: https://yourdomain.com/webhooks/selcom/repayments\n\n";

echo "⚠️  Security reminders:\n";
echo "- Ensure APP_DEBUG=false in production\n";
echo "- Use strong database passwords\n";
echo "- Enable HTTPS only\n";
echo "- Regular security updates\n";
echo "- Monitor application logs\n\n";

echo "✨ Deployment script completed successfully!\n";
?>