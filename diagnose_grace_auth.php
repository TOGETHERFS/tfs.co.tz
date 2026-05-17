<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

echo "=== Comprehensive Authentication Diagnostic ===\n\n";

// Try all possible email variations
$emails = [
    'gracemichaelkisinga@yahoo.com',
    'gracemichaelkiinga@yahoo.com',
    'GraceMichaelKisinga@yahoo.com',
    'GRACEMICHAELKISINGA@YAHOO.COM'
];

$user = null;
$actualEmail = null;

foreach ($emails as $email) {
    $found = User::where('email', $email)->first();
    if ($found) {
        $user = $found;
        $actualEmail = $email;
        echo "✅ User found with email: {$email}\n";
        break;
    }
    
    // Try case-insensitive search
    $found = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();
    if ($found) {
        $user = $found;
        $actualEmail = $found->email;
        echo "✅ User found with case-insensitive search: {$found->email}\n";
        break;
    }
}

if (!$user) {
    echo "❌ User not found with any email variation\n";
    echo "\nSearching for similar emails...\n";
    $similar = User::where('email', 'LIKE', '%grace%')->get();
    foreach ($similar as $s) {
        echo "  - {$s->email} (ID: {$s->id})\n";
    }
    exit(1);
}

echo "\n=== User Details ===\n";
echo "ID: {$user->id}\n";
echo "Name: {$user->name}\n";
echo "Email (stored): {$user->email}\n";
echo "Role: {$user->role}\n";
echo "Tenant ID: " . ($user->tenant_id ?? 'NULL') . "\n";
echo "Email Verified: " . ($user->email_verified_at ? 'Yes' : 'No') . "\n";
echo "Created: {$user->created_at}\n";
echo "Updated: {$user->updated_at}\n\n";

// Check password
if (empty($user->password)) {
    echo "❌ CRITICAL: Password is empty!\n\n";
} else {
    echo "✅ Password hash exists\n";
    echo "   Length: " . strlen($user->password) . " chars\n";
    echo "   Type: " . (str_starts_with($user->password, '$2y$') ? 'bcrypt' : 'unknown') . "\n\n";
}

// Check tenant
if (!$user->tenant_id) {
    echo "❌ CRITICAL: No tenant_id assigned\n\n";
} else {
    $tenant = Tenant::find($user->tenant_id);
    if (!$tenant) {
        echo "❌ CRITICAL: Tenant {$user->tenant_id} does not exist!\n\n";
    } else {
        echo "=== Tenant Details ===\n";
        echo "Name: {$tenant->name}\n";
        echo "Slug: {$tenant->slug}\n";
        echo "Status: {$tenant->status}\n";
        echo "Contact Email: {$tenant->contact_email}\n";
        
        if ($tenant->status !== 'active') {
            echo "❌ WARNING: Tenant is not active!\n";
        } else {
            echo "✅ Tenant is active\n";
        }
        echo "\n";
    }
}

// Check for authentication blockers
echo "=== Authentication Checks ===\n";

// Test password verification with common passwords
$testPasswords = ['password', 'Password123', 'Grace@2026', '12345678'];
$passwordWorks = false;
foreach ($testPasswords as $pwd) {
    if (Hash::check($pwd, $user->password)) {
        echo "✅ Current password is: {$pwd}\n";
        $passwordWorks = true;
        break;
    }
}

if (!$passwordWorks) {
    echo "⚠️  Current password is not one of the common test passwords\n";
}

// Check if email has special characters or encoding issues
$emailBytes = unpack('C*', $user->email);
$hasSpecialChars = false;
foreach ($emailBytes as $byte) {
    if ($byte > 127) {
        $hasSpecialChars = true;
        break;
    }
}

if ($hasSpecialChars) {
    echo "❌ WARNING: Email contains non-ASCII characters!\n";
    echo "   This could cause authentication issues\n";
} else {
    echo "✅ Email contains only ASCII characters\n";
}

// Check session configuration
echo "\n=== Session Configuration ===\n";
echo "Driver: " . config('session.driver') . "\n";
echo "Lifetime: " . config('session.lifetime') . " minutes\n";
echo "Encrypt: " . (config('session.encrypt') ? 'Yes' : 'No') . "\n";

// Check database connection
echo "\n=== Database Check ===\n";
try {
    $userCount = User::count();
    echo "✅ Database connected (Total users: {$userCount})\n";
} catch (\Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Simulate authentication
echo "\n=== Simulating Authentication ===\n";
$testPassword = 'Grace@2026';
echo "Testing with password: {$testPassword}\n";

// Direct password check
if (Hash::check($testPassword, $user->password)) {
    echo "✅ Password verification: SUCCESS\n";
} else {
    echo "❌ Password verification: FAILED\n";
    echo "   The password '{$testPassword}' does not match the stored hash\n";
}

// Check if user can be found by email (case-sensitive)
$foundByEmail = User::where('email', $user->email)->first();
if ($foundByEmail) {
    echo "✅ User can be found by exact email match\n";
} else {
    echo "❌ User cannot be found by exact email match\n";
}

echo "\n=== RECOMMENDATIONS ===\n";

$issues = [];
if (empty($user->password)) $issues[] = "Reset password";
if (!$user->tenant_id) $issues[] = "Assign tenant";
if ($user->tenant_id && (!$tenant || $tenant->status !== 'active')) $issues[] = "Activate tenant";
if (!$user->email_verified_at) $issues[] = "Verify email";
if ($hasSpecialChars) $issues[] = "Fix email encoding";

if (empty($issues)) {
    echo "✅ No obvious issues found\n";
    echo "\nThe problem might be:\n";
    echo "1. Browser cache/cookies - User should clear browser data\n";
    echo "2. Session issues - Check session storage on server\n";
    echo "3. Middleware blocking - Check authentication middleware\n";
    echo "4. Password mismatch - User entering wrong password\n";
} else {
    echo "Issues to fix:\n";
    foreach ($issues as $i => $issue) {
        echo ($i + 1) . ". {$issue}\n";
    }
}

echo "\n=== SOLUTION ===\n";
echo "To reset password and fix all issues, run:\n";
echo "php fix_grace_complete.php\n\n";
