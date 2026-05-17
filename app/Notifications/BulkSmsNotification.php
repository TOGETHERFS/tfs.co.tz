<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BulkSmsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $subject;
    protected $message;
    protected $channels;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $subject, string $message, array $channels = ['database'])
    {
        $this->subject = $subject;
        $this->message = $message;
        $this->channels = $channels;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return $this->channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->message)
            ->line('Thank you for using our SMS service!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'bulk_sms_notification',
            'title' => $this->subject,
            'message' => $this->message,
            'data' => [
                'sent_at' => now(),
                'channels' => $this->channels,
            ],
            'priority' => 'normal',
            'icon' => 'bell',
            'color' => 'blue'
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}