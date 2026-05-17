<?php

namespace App\Notifications\Channels;

use App\Services\BeemSmsService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    protected $smsService;

    public function __construct(BeemSmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification)
    {
        if (!$notifiable->phone) {
            Log::warning('SMS notification skipped: No phone number', [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id,
                'notification_type' => get_class($notification),
            ]);
            return;
        }

        if (!$this->smsService->isValidPhoneNumber($notifiable->phone)) {
            Log::warning('SMS notification skipped: Invalid phone number', [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id,
                'phone' => $notifiable->phone,
                'notification_type' => get_class($notification),
            ]);
            return;
        }

        try {
            // Check if notification has a custom toSms method
            if (method_exists($notification, 'toSms')) {
                $result = $notification->toSms($notifiable);
            } else {
                // Fallback to a generic SMS message
                $message = $this->buildGenericMessage($notification, $notifiable);
                $result = $this->smsService->sendSms($notifiable->phone, $message);
            }

            if ($result['success']) {
                Log::info('SMS notification sent successfully', [
                    'notifiable_type' => get_class($notifiable),
                    'notifiable_id' => $notifiable->id,
                    'phone' => $notifiable->phone,
                    'notification_type' => get_class($notification),
                ]);
            } else {
                Log::error('SMS notification failed', [
                    'notifiable_type' => get_class($notifiable),
                    'notifiable_id' => $notifiable->id,
                    'phone' => $notifiable->phone,
                    'notification_type' => get_class($notification),
                    'error' => $result['message'],
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('SMS notification exception', [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id,
                'phone' => $notifiable->phone,
                'notification_type' => get_class($notification),
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'SMS notification failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build a generic SMS message from notification data.
     */
    private function buildGenericMessage(Notification $notification, $notifiable): string
    {
        $data = $notification->toArray($notifiable);
        
        $message = $data['title'] ?? 'Notification';
        
        if (isset($data['message'])) {
            $message .= ': ' . $data['message'];
        }

        // Truncate message if too long (SMS limit is typically 160 characters)
        if (strlen($message) > 160) {
            $message = substr($message, 0, 157) . '...';
        }

        return $message;
    }
}