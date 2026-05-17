<?php

namespace App\Observers;

use App\Models\SenderId;
use App\Services\SmsNotificationService;
use Illuminate\Support\Facades\Log;

class SenderIdObserver
{
    protected $notificationService;

    public function __construct(SmsNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the SenderId "updated" event.
     */
    public function updated(SenderId $senderId): void
    {
        try {
            // Check if status was changed
            if ($senderId->wasChanged('status')) {
                $previousStatus = $senderId->getOriginal('status');
                $this->notificationService->sendSenderIdStatusNotification($senderId, $previousStatus);
                
                Log::info('Sender ID status notification sent', [
                    'sender_id' => $senderId->id,
                    'sender_id_name' => $senderId->sender_id,
                    'previous_status' => $previousStatus,
                    'new_status' => $senderId->status,
                    'tenant_id' => $senderId->tenant_id
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error in SenderIdObserver::updated', [
                'sender_id' => $senderId->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the SenderId "created" event.
     */
    public function created(SenderId $senderId): void
    {
        try {
            // Send notification for new sender ID application
            $this->notificationService->sendSenderIdStatusNotification($senderId);
            
            Log::info('New sender ID application notification sent', [
                'sender_id' => $senderId->id,
                'sender_id_name' => $senderId->sender_id,
                'status' => $senderId->status,
                'tenant_id' => $senderId->tenant_id
            ]);

        } catch (\Exception $e) {
            Log::error('Error in SenderIdObserver::created', [
                'sender_id' => $senderId->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}