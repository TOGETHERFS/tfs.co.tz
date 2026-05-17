#!/usr/bin/env python3
import subprocess, re

script = """
<?php
define('LARAVEL_START', microtime(true));
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tables = ['loans', 'users', 'clients', 'branches', 'repayments', 'loan_schedules'];
foreach ($tables as $t) {
    $cols = array_column(DB::select("DESCRIBE $t"), 'Field');
    echo $t . ': ' . implode(', ', $cols) . "\n";
}
"""

with open('/tmp/check_schema.php', 'w') as f:
    f.write(script)

result = subprocess.run(['php', '/tmp/check_schema.php'], capture_output=True, text=True, cwd='/var/www/phidlms')
print(result.stdout)
print(result.stderr[:500] if result.stderr else '')
