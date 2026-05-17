<?php
require '/var/www/phidlms/vendor/autoload.php';
$app = require '/var/www/phidlms/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

$pdo = $app->make('db')->getPdo();

// Find user by email
$stmt = $pdo->prepare("SELECT id, name, email, role, position, admin_role_id, tenant_id FROM users WHERE email = 'mkuyumicrofinanceltd@gmail.com' LIMIT 1");
$stmt->execute();
$u = $stmt->fetch(PDO::FETCH_OBJ);

if ($u) {
    echo "Found user:\n";
    echo "  id: " . $u->id . "\n";
    echo "  name: " . $u->name . "\n";
    echo "  email: " . $u->email . "\n";
    echo "  role: " . $u->role . "\n";
    echo "  position: " . $u->position . "\n";
    echo "  admin_role_id: " . $u->admin_role_id . "\n";
    echo "  tenant_id: " . $u->tenant_id . "\n";
} else {
    echo "User not found by that email.\n";
    echo "Listing all users with tenant_id set:\n";
    $stmt2 = $pdo->query("SELECT id, name, email, role, position, admin_role_id, tenant_id FROM users WHERE tenant_id IS NOT NULL ORDER BY id LIMIT 20");
    foreach ($stmt2->fetchAll(PDO::FETCH_OBJ) as $row) {
        echo "  " . $row->id . " | " . $row->name . " | " . $row->email . " | role=" . $row->role . " | pos=" . $row->position . " | tenant=" . $row->tenant_id . "\n";
    }
}
