<?php

namespace App\Console\Commands;

use App\Services\SmsNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendWeeklySmsUsageReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sms:send-weekly-report
                            {--week= : Week to send report for (Y-m-d format for week start, defaults to last week)}';

    /**
     * The console command description.
     */
    protected $description = 'Send weekly SMS usage report to tenants';

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
        $weekStart = $this->option('week') ? 
            \Carbon\Carbon::createFromFormat('Y-m-d', $this->option('week'))->startOfWeek() : 
            now()->subWeek()->startOfWeek();

        $weekEnd = $weekStart->copy()->endOfWeek();

        $this->info('Sending weekly SMS usage report for ' . $weekStart->format('Y-m-d') . ' to ' . $weekEnd->format('Y-m-d') . '...');

        try {
            $this->notificationService->sendWeeklyUsageReport();
            
            $this->info('Weekly usage report sent successfully.');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Error sending weekly usage report: ' . $e->getMessage());
            
            Log::error('Weekly SMS usage report failed', [
                'week_start' => $weekStart->format('Y-m-d'),
                'week_end' => $weekEnd->format('Y-m-d'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Command::FAILURE;
        }
    }
}