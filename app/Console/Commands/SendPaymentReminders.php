<?php

namespace App\Console\Commands;

use App\Models\LoanSchedule;
use App\Models\Sms\SmsProviderSetting;
use App\Models\Sms\SmsSenderId;
use App\Models\Sms\SmsBalance;
use App\Services\Sms\BeemAfricaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendPaymentReminders extends Command
{
    protected $signature = 'reminders:send-payment
                            {--type=due_today : Type of reminder (upcoming, due_today, overdue)}
                            {--days=3 : Days ahead for upcoming reminders}
                            {--tenant= : Specific tenant ID to process}';

    protected $description = 'Send payment reminder SMS to clients via Beem Africa';

    public function handle()
    {
        $type     = $this->option('type');
        $days     = (int) $this->option('days');
        $tenantId = $this->option('tenant');

        $this->info("Sending {$type} payment reminder SMS...");

        $query = LoanSchedule::with(['loan.client', 'loan.tenant'])
            ->whereIn('status', ['pending', 'partial']);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        switch ($type) {
            case 'upcoming':
                $query->whereBetween('due_date', [now()->addDay(), now()->addDays($days)]);
                break;
            case 'due_today':
                $query->whereDate('due_date', today());
                break;
            case 'overdue':
                $query->whereDate('due_date', '<', today());
                break;
            default:
                $this->error('Invalid type. Use: upcoming, due_today, or overdue');
                return 1;
        }

        $schedules = $query->get();

        if ($schedules->isEmpty()) {
            $this->info("No {$type} schedules found.");
            return 0;
        }

        $this->info("Found {$schedules->count()} schedules.");

        $beem = app(BeemAfricaService::class);

        // Check Beem credentials exist
        $providerSetting = SmsProviderSetting::getBeemAfrica();
        if (!$providerSetting || !$providerSetting->api_key) {
            $this->error('Beem Africa credentials not configured. Set them in Settings > SMS Settings.');
            return 1;
        }

        $bar = $this->output->createProgressBar($schedules->count());
        $bar->start();

        $sent   = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($schedules as $schedule) {
            try {
                $loan   = $schedule->loan;
                $client = $loan?->client;

                if (!$client || !$client->phone) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                $tenantIdForSchedule = $schedule->tenant_id ?? $loan->tenant_id;

                // Resolve sender ID
                $senderId = $this->resolveSenderId($tenantIdForSchedule, $providerSetting);

                // Build message
                $name      = trim($client->first_name . ' ' . $client->last_name);
                $amount    = number_format((float)($schedule->total_amount - $schedule->paid_amount), 2);
                $dueDate   = \Carbon\Carbon::parse($schedule->due_date)->format('d M Y');
                $loanNo    = $loan->loan_number ?? ('LN' . $loan->id);
                $org       = $loan->tenant?->name ?? 'TFS';

                if ($type === 'overdue') {
                    $daysLate = \Carbon\Carbon::parse($schedule->due_date)->diffInDays(today());
                    $message  = "Dear {$name}, your loan {$loanNo} payment of TZS {$amount} was due on {$dueDate} ({$daysLate} days overdue). Please pay immediately to avoid penalties. - {$org}";
                } elseif ($type === 'due_today') {
                    $message = "Dear {$name}, your loan {$loanNo} payment of TZS {$amount} is due TODAY ({$dueDate}). Please ensure timely payment. - {$org}";
                } else {
                    $message = "Dear {$name}, your loan {$loanNo} payment of TZS {$amount} is due on {$dueDate}. Please prepare for timely payment. - {$org}";
                }

                // Send SMS
                $result = $beem->sendSms($senderId, $client->phone, $message);

                if ($result['success']) {
                    $sent++;
                    // Deduct from tenant SMS balance
                    try {
                        $balance = SmsBalance::getOrCreateForTenant($tenantIdForSchedule);
                        $smsCount = (int) ceil(strlen($message) / 160);
                        if ($balance->hasEnoughBalance($smsCount)) {
                            $balance->deduct($smsCount);
                        }
                    } catch (\Throwable $balEx) {
                        // Non-critical
                    }
                } else {
                    $failed++;
                    Log::channel('sms')->warning('Payment reminder SMS failed', [
                        'schedule_id' => $schedule->id,
                        'phone'       => $client->phone,
                        'error'       => $result['error'] ?? 'unknown',
                    ]);
                }

            } catch (\Throwable $e) {
                $failed++;
                Log::channel('sms')->error('SendPaymentReminders exception', [
                    'schedule_id' => $schedule->id ?? null,
                    'error'       => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Sent: {$sent} | Failed: {$failed} | Skipped (no phone): {$skipped}");

        return 0;
    }

    protected function resolveSenderId(int $tenantId, SmsProviderSetting $providerSetting): string
    {
        $sid = SmsSenderId::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->value('sender_id');

        if ($sid) return $sid;

        $system = SmsSenderId::whereNull('tenant_id')
            ->where('is_active', true)
            ->value('sender_id');

        if ($system) return $system;

        return $providerSetting->default_sender_id ?? 'TFS';
    }
}