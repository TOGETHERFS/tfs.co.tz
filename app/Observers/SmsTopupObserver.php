<?php

namespace App\Observers;

use App\Models\SmsTopup;
use App\Services\SmsNotificationService;
use Illuminate\Support\Facades\Log;

class SmsTopupObserver
{
    protected $notificationService;

    public function __construct(SmsNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the SmsTopup "updated" event.
     */
    public function updated(SmsTopup $smsTopup): void
    {
        try {
            // Check if status was changed
            if ($smsTopup->wasChanged('status')) {
                $previousStatus = $smsTopup->getOriginal('status');
                
                switch ($smsTopup->status) {
                    case 'completed':
                        $this->notificationService->sendTopupSuccessNotification($smsTopup);
                        break;
                        
                    case 'failed':
                        $this->notificationService->sendTopupFailedNotification($smsTopup);
                        break;
                }
                
                Log::info('SMS topup status notification sent', [
                    'topup_id' => $smsTopup->id,
                    'transaction_id' => $smsTopup->transaction_id,
                    'previous_status' => $previousStatus,
                    'new_status' => $smsTopup->status,
                    'tenant_id' => $smsTopup->tenant_id,
                    'amount' => $smsTopup->amount
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error in SmsTopupObserver::updated', [
                'topup_id' => $smsTopup->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}