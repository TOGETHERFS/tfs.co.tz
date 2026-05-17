<?php

namespace App\Services;

use App\Models\SmsLog;
use App\Models\SmsCampaign;
use App\Models\SenderId;
use App\Models\Tenant;
use App\Jobs\SendSmsJob;
use App\Jobs\ProcessSmsCampaignJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;

class SmsSendingService
{
    protected SmsManager $smsManager;
    protected SmsWalletService $walletService;

    public function __construct(SmsManager $smsManager, SmsWalletService $walletService)
    {
        $this->smsManager = $smsManager;
        $this->walletService = $walletService;
    }

    /**
     * Send a single SMS.
     */
    public function sendSingle(
        int $tenantId,
        string $recipient,
        string $message,
        ?string $senderId = null,
        ?Carbon $scheduledAt = null,
        ?int $userId = null
    ): array {
        try {
            // Validate inputs
            $validation = $this->validateSmsInputs($tenantId, [$recipient], $message, $senderId);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message'],
                    'data' => null
                ];
            }

            // Calculate SMS count and cost (160 chars per SMS)
            $smsCount = ceil(strlen($message) / 160);
            
            // Check wallet balance
            if (!$this->walletService->hasBalance($tenantId, $smsCount)) {
                return [
                    'success' => false,
                    'message' => 'Insufficient SMS credits. Required: ' . $smsCount . ', Available: ' . $this->walletService->getBalance($tenantId),
                    'data' => null
                ];
            }

            // Create SMS log entry
            $smsLog = SmsLog::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId ?? auth()->id(),
                'sender_id' => $senderId,
                'message' => $message,
                'recipients' => [$recipient],
                'recipient_count' => 1,
                'status' => $scheduledAt ? 'scheduled' : 'pending',
                'message_type' => 'single',
                'cost' => $smsCount * 0.02, // Assuming 0.02 USD per SMS
                'scheduled_at' => $scheduledAt,
                'retry_count' => 0
            ]);

            // If scheduled, queue for later
            if ($scheduledAt) {
                SendSmsJob::dispatch($smsLog->id)->delay($scheduledAt);
                
                return [
                    'success' => true,
                    'message' => 'SMS scheduled successfully',
                    'data' => [
                        'sms_log_id' => $smsLog->id,
                        'scheduled_at' => $scheduledAt->toISOString(),
                        'estimated_cost' => $smsLog->cost
                    ]
                ];
            }

            // Send immediately
            $result = $this->processSingleSms($smsLog);

            return [
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => [
                    'sms_log_id' => $smsLog->id,
                    'provider_request_id' => $result['provider_request_id'] ?? null,
                    'cost' => $smsLog->cost
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Failed to send single SMS", [
                'tenant_id' => $tenantId,
                'recipient' => $recipient,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send SMS: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Send bulk SMS.
     */
    public function sendBulk(
        int $tenantId,
        array $recipients,
        string $message,
        ?string $senderId = null,
        ?Carbon $scheduledAt = null,
        ?int $userId = null,
        ?string $campaignName = null
    ): array {
        try {
            // Validate inputs
            $validation = $this->validateSmsInputs($tenantId, $recipients, $message, $senderId);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message'],
                    'data' => null
                ];
            }

            // Remove duplicates
            $validRecipients = array_unique($recipients);

            if (empty($validRecipients)) {
                return [
                    'success' => false,
                    'message' => 'No valid recipients found',
                    'data' => null
                ];
            }

            // Calculate total SMS count and cost
            $smsCount = ceil(strlen($message) / 160);
            $totalCreditsNeeded = $smsCount * count($validRecipients);
            
            // Check wallet balance
            if (!$this->walletService->hasBalance($tenantId, $totalCreditsNeeded)) {
                return [
                    'success' => false,
                    'message' => 'Insufficient SMS credits. Required: ' . $totalCreditsNeeded . ', Available: ' . $this->walletService->getBalance($tenantId),
                    'data' => null
                ];
            }

            // Create campaign if bulk SMS
            $campaign = null;
            if (count($validRecipients) > 1) {
                $campaign = SmsCampaign::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId ?? auth()->id(),
                    'name' => $campaignName ?? 'Bulk SMS - ' . now()->format('Y-m-d H:i:s'),
                    'message' => $message,
                    'sender_id' => $senderId,
                    'recipients' => $validRecipients,
                    'total_recipients' => count($validRecipients),
                    'status' => $scheduledAt ? 'scheduled' : 'pending',
                    'estimated_cost' => $totalCreditsNeeded * 0.02,
                    'cost_per_sms' => 0.02,
                    'scheduled_at' => $scheduledAt
                ]);
            }

            // Create SMS log entries
            $smsLogs = [];
            foreach ($validRecipients as $recipient) {
                $smsLog = SmsLog::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $userId ?? auth()->id(),
                    'campaign_id' => $campaign?->id,
                    'sender_id' => $senderId,
                    'message' => $message,
                    'recipients' => [$recipient],
                    'recipient_count' => 1,
                    'status' => $scheduledAt ? 'scheduled' : 'pending',
                    'message_type' => $campaign ? 'campaign' : 'bulk',
                    'cost' => $smsCount * 0.02,
                    'scheduled_at' => $scheduledAt,
                    'retry_count' => 0
                ]);
                
                $smsLogs[] = $smsLog;
            }

            // If scheduled, queue for later
            if ($scheduledAt) {
                if ($campaign) {
                    ProcessSmsCampaignJob::dispatch($campaign->id)->delay($scheduledAt);
                } else {
                    foreach ($smsLogs as $smsLog) {
                        SendSmsJob::dispatch($smsLog->id)->delay($scheduledAt);
                    }
                }
                
                return [
                    'success' => true,
                    'message' => 'Bulk SMS scheduled successfully',
                    'data' => [
                        'campaign_id' => $campaign?->id,
                        'total_recipients' => count($validRecipients),
                        'scheduled_at' => $scheduledAt->toISOString(),
                        'estimated_cost' => $totalCreditsNeeded * 0.02
                    ]
                ];
            }

            // Process immediately
            if ($campaign) {
                ProcessSmsCampaignJob::dispatch($campaign->id);
            } else {
                foreach ($smsLogs as $smsLog) {
                    SendSmsJob::dispatch($smsLog->id);
                }
            }

            return [
                'success' => true,
                'message' => 'Bulk SMS queued for processing',
                'data' => [
                    'campaign_id' => $campaign?->id,
                    'total_recipients' => count($validRecipients),
                    'estimated_cost' => $totalCreditsNeeded * 0.02,
                    'sms_log_ids' => collect($smsLogs)->pluck('id')->toArray()
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Failed to send bulk SMS", [
                'tenant_id' => $tenantId,
                'recipient_count' => count($recipients),
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send bulk SMS: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Process a single SMS (called by job).
     */
    public function processSingleSms(SmsLog $smsLog): array
    {
        try {
            // Check if already processed
            if (in_array($smsLog->status, ['sent', 'delivered', 'failed'])) {
                return [
                    'success' => false,
                    'message' => 'SMS already processed'
                ];
            }

            // Reserve credits (160 chars per SMS)
            $smsCount = ceil(strlen($smsLog->message) / 160);
            $reserveSuccess = $this->walletService->reserveCredits(
                $smsLog->tenant_id,
                $smsCount,
                "SMS reservation - Log ID: {$smsLog->id}",
                "SMS-{$smsLog->id}"
            );

            if (!$reserveSuccess) {
                $smsLog->markAsFailed('Insufficient credits');
                return [
                    'success' => false,
                    'message' => 'Insufficient credits'
                ];
            }

            // Send SMS via provider
            $result = $this->smsManager->sendSingle(
                $smsLog->recipients[0],
                $smsLog->message,
                $smsLog->sender_id
            );

            if ($result['success']) {
                // Deduct credits from wallet
                $this->walletService->deductCredits(
                    $smsLog->tenant_id,
                    $smsCount,
                    "SMS sent - Log ID: {$smsLog->id}",
                    "SMS-{$smsLog->id}"
                );

                // Update SMS log
                $smsLog->update([
                    'status' => 'sent',
                    'provider' => $result['provider'],
                    'provider_request_id' => $result['provider_request_id'],
                    'provider_response' => $result['response'],
                    'sent_at' => now()
                ]);

                // Update campaign stats if applicable
                if ($smsLog->campaign_id) {
                    $this->updateCampaignStats($smsLog->campaign_id);
                }

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'provider_request_id' => $result['provider_request_id']
                ];
            } else {
                // Release reserved credits
                $this->walletService->releaseReservedCredits(
                    $smsLog->tenant_id,
                    $smsCount,
                    "SMS failed - Log ID: {$smsLog->id}",
                    "SMS-{$smsLog->id}"
                );

                $smsLog->markAsFailed($result['message']);

                return [
                    'success' => false,
                    'message' => $result['message']
                ];
            }

        } catch (\Exception $e) {
            Log::error("Failed to process single SMS", [
                'sms_log_id' => $smsLog->id,
                'error' => $e->getMessage()
            ]);

            $smsLog->markAsFailed('Processing error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Processing error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process SMS campaign.
     */
    public function processCampaign(SmsCampaign $campaign): void
    {
        try {
            if ($campaign->status !== 'pending' && $campaign->status !== 'scheduled') {
                return;
            }

            $campaign->markAsStarted();

            // Get pending SMS logs for this campaign
            $smsLogs = SmsLog::where('campaign_id', $campaign->id)
                ->where('status', 'pending')
                ->get();

            foreach ($smsLogs as $smsLog) {
                // Add delay between messages to avoid rate limiting
                SendSmsJob::dispatch($smsLog->id)->delay(now()->addSeconds(rand(1, 5)));
            }

            Log::info("Campaign processing started", [
                'campaign_id' => $campaign->id,
                'total_messages' => $smsLogs->count()
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to process campaign", [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);

            $campaign->markAsFailed('Processing error: ' . $e->getMessage());
        }
    }

    /**
     * Retry failed SMS.
     */
    public function retrySms(SmsLog $smsLog): array
    {
        if ($smsLog->status !== 'failed') {
            return [
                'success' => false,
                'message' => 'SMS is not in failed status'
            ];
        }

        if ($smsLog->retry_count >= 3) {
            return [
                'success' => false,
                'message' => 'Maximum retry attempts reached'
            ];
        }

        $smsLog->incrementRetryCount();
        $smsLog->update(['status' => 'pending']);

        SendSmsJob::dispatch($smsLog->id);

        return [
            'success' => true,
            'message' => 'SMS queued for retry'
        ];
    }

    /**
     * Get SMS delivery status.
     */
    public function getDeliveryStatus(string $providerRequestId): array
    {
        return $this->smsManager->getDeliveryStatus($providerRequestId);
    }

    /**
     * Update delivery status from webhook.
     */
    public function updateDeliveryStatus(string $providerRequestId, string $status, ?array $additionalData = null): bool
    {
        try {
            $smsLog = SmsLog::where('provider_request_id', $providerRequestId)->first();
            
            if (!$smsLog) {
                Log::warning("SMS log not found for provider request ID", [
                    'provider_request_id' => $providerRequestId
                ]);
                return false;
            }

            $deliveryReports = $smsLog->delivery_reports ?? [];
            $deliveryReports[] = [
                'status' => $status,
                'timestamp' => now()->toISOString(),
                'data' => $additionalData
            ];

            $updateData = ['delivery_reports' => $deliveryReports];

            // Update status based on delivery report
            switch (strtolower($status)) {
                case 'delivered':
                    $updateData['status'] = 'delivered';
                    $updateData['delivered_at'] = now();
                    break;
                case 'failed':
                case 'undelivered':
                    $updateData['status'] = 'failed';
                    $updateData['failed_at'] = now();
                    break;
            }

            $smsLog->update($updateData);

            // Update campaign stats if applicable
            if ($smsLog->campaign_id) {
                $this->updateCampaignStats($smsLog->campaign_id);
            }

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to update delivery status", [
                'provider_request_id' => $providerRequestId,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Validate SMS inputs.
     */
    protected function validateSmsInputs(int $tenantId, array $recipients, string $message, ?string $senderId): array
    {
        // Check if tenant exists and is active
        $tenant = Tenant::find($tenantId);
        if (!$tenant || $tenant->status === 'canceled') {
            return [
                'valid' => false,
                'message' => 'Tenant not found or inactive'
            ];
        }

        // Validate message
        if (empty(trim($message))) {
            return [
                'valid' => false,
                'message' => 'Message cannot be empty'
            ];
        }

        if (strlen($message) > 1600) { // Max 10 SMS parts
            return [
                'valid' => false,
                'message' => 'Message too long (max 1600 characters)'
            ];
        }

        // Validate recipients
        if (empty($recipients)) {
            return [
                'valid' => false,
                'message' => 'No recipients provided'
            ];
        }

        if (count($recipients) > 10000) {
            return [
                'valid' => false,
                'message' => 'Too many recipients (max 10,000)'
            ];
        }

        // Validate sender ID if provided
        if ($senderId) {
            $senderIdRecord = SenderId::where('tenant_id', $tenantId)
                ->where('sender_id', $senderId)
                ->where('is_active', true)
                ->first();

            if (!$senderIdRecord) {
                return [
                    'valid' => false,
                    'message' => 'Sender ID not found or not approved'
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Update campaign statistics.
     */
    protected function updateCampaignStats(int $campaignId): void
    {
        try {
            $campaign = SmsCampaign::find($campaignId);
            if (!$campaign) {
                return;
            }

            $stats = SmsLog::where('campaign_id', $campaignId)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                    SUM(cost) as total_cost
                ')
                ->first();

            $campaign->update([
                'sent_count' => $stats->sent,
                'delivered_count' => $stats->delivered,
                'failed_count' => $stats->failed,
                'pending_count' => $stats->pending,
                'actual_cost' => $stats->total_cost
            ]);

            // Update campaign status
            if ($stats->pending == 0) {
                if ($stats->failed == $stats->total) {
                    $campaign->markAsFailed('All messages failed');
                } else {
                    $campaign->markAsCompleted();
                }
            }

        } catch (\Exception $e) {
            Log::error("Failed to update campaign stats", [
                'campaign_id' => $campaignId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get SMS statistics for a tenant.
     */
    public function getSmsStats(int $tenantId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = SmsLog::where('tenant_id', $tenantId);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_messages,
            SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent_messages,
            SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered_messages,
            SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_messages,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_messages,
            SUM(cost) as total_cost,
            SUM(recipient_count) as total_recipients
        ')->first();

        return [
            'total_messages' => $stats->total_messages ?? 0,
            'sent_messages' => $stats->sent_messages ?? 0,
            'delivered_messages' => $stats->delivered_messages ?? 0,
            'failed_messages' => $stats->failed_messages ?? 0,
            'pending_messages' => $stats->pending_messages ?? 0,
            'total_cost' => $stats->total_cost ?? 0,
            'total_recipients' => $stats->total_recipients ?? 0,
            'delivery_rate' => $stats->sent_messages > 0 ? round(($stats->delivered_messages / $stats->sent_messages) * 100, 2) : 0,
            'success_rate' => $stats->total_messages > 0 ? round(($stats->sent_messages / $stats->total_messages) * 100, 2) : 0
        ];
    }
}