<?php

namespace App\Notifications;

use App\Models\SenderId;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SenderIdStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $senderId;
    protected $previousStatus;

    /**
     * Create a new notification instance.
     */
    public function __construct(SenderId $senderId, string $previousStatus = null)
    {
        $this->senderId = $senderId;
        $this->previousStatus = $previousStatus;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['database'];
        
        // Add email for important status changes
        if (in_array($this->senderId->status, ['approved', 'rejected', 'suspended'])) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $message = new MailMessage();
        
        switch ($this->senderId->status) {
            case 'approved':
                $message->subject('Sender ID Approved')
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line('Great news! Your sender ID application has been approved.')
                    ->line('Sender ID: ' . $this->senderId->sender_id)
                    ->line('Business Name: ' . $this->senderId->business_name)
                    ->line('You can now use this sender ID for your SMS campaigns.')
                    ->action('View Sender IDs', url('/sender-ids'));
                break;
                
            case 'rejected':
                $message->subject('Sender ID Rejected')
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line('Unfortunately, your sender ID application has been rejected.')
                    ->line('Sender ID: ' . $this->senderId->sender_id)
                    ->line('Business Name: ' . $this->senderId->business_name)
                    ->line('Reason: ' . ($this->senderId->rejection_reason ?? 'Please contact support for details.'))
                    ->line('You can submit a new application with the required corrections.')
                    ->action('Apply Again', url('/sender-ids/create'));
                break;
                
            case 'suspended':
                $message->subject('Sender ID Suspended')
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line('Your sender ID has been suspended.')
                    ->line('Sender ID: ' . $this->senderId->sender_id)
                    ->line('This sender ID can no longer be used for SMS campaigns.')
                    ->line('Please contact support for more information.')
                    ->action('Contact Support', url('/support'));
                break;
                
            default:
                $message->subject('Sender ID Status Updated')
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line('Your sender ID status has been updated.')
                    ->line('Sender ID: ' . $this->senderId->sender_id)
                    ->line('New Status: ' . ucfirst($this->senderId->status))
                    ->action('View Details', url('/sender-ids/' . $this->senderId->id));
        }
        
        return $message->line('Thank you for using our SMS service!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        $statusMessages = [
            'pending' => 'Your sender ID application is under review.',
            'approved' => 'Your sender ID has been approved and is now active.',
            'rejected' => 'Your sender ID application has been rejected.',
            'suspended' => 'Your sender ID has been suspended.',
        ];

        $colors = [
            'pending' => 'blue',
            'approved' => 'green',
            'rejected' => 'red',
            'suspended' => 'orange',
        ];

        $icons = [
            'pending' => 'clock',
            'approved' => 'check-circle',
            'rejected' => 'x-circle',
            'suspended' => 'pause-circle',
        ];

        $priorities = [
            'pending' => 'normal',
            'approved' => 'normal',
            'rejected' => 'high',
            'suspended' => 'high',
        ];

        return [
            'type' => 'sender_id_status_change',
            'title' => 'Sender ID Status: ' . ucfirst($this->senderId->status),
            'message' => $statusMessages[$this->senderId->status] . ' Sender ID: ' . $this->senderId->sender_id,
            'data' => [
                'sender_id' => $this->senderId->id,
                'sender_id_name' => $this->senderId->sender_id,
                'business_name' => $this->senderId->business_name,
                'status' => $this->senderId->status,
                'previous_status' => $this->previousStatus,
                'rejection_reason' => $this->senderId->rejection_reason,
                'approved_by' => $this->senderId->approved_by,
                'approved_at' => $this->senderId->approved_at,
                'tenant_id' => $this->senderId->tenant_id,
            ],
            'action_url' => url('/sender-ids/' . $this->senderId->id),
            'action_text' => 'View Details',
            'priority' => $priorities[$this->senderId->status],
            'icon' => $icons[$this->senderId->status],
            'color' => $colors[$this->senderId->status]
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