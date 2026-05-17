<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Models\SmsCampaign;
use App\Models\SmsProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SmsDeliveryWebhookController extends Controller
{
    /**
     * Handle delivery reports from various SMS providers.
     */
    public function handle(Request $request, string $provider)
    {
        try {
            Log::info("SMS DLR webhook received from {$provider}", [
                'headers' => $request->headers->all(),
                'body' => $request->all()
            ]);

            // Validate provider
            $smsProvider = SmsProvider::where('provider_name', $provider)
                ->where('is_active', true)
                ->first();

            if (!$smsProvider) {
                Log::warning("Unknown or inactive SMS provider: {$provider}");
                return response()->json(['error' => 'Unknown provider'], 400);
            }

            // Route to appropriate handler based on provider
            switch (strtolower($provider)) {
                case 'beem':
                    return $this->handleBeemDLR($request);
                case 'twilio':
                    return $this->handleTwilioDLR($request);
                case 'nexmo':
                case 'vonage':
                    return $this->handleNexmoDLR($request);
                case 'infobip':
                    return $this->handleInfobipDLR($request);
                case 'clickatell':
                    return $this->handleClickatellDLR($request);
                default:
                    return $this->handleGenericDLR($request, $provider);
            }

        } catch (\Exception $e) {
            Log::error("Error processing SMS DLR webhook from {$provider}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle Beem Africa delivery reports.
     */
    private function handleBeemDLR(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->all();
        
        // Beem Africa DLR format
        $messageId = $data['message_id'] ?? $data['id'] ?? null;
        $status = $data['status'] ?? null;
        $errorCode = $data['error_code'] ?? null;
        $errorMessage = $data['error_message'] ?? null;
        $deliveredAt = $data['delivered_at'] ?? $data['timestamp'] ?? null;

        if (!$messageId) {
            Log::warning('Beem DLR missing message_id', $data);
            return response()->json(['error' => 'Missing message_id'], 400);
        }

        return $this->updateSmsStatus($messageId, $status, $errorCode, $errorMessage, $deliveredAt);
    }

    /**
     * Handle Twilio delivery reports.
     */
    private function handleTwilioDLR(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->all();
        
        // Twilio DLR format
        $messageId = $data['MessageSid'] ?? $data['SmsSid'] ?? null;
        $status = $data['MessageStatus'] ?? $data['SmsStatus'] ?? null;
        $errorCode = $data['ErrorCode'] ?? null;
        $errorMessage = $data['ErrorMessage'] ?? null;
        $deliveredAt = $data['DateSent'] ?? null;

        if (!$messageId) {
            Log::warning('Twilio DLR missing MessageSid', $data);
            return response()->json(['error' => 'Missing MessageSid'], 400);
        }

        // Map Twilio status to our standard status
        $mappedStatus = $this->mapTwilioStatus($status);

        return $this->updateSmsStatus($messageId, $mappedStatus, $errorCode, $errorMessage, $deliveredAt);
    }

    /**
     * Handle Nexmo/Vonage delivery reports.
     */
    private function handleNexmoDLR(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->all();
        
        // Nexmo DLR format
        $messageId = $data['messageId'] ?? $data['message-id'] ?? null;
        $status = $data['status'] ?? null;
        $errorCode = $data['err-code'] ?? $data['error-code'] ?? null;
        $errorMessage = $data['error-text'] ?? null;
        $deliveredAt = $data['message-timestamp'] ?? null;

        if (!$messageId) {
            Log::warning('Nexmo DLR missing messageId', $data);
            return response()->json(['error' => 'Missing messageId'], 400);
        }

        // Map Nexmo status to our standard status
        $mappedStatus = $this->mapNexmoStatus($status);

        return $this->updateSmsStatus($messageId, $mappedStatus, $errorCode, $errorMessage, $deliveredAt);
    }

    /**
     * Handle Infobip delivery reports.
     */
    private function handleInfobipDLR(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->all();
        
        // Infobip DLR format
        $results = $data['results'] ?? [$data];
        
        $responses = [];
        foreach ($results as $result) {
            $messageId = $result['messageId'] ?? null;
            $status = $result['status']['groupName'] ?? $result['status']['name'] ?? null;
            $errorCode = $result['error']['groupId'] ?? null;
            $errorMessage = $result['error']['name'] ?? null;
            $deliveredAt = $result['doneAt'] ?? null;

            if ($messageId) {
                $mappedStatus = $this->mapInfobipStatus($status);
                $response = $this->updateSmsStatus($messageId, $mappedStatus, $errorCode, $errorMessage, $deliveredAt);
                $responses[] = $response->getData();
            }
        }

        return response()->json(['processed' => count($responses), 'results' => $responses]);
    }

    /**
     * Handle Clickatell delivery reports.
     */
    private function handleClickatellDLR(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->all();
        
        // Clickatell DLR format
        $messageId = $data['messageId'] ?? $data['apiMsgId'] ?? null;
        $status = $data['messageStatus'] ?? $data['status'] ?? null;
        $errorCode = $data['errorCode'] ?? null;
        $errorMessage = $data['errorDescription'] ?? null;
        $deliveredAt = $data['timestamp'] ?? null;

        if (!$messageId) {
            Log::warning('Clickatell DLR missing messageId', $data);
            return response()->json(['error' => 'Missing messageId'], 400);
        }

        // Map Clickatell status to our standard status
        $mappedStatus = $this->mapClickatellStatus($status);

        return $this->updateSmsStatus($messageId, $mappedStatus, $errorCode, $errorMessage, $deliveredAt);
    }

    /**
     * Handle generic delivery reports.
     */
    private function handleGenericDLR(Request $request, string $provider): \Illuminate\Http\JsonResponse
    {
        $data = $request->all();
        
        // Try common field names
        $messageId = $data['message_id'] ?? $data['messageId'] ?? $data['id'] ?? $data['reference'] ?? null;
        $status = $data['status'] ?? $data['delivery_status'] ?? null;
        $errorCode = $data['error_code'] ?? $data['errorCode'] ?? null;
        $errorMessage = $data['error_message'] ?? $data['errorMessage'] ?? null;
        $deliveredAt = $data['delivered_at'] ?? $data['timestamp'] ?? $data['date'] ?? null;

        if (!$messageId) {
            Log::warning("Generic DLR missing message identifier for provider {$provider}", $data);
            return response()->json(['error' => 'Missing message identifier'], 400);
        }

        return $this->updateSmsStatus($messageId, $status, $errorCode, $errorMessage, $deliveredAt);
    }

    /**
     * Update SMS status in database.
     */
    private function updateSmsStatus(
        string $messageId, 
        ?string $status, 
        ?string $errorCode = null, 
        ?string $errorMessage = null, 
        ?string $deliveredAt = null
    ): \Illuminate\Http\JsonResponse {
        
        DB::beginTransaction();
        
        try {
            // Find SMS log by external message ID
            $smsLog = SmsLog::where('external_message_id', $messageId)->first();
            
            if (!$smsLog) {
                Log::warning("SMS log not found for message ID: {$messageId}");
                return response()->json(['error' => 'Message not found'], 404);
            }

            // Don't update if already in a final state
            if (in_array($smsLog->status, ['delivered', 'failed', 'expired'])) {
                Log::info("SMS already in final state: {$smsLog->status} for message ID: {$messageId}");
                return response()->json(['message' => 'Already processed'], 200);
            }

            // Prepare update data
            $updateData = [
                'status' => $this->normalizeStatus($status),
                'delivery_report_received_at' => now(),
            ];

            if ($errorCode) {
                $updateData['error_code'] = $errorCode;
            }

            if ($errorMessage) {
                $updateData['error_message'] = $errorMessage;
            }

            if ($deliveredAt) {
                try {
                    $updateData['delivered_at'] = Carbon::parse($deliveredAt);
                } catch (\Exception $e) {
                    Log::warning("Invalid delivered_at format: {$deliveredAt}");
                }
            }

            // Update SMS log
            $smsLog->update($updateData);

            // Update campaign statistics if this SMS belongs to a campaign
            if ($smsLog->campaign_id) {
                $this->updateCampaignStats($smsLog->campaign_id);
            }

            DB::commit();

            Log::info("SMS status updated successfully", [
                'message_id' => $messageId,
                'sms_log_id' => $smsLog->id,
                'old_status' => $smsLog->getOriginal('status'),
                'new_status' => $updateData['status']
            ]);

            return response()->json(['message' => 'Status updated successfully'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Error updating SMS status", [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Failed to update status'], 500);
        }
    }

    /**
     * Update campaign statistics.
     */
    private function updateCampaignStats(int $campaignId): void
    {
        try {
            $campaign = SmsCampaign::find($campaignId);
            if (!$campaign) {
                return;
            }

            $stats = SmsLog::where('campaign_id', $campaignId)
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN status = "delivered" THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed,
                    SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent
                ')
                ->first();

            $campaign->update([
                'total_recipients' => $stats->total,
                'delivered_count' => $stats->delivered,
                'failed_count' => $stats->failed,
                'pending_count' => $stats->pending,
                'sent_count' => $stats->sent,
            ]);

        } catch (\Exception $e) {
            Log::error("Error updating campaign stats for campaign {$campaignId}", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Normalize status to our standard format.
     */
    private function normalizeStatus(?string $status): string
    {
        if (!$status) {
            return 'unknown';
        }

        $status = strtolower(trim($status));

        // Map various provider statuses to our standard statuses
        $statusMap = [
            // Delivered statuses
            'delivered' => 'delivered',
            'dlvrd' => 'delivered',
            'success' => 'delivered',
            'ok' => 'delivered',
            'accepted' => 'delivered',
            
            // Failed statuses
            'failed' => 'failed',
            'undeliv' => 'failed',
            'rejected' => 'failed',
            'error' => 'failed',
            'expired' => 'failed',
            'unknown' => 'failed',
            'rejectd' => 'failed',
            
            // Pending statuses
            'pending' => 'pending',
            'buffered' => 'pending',
            'enroute' => 'pending',
            'submitted' => 'sent',
            'sent' => 'sent',
        ];

        return $statusMap[$status] ?? 'unknown';
    }

    /**
     * Map Twilio status to our standard status.
     */
    private function mapTwilioStatus(?string $status): string
    {
        $statusMap = [
            'delivered' => 'delivered',
            'sent' => 'sent',
            'failed' => 'failed',
            'undelivered' => 'failed',
            'queued' => 'pending',
            'accepted' => 'pending',
            'receiving' => 'pending',
            'received' => 'delivered',
        ];

        return $statusMap[strtolower($status ?? '')] ?? 'unknown';
    }

    /**
     * Map Nexmo status to our standard status.
     */
    private function mapNexmoStatus(?string $status): string
    {
        $statusMap = [
            'delivered' => 'delivered',
            'buffered' => 'pending',
            'failed' => 'failed',
            'rejected' => 'failed',
            'unknown' => 'failed',
            'expired' => 'failed',
        ];

        return $statusMap[strtolower($status ?? '')] ?? 'unknown';
    }

    /**
     * Map Infobip status to our standard status.
     */
    private function mapInfobipStatus(?string $status): string
    {
        $statusMap = [
            'delivered' => 'delivered',
            'pending' => 'pending',
            'undeliverable' => 'failed',
            'expired' => 'failed',
            'rejected' => 'failed',
            'unknown' => 'failed',
        ];

        return $statusMap[strtolower($status ?? '')] ?? 'unknown';
    }

    /**
     * Map Clickatell status to our standard status.
     */
    private function mapClickatellStatus(?string $status): string
    {
        $statusMap = [
            'delivered' => 'delivered',
            'queued' => 'pending',
            'failed' => 'failed',
            'error' => 'failed',
            'unknown' => 'failed',
            'expired' => 'failed',
        ];

        return $statusMap[strtolower($status ?? '')] ?? 'unknown';
    }

    /**
     * Test webhook endpoint.
     */
    public function test(Request $request, string $provider)
    {
        Log::info("SMS DLR test webhook called for provider: {$provider}", $request->all());
        
        return response()->json([
            'message' => 'Test webhook received successfully',
            'provider' => $provider,
            'timestamp' => now()->toISOString(),
            'data' => $request->all()
        ]);
    }
}