<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Custom microfinance commands
Artisan::command('microfinance:setup', function () {
    $this->info('Setting up microfinance application...');
    
    // Run migrations
    $this->call('migrate');
    
    // Seed database
    $this->call('db:seed');
    
    // Create storage links
    $this->call('storage:link');
    
    // Clear caches
    $this->call('config:clear');
    $this->call('cache:clear');
    $this->call('view:clear');
    
    $this->info('Microfinance application setup completed!');
})->purpose('Set up the microfinance application');

Artisan::command('microfinance:daily-tasks', function () {
    $this->info('Running daily microfinance tasks...');
    
    // Process overdue invoices
    $this->call('microfinance:process-overdue-invoices');
    
    // Send payment reminders
    $this->call('microfinance:send-payment-reminders', ['type' => 'due-today']);
    $this->call('microfinance:send-payment-reminders', ['type' => 'upcoming']);
    $this->call('microfinance:send-payment-reminders', ['type' => 'overdue']);
    
    // Clean up old notifications
    $this->call('microfinance:cleanup-notifications');
    
    $this->info('Daily tasks completed!');
})->purpose('Run daily microfinance maintenance tasks');

Artisan::command('microfinance:monthly-tasks', function () {
    $this->info('Running monthly microfinance tasks...');
    
    // Generate monthly invoices
    $this->call('microfinance:generate-invoices');
    
    // Generate monthly reports
    $this->info('Generating monthly reports...');
    
    $this->info('Monthly tasks completed!');
})->purpose('Run monthly microfinance maintenance tasks');

Artisan::command('microfinance:cleanup-notifications', function () {
    $this->info('Cleaning up old notifications...');
    
    $deleted = \DB::table('notifications')
        ->where('created_at', '<', now()->subDays(30))
        ->where('read_at', '!=', null)
        ->delete();
    
    $this->info("Deleted {$deleted} old read notifications.");
})->purpose('Clean up old read notifications');

Artisan::command('microfinance:backup', function () {
    $this->info('Creating application backup...');
    
    $timestamp = now()->format('Y-m-d_H-i-s');
    $backupPath = storage_path("backups/backup_{$timestamp}");
    
    // Create backup directory
    if (!file_exists(dirname($backupPath))) {
        mkdir(dirname($backupPath), 0755, true);
    }
    
    // Database backup
    $this->info('Backing up database...');
    $dbName = config('database.connections.mysql.database');
    $dbUser = config('database.connections.mysql.username');
    $dbPass = config('database.connections.mysql.password');
    $dbHost = config('database.connections.mysql.host');
    
    $command = "mysqldump -h {$dbHost} -u {$dbUser} -p{$dbPass} {$dbName} > {$backupPath}_database.sql";
    exec($command);
    
    // Files backup
    $this->info('Backing up files...');
    $command = "tar -czf {$backupPath}_files.tar.gz storage/app/public";
    exec($command);
    
    $this->info("Backup created: {$backupPath}");
})->purpose('Create a backup of the application');

Artisan::command('microfinance:test-notifications', function () {
    $this->info('Testing notification system...');
    
    $user = \App\Models\User::first();
    if (!$user) {
        $this->error('No users found. Please create a user first.');
        return;
    }
    
    // Test email notification
    try {
        $user->notify(new \App\Notifications\WelcomeClientNotification($user, 'test123'));
        $this->info('Email notification sent successfully.');
    } catch (\Exception $e) {
        $this->error('Email notification failed: ' . $e->getMessage());
    }
    
    // Test SMS notification
    try {
        $smsService = app(\App\Services\BeemSmsService::class);
        $result = $smsService->sendSms($user->phone_number ?? '+255123456789', 'Test SMS from microfinance system');
        $this->info('SMS notification sent successfully.');
    } catch (\Exception $e) {
        $this->error('SMS notification failed: ' . $e->getMessage());
    }
    
})->purpose('Test the notification system');

Artisan::command('microfinance:stats', function () {
    $this->info('Microfinance System Statistics');
    $this->line('================================');
    
    $clientsCount = \App\Models\Client::count();
    $loansCount = \App\Models\Loan::count();
    $activeLoans = \App\Models\Loan::where('status', 'active')->count();
    $totalDisbursed = \App\Models\Loan::where('status', 'active')->sum('principal_amount');
    $totalOutstanding = \App\Models\Loan::where('status', 'active')->sum('outstanding_balance');
    
    $this->table(
        ['Metric', 'Value'],
        [
            ['Total Clients', number_format($clientsCount)],
            ['Total Loans', number_format($loansCount)],
            ['Active Loans', number_format($activeLoans)],
            ['Total Disbursed', 'TZS ' . number_format($totalDisbursed, 2)],
            ['Total Outstanding', 'TZS ' . number_format($totalOutstanding, 2)],
        ]
    );
})->purpose('Display system statistics');