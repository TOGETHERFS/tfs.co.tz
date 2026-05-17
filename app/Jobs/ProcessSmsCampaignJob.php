<?php

namespace App\Jobs;

use App\Models\SmsCampaign;
use App\Services\SmsSendingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSmsCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $campaignId;
    public int $tries = 3;
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(int $campaignId)
    {
        $this->campaignId = $campaignId;
        $this->onQueue('sms-campaigns');
    }

    /**
     * Execute the job.
     */
    public function handle(SmsSendingService $smsSendingService): void
    {
        try {
            $campaign = SmsCampaign::find($this->campaignId);
            
            if (!$campaign) {
                Log::error("SMS campaign not found", ['campaign_id' => $this->campaignId]);
                return;
            }

            // Check if campaign is still pending or scheduled
            if (!in_array($campaign->status, ['pending', 'scheduled'])) {
                Log::info("SMS campaign already processed", [
                    'campaign_id' => $this->campaignId,
                    'status' => $campaign->status
                ]);
                return;
            }

            Log::info("Processing SMS campaign", [
                'campaign_id' => $this->campaignId,
                'name' => $campaign->name,
                'total_recipients' => $campaign->total_recipients
            ]);

            // Process the campaign
            $smsSendingService->processCampaign($campaign);

            Log::info("SMS campaign processing initiated", [
                'campaign_id' => $this->campaignId,
                'total_recipients' => $campaign->total_recipients
            ]);

        } catch (\Exception $e) {
            Log::error("ProcessSmsCampaignJob failed", [
                'campaign_id' => $this->campaignId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Mark campaign as failed if this is the last attempt
            if ($this->attempts() >= $this->tries) {
                $campaign = SmsCampaign::find($this->campaignId);
                if ($campaign) {
                    $campaign->markAsFailed('Job failed after ' . $this->tries . ' attempts: ' . $e->getMessage());
                }
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessSmsCampaignJob permanently failed", [
            'campaign_id' => $this->campaignId,
            'error' => $exception->getMessage()
        ]);

        $campaign = SmsCampaign::find($this->campaignId);
        if ($campaign && in_array($campaign->status, ['pending', 'scheduled'])) {
            $campaign->markAsFailed('Job permanently failed: ' . $exception->getMessage());
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['sms', 'campaign', 'campaign:' . $this->campaignId];
    }
}