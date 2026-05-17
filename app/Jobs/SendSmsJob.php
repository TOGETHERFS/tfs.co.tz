<?php

namespace App\Jobs;

use App\Models\SmsLog;
use App\Services\SmsSendingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $smsLogId;
    public int $tries = 3;
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(int $smsLogId)
    {
        $this->smsLogId = $smsLogId;
        $this->onQueue('sms');
    }

    /**
     * Execute the job.
     */
    public function handle(SmsSendingService $smsSendingService): void
    {
        try {
            $smsLog = SmsLog::find($this->smsLogId);
            
            if (!$smsLog) {
                Log::error("SMS log not found", ['sms_log_id' => $this->smsLogId]);
                return;
            }

            // Check if SMS is still pending
            if ($smsLog->status !== 'pending' && $smsLog->status !== 'scheduled') {
                Log::info("SMS already processed", [
                    'sms_log_id' => $this->smsLogId,
                    'status' => $smsLog->status
                ]);
                return;
            }

            // Process the SMS
            $result = $smsSendingService->processSingleSms($smsLog);

            if ($result['success']) {
                Log::info("SMS sent successfully", [
                    'sms_log_id' => $this->smsLogId,
                    'provider_request_id' => $result['provider_request_id'] ?? null
                ]);
            } else {
                Log::warning("SMS sending failed", [
                    'sms_log_id' => $this->smsLogId,
                    'message' => $result['message']
                ]);
            }

        } catch (\Exception $e) {
            Log::error("SendSmsJob failed", [
                'sms_log_id' => $this->smsLogId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Mark SMS as failed if this is the last attempt
            if ($this->attempts() >= $this->tries) {
                $smsLog = SmsLog::find($this->smsLogId);
                if ($smsLog) {
                    $smsLog->markAsFailed('Job failed after ' . $this->tries . ' attempts: ' . $e->getMessage());
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
        Log::error("SendSmsJob permanently failed", [
            'sms_log_id' => $this->smsLogId,
            'error' => $exception->getMessage()
        ]);

        $smsLog = SmsLog::find($this->smsLogId);
        if ($smsLog && $smsLog->status === 'pending') {
            $smsLog->markAsFailed('Job permanently failed: ' . $exception->getMessage());
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return ['sms', 'send', 'sms_log:' . $this->smsLogId];
    }
}