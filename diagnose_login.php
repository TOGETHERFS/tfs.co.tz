<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;

$email = 'gracemichaelkisinga@yahoo.com'; // Note: corrected spelling
$testPassword = 'test123'; // We'll test with this

echo "=== Login Diagnostic for {$email} ===\n\n";

// Find user
$user = User::where('email', $email)->first();

if (!$user) {
    // Try alternate spelling
    $email = 'gracemichaelkiinga@yahoo.com';
    $user = User::where('email', $email)->first();
    
    if (!$user) {
        echo "❌ User not found with either spelling:\n";
        echo "   - gracemichaelkisinga@yahoo.com\n";
        echo "   - gracemichaelkiinga@yahoo.com\n\n";
        
        // Search for similar emails
        $similar = User::where('email', 'LIKE', '%gracemichael%')->get();
        if ($similar->count() > 0) {
            echo "Found similar emails:\n";
            foreach ($similar as $s) {
                echo "   - {$s->email} (ID: {$s->id}, Name: {$s->name})\n";
            }
        }
        exit(1);
    }
}

echo "✅ User found: {$user->name}\n";
echo "   Email: {$user->email}\n";
echo "   ID: {$user->id}\n";
echo "   Role: {$user->role}\n";
echo "   Tenant ID: " . ($user->tenant_id ?? 'NULL') . "\n";
echo "   Created: {$user->created_at}\n\n";

// Check password hash
echo "=== Password Check ===\n";
if (empty($user->password)) {
    echo "❌ CRITICAL: Password hash is EMPTY!\n\n";
    exit(1);
}

echo "Password hash exists: " . substr($user->password, 0, 20) . "...\n";
echo "Hash length: " . strlen($user->password) . " characters\n";
echo "Hash algorithm: " . (str_starts_with($user->password, '$2y$') ? 'bcrypt' : 'unknown') . "\n\n";

// Test password verification
echo "=== Testing Password Verification ===\n";
$passwords = ['test123', 'Test123', 'Grace@2026', 'password', '12345678'];

foreach ($passwords as $pwd) {
    $matches = Hash::check($pwd, $user->password);
    echo ($matches ? "✅" : "❌") . " Password '{$pwd}': " . ($matches ? "MATCHES" : "does not match") . "\n";
}
echo "\n";

// Check tenant
echo "=== Tenant Check ===\n";
if (!$user->tenant_id) {
    echo "❌ CRITICAL: User has NO tenant_id!\n";
    echo "   This will prevent login.\n\n";
} else {
    $tenant = Tenant::find($user->tenant_id);
    if (!$tenant) {
        echo "❌ CRITICAL: Tenant ID {$user->tenant_id} does NOT exist!\n\n";
    } else {
        echo "✅ Tenant exists: {$tenant->name}\n";
        echo "   Status: {$tenant->status}\n";
        echo "   Slug: {$tenant->slug}\n";
        
        if ($tenant->status !== 'active') {
            echo "   ⚠️  WARNING: Tenant is not active!\n";
        }
        echo "\n";
    }
}

// Check database connection
echo "=== Database Check ===\n";
try {
    $count = User::count();
    echo "✅ Database connection OK (Total users: {$count})\n\n";
} catch (\Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n\n";
}

// Simulate Auth::attempt
echo "=== Simulating Auth::attempt ===\n";
$credentials = ['email' => $user->email, 'password' => 'test123'];
echo "Credentials: " . json_encode($credentials) . "\n";

// Manual check
$dbUser = User::where('email', $credentials['email'])->first();
if ($dbUser && Hash::check($credentials['password'], $dbUser->password)) {
    echo "✅ Manual authentication would SUCCEED\n\n";
} else {
    echo "❌ Manual authentication would FAIL\n";
    if (!$dbUser) {
        echo "   Reason: User not found\n\n";
    } else {
        echo "   Reason: Password does not match\n\n";
    }
}

echo "=== SOLUTION ===\n";
echo "To reset password to 'Grace@2026', run:\n";
echo "php artisan tinker\n";
echo "Then execute:\n";
echo "\$user = User::where('email', '{$user->email}')->first();\n";
echo "\$user->password = Hash::make('Grace@2026');\n";
echo "\$user->save();\n";
echo "echo 'Password reset to: Grace@2026';\n\n";
