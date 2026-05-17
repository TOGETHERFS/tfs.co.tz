<?php

namespace App\Observers;

use App\Models\SmsWallet;
use App\Services\SmsNotificationService;
use Illuminate\Support\Facades\Log;

class SmsWalletObserver
{
    protected $notificationService;

    public function __construct(SmsNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the SmsWallet "updated" event.
     */
    public function updated(SmsWallet $smsWallet): void
    {
        try {
            // Check if balance was updated and is now low
            if ($smsWallet->wasChanged('balance')) {
                $this->checkLowBalance($smsWallet);
            }

            // Check if low balance threshold was changed
            if ($smsWallet->wasChanged('low_balance_threshold')) {
                $this->checkLowBalance($smsWallet);
            }

        } catch (\Exception $e) {
            Log::error('Error in SmsWalletObserver::updated', [
                'wallet_id' => $smsWallet->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if wallet balance is low and send notification if needed.
     */
    protected function checkLowBalance(SmsWallet $smsWallet): void
    {
        if (!$smsWallet->low_balance_notifications) {
            return;
        }

        // Check if balance is at or below threshold
        if ($smsWallet->balance <= $smsWallet->low_balance_threshold) {
            // Check if we haven't sent a notification recently (within 24 hours)
            $lastNotification = $smsWallet->last_low_balance_notification;
            
            if (!$lastNotification || $lastNotification->diffInHours(now()) >= 24) {
                $this->notificationService->sendLowBalanceNotification($smsWallet);
                
                // Update last notification timestamp
                $smsWallet->update([
                    'last_low_balance_notification' => now()
                ]);
            }
        }
    }
}