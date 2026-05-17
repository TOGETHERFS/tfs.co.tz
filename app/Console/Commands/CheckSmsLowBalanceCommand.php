<?php

namespace App\Console\Commands;

use App\Services\SmsNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSmsLowBalanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sms:check-low-balance
                            {--force : Force send notifications even if recently sent}';

    /**
     * The console command description.
     */
    protected $description = 'Check SMS wallets for low balance and send notifications';

    protected $notificationService;

    /**
     * Create a new command instance.
     */
    public function __construct(SmsNotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking SMS wallets for low balance...');

        try {
            $this->notificationService->checkAndSendLowBalanceNotifications();
            
            $this->info('Low balance check completed successfully.');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Error checking low balance: ' . $e->getMessage());
            
            Log::error('SMS low balance check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}