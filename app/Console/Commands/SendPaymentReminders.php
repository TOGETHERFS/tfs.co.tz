<?php

namespace App\Console\Commands;

use App\Models\LoanSchedule;
use App\Notifications\PaymentReminderNotification;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendPaymentReminders extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'reminders:send-payment 
                            {--type=upcoming : Type of reminder (upcoming, due_today, overdue)}
                            {--days=3 : Days ahead for upcoming reminders}
                            {--tenant= : Specific tenant ID to process}';

    /**
     * The console command description.
     */
    protected $description = 'Send payment reminders to clients';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $days = (int) $this->option('days');
        $tenantId = $this->option('tenant');

        $this->info("Sending {$type} payment reminders...");

        $query = LoanSchedule::with(['loan.client'])
                            ->where('status', 'pending');

        // Filter by tenant if specified
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        // Apply date filters based on reminder type
        switch ($type) {
            case 'upcoming':
                $query->whereBetween('due_date', [
                    now()->addDay(),
                    now()->addDays($days)
                ]);
                break;

            case 'due_today':
                $query->whereDate('due_date', today());
                break;

            case 'overdue':
                $query->where('due_date', '<', today());
                break;

            default:
                $this->error('Invalid reminder type. Use: upcoming, due_today, or overdue');
                return 1;
        }

        $schedules = $query->get();

        if ($schedules->isEmpty()) {
            $this->info("No {$type} payments found.");
            return 0;
        }

        $this->info("Found {$schedules->count()} {$type} payments.");

        $bar = $this->output->createProgressBar($schedules->count());
        $bar->start();

        $sent = 0;
        $failed = 0;

        foreach ($schedules as $schedule) {
            try {
                $client = $schedule->loan->client;
                
                if (!$client) {
                    $this->warn("No client found for loan schedule ID: {$schedule->id}");
                    $failed++;
                    continue;
                }

                // Send notification
                $client->notify(new PaymentReminderNotification($schedule, $type));
                $sent++;

                // Update last reminder sent timestamp
                $schedule->update([
                    'last_reminder_sent' => now()
                ]);

            } catch (\Exception $e) {
                $this->error("Failed to send reminder for schedule ID {$schedule->id}: " . $e->getMessage());
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Payment reminders sent successfully: {$sent}");
        
        if ($failed > 0) {
            $this->warn("Failed to send: {$failed}");
        }

        return 0;
    }
}