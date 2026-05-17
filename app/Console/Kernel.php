<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Generate monthly invoices on the 1st of each month at 2:00 AM
        $schedule->command('billing:generate-invoices')
                 ->monthlyOn(1, '02:00')
                 ->withoutOverlapping();

        // Process overdue invoices daily at 3:00 AM
        $schedule->command('billing:process-overdue')
                 ->dailyAt('03:00')
                 ->withoutOverlapping();

        // Send upcoming payment reminders daily at 9:00 AM
        $schedule->command('reminders:send-payment --type=upcoming --days=3')
                 ->dailyAt('09:00')
                 ->withoutOverlapping();

        // Send due today reminders at 8:00 AM
        $schedule->command('reminders:send-payment --type=due_today')
                 ->dailyAt('08:00')
                 ->withoutOverlapping();

        // Send overdue reminders daily at 10:00 AM
        $schedule->command('reminders:send-payment --type=overdue')
                 ->dailyAt('10:00')
                 ->withoutOverlapping();

        // Clean up old notifications (older than 30 days) weekly
        $schedule->command('model:prune', ['--model' => 'App\\Models\\DatabaseNotification'])
                 ->weekly()
                 ->sundays()
                 ->at('01:00');

        // Clean up failed jobs weekly
        $schedule->command('queue:prune-failed --hours=168') // 7 days
                 ->weekly()
                 ->sundays()
                 ->at('01:30');

        // SMS Notification Commands
        // Check for low SMS balance every 6 hours (reduced from hourly to save processes)
        $schedule->command('sms:check-low-balance')
                 ->everySixHours()
                 ->withoutOverlapping();

        // Send daily SMS usage summary at 6:00 PM
        $schedule->command('sms:send-daily-summary')
                 ->dailyAt('18:00')
                 ->withoutOverlapping();

        // Send weekly SMS usage report on Mondays at 9:00 AM
        $schedule->command('sms:send-weekly-report')
                 ->weeklyOn(1, '09:00')
                 ->withoutOverlapping();

        // SMS Provider Tasks - Sync balance every 6 hours (reduced from every 30 min)
        $schedule->command('sms:scheduled-tasks --sync-balance')
                 ->everySixHours()
                 ->withoutOverlapping();

        // SMS Provider Tasks - Sync sender IDs daily at 4:00 AM
        $schedule->command('sms:scheduled-tasks --sync-senders')
                 ->dailyAt('04:00')
                 ->withoutOverlapping();

        // SMS Provider Tasks - Update delivery reports every 2 hours (reduced from every 15 min)
        $schedule->command('sms:scheduled-tasks --update-delivery')
                 ->everyTwoHours()
                 ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}