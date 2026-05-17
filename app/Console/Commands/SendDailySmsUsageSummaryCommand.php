<?php

namespace App\Console\Commands;

use App\Services\SmsNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendDailySmsUsageSummaryCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sms:send-daily-summary
                            {--date= : Date to send summary for (Y-m-d format, defaults to yesterday)}';

    /**
     * The console command description.
     */
    protected $description = 'Send daily SMS usage summary to tenants';

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
        $date = $this->option('date') ? 
            \Carbon\Carbon::createFromFormat('Y-m-d', $this->option('date')) : 
            now()->subDay();

        $this->info('Sending daily SMS usage summary for ' . $date->format('Y-m-d') . '...');

        try {
            $this->notificationService->sendDailyUsageSummary();
            
            $this->info('Daily usage summary sent successfully.');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Error sending daily usage summary: ' . $e->getMessage());
            
            Log::error('Daily SMS usage summary failed', [
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}