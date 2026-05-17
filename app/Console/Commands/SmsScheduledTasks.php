<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Sms\BeemAfricaService;
use App\Models\Sms\SmsProviderSetting;
use App\Models\Sms\SmsMessage;
use App\Models\Sms\SmsSenderId;
use Illuminate\Support\Facades\Log;

class SmsScheduledTasks extends Command
{
    protected $signature = 'sms:scheduled-tasks {--sync-balance : Sync provider balance} {--sync-senders : Sync sender IDs} {--update-delivery : Update delivery reports} {--all : Run all tasks}';
    
    protected $description = 'Run scheduled SMS management tasks';

    protected BeemAfricaService $beemService;

    public function __construct(BeemAfricaService $beemService)
    {
        parent::__construct();
        $this->beemService = $beemService;
    }

    public function handle()
    {
        $runAll = $this->option('all');
        
        if ($runAll || $this->option('sync-balance')) {
            $this->syncProviderBalance();
        }
        
        if ($runAll || $this->option('sync-senders')) {
            $this->syncSenderIds();
        }
        
        if ($runAll || $this->option('update-delivery')) {
            $this->updateDeliveryReports();
        }
        
        $this->info('SMS scheduled tasks completed.');
        return Command::SUCCESS;
    }

    protected function syncProviderBalance()
    {
        $this->info('Syncing provider balance...');
        
        if (!$this->beemService->isConfigured()) {
            $this->warn('Beem Africa not configured. Skipping balance sync.');
            return;
        }

        try {
            $result = $this->beemService->getBalance();
            
            if ($result['success']) {
                $this->info("Provider balance: {$result['balance']} SMS credits");
                Log::channel('sms')->info('Cron: Provider balance synced', ['balance' => $result['balance']]);
            } else {
                $this->error('Failed to sync balance: ' . ($result['error'] ?? 'Unknown error'));
                Log::channel('sms')->error('Cron: Failed to sync balance', ['error' => $result['error'] ?? 'Unknown']);
            }
        } catch (\Exception $e) {
            $this->error('Exception syncing balance: ' . $e->getMessage());
            Log::channel('sms')->error('Cron: Exception syncing balance', ['error' => $e->getMessage()]);
        }
    }

    protected function syncSenderIds()
    {
        $this->info('Syncing sender IDs from Beem Africa...');
        
        if (!$this->beemService->isConfigured()) {
            $this->warn('Beem Africa not configured. Skipping sender ID sync.');
            return;
        }

        try {
            $result = $this->beemService->getSenderIds();
            
            if ($result['success']) {
                $count = 0;
                foreach ($result['sender_ids'] as $senderData) {
                    SmsSenderId::updateOrCreate(
                        ['provider_id' => $senderData['senderid'] ?? $senderData['sender_id']],
                        [
                            'sender_id' => $senderData['senderid'] ?? $senderData['sender_id'],
                            'is_active' => ($senderData['status'] ?? '') === 'Approved',
                        ]
                    );
                    $count++;
                }
                $this->info("Synced {$count} sender IDs from Beem Africa");
                Log::channel('sms')->info('Cron: Sender IDs synced', ['count' => $count]);
            } else {
                $this->error('Failed to sync sender IDs: ' . ($result['error'] ?? 'Unknown error'));
                Log::channel('sms')->error('Cron: Failed to sync sender IDs', ['error' => $result['error'] ?? 'Unknown']);
            }
        } catch (\Exception $e) {
            $this->error('Exception syncing sender IDs: ' . $e->getMessage());
            Log::channel('sms')->error('Cron: Exception syncing sender IDs', ['error' => $e->getMessage()]);
        }
    }

    protected function updateDeliveryReports()
    {
        $this->info('Updating delivery reports for pending messages...');
        
        if (!$this->beemService->isConfigured()) {
            $this->warn('Beem Africa not configured. Skipping delivery report updates.');
            return;
        }

        // Get messages sent in the last 24 hours that are still pending/queued
        $pendingMessages = SmsMessage::whereIn('status', ['queued', 'sent'])
            ->where('created_at', '>=', now()->subHours(24))
            ->whereNotNull('provider_message_id')
            ->take(100)
            ->get();

        $this->info("Found {$pendingMessages->count()} messages to check...");
        
        $updated = 0;
        foreach ($pendingMessages as $message) {
            try {
                $result = $this->beemService->getDeliveryReport($message->provider_message_id);
                
                if ($result['success'] && !empty($result['reports'])) {
                    foreach ($result['reports'] as $report) {
                        $status = strtolower($report['status'] ?? '');
                        if (in_array($status, ['delivered', 'failed', 'rejected'])) {
                            $message->update([
                                'status' => $status === 'delivered' ? 'delivered' : 'failed',
                                'delivered_at' => $status === 'delivered' ? now() : null,
                                'error_message' => $status !== 'delivered' ? ($report['error'] ?? 'Delivery failed') : null,
                            ]);
                            $updated++;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::channel('sms')->warning('Cron: Failed to get delivery report', [
                    'message_id' => $message->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->info("Updated {$updated} delivery reports");
        Log::channel('sms')->info('Cron: Delivery reports updated', ['updated' => $updated]);
    }
}
