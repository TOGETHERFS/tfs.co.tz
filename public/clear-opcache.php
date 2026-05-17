<?php
/**
 * Clear OPcache
 * Access via: https://phidlms.co.tz/clear-opcache.php
 */

header('Content-Type: text/plain');

echo "=== OPCACHE RESET ===\n\n";

if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "✓ OPcache has been cleared successfully!\n";
        echo "✓ The new BaseModel code will now be used.\n\n";
        echo "Next steps:\n";
        echo "1. Close all browser windows\n";
        echo "2. Open new incognito window\n";
        echo "3. Log in as KWARE user\n";
        echo "4. Check if loans appear\n";
    } else {
        echo "❌ Failed to clear OPcache\n";
        echo "You may need to restart the web server manually.\n";
    }
} else {
    echo "ℹ️  OPcache is not enabled or not available\n";
    echo "The code should work without restart.\n";
}

echo "\n=== END ===\n";
