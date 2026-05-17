<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class CustomNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $notificationData;
    protected $channels;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $notificationData, array $channels = ['database'])
    {
        $this->notificationData = $notificationData;
        $this->channels = $channels;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mailMessage = (new MailMessage)
            ->subject($this->notificationData['subject'] ?? 'Notification')
            ->line($this->notificationData['message'] ?? '');

        // Add action button if provided
        if (isset($this->notificationData['action_url']) && isset($this->notificationData['action_text'])) {
            $mailMessage->action($this->notificationData['action_text'], $this->notificationData['action_url']);
        }

        return $mailMessage;
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->notificationData['title'] ?? $this->notificationData['subject'] ?? 'Notification',
            'message' => $this->notificationData['message'] ?? '',
            'type' => $this->notificationData['type'] ?? 'general',
            'icon' => $this->notificationData['icon'] ?? 'bell',
            'color' => $this->notificationData['color'] ?? 'blue',
            'priority' => $this->notificationData['priority'] ?? 'medium',
            'action_url' => $this->notificationData['action_url'] ?? null,
            'action_text' => $this->notificationData['action_text'] ?? null,
            'data' => $this->notificationData['data'] ?? [],
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return $this->notificationData;
    }
}