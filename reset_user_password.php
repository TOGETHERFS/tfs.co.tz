<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$email = 'gracemichaelkiinga@yahoo.com';
$newPassword = 'Grace@2026'; // Temporary password - user should change after login

echo "=== Password Reset Tool ===\n\n";

$user = User::where('email', $email)->first();

if (!$user) {
    echo "❌ ERROR: User with email '{$email}' not found.\n";
    echo "   Please verify the email address is correct.\n\n";
    exit(1);
}

echo "Found user: {$user->name} (ID: {$user->id})\n";
echo "Resetting password...\n\n";

$user->password = Hash::make($newPassword);
$user->save();

echo "✅ Password reset successful!\n\n";
echo "=== Login Credentials ===\n";
echo "Email: {$email}\n";
echo "Password: {$newPassword}\n\n";
echo "⚠️  IMPORTANT: User should change this password after logging in.\n";
echo "   Go to Profile > Change Password after login.\n\n";
