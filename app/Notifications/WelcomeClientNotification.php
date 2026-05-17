<?php

namespace App\Notifications;

use App\Models\Client;
use App\Services\BeemSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeClientNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $client;
    public $temporaryPassword;

    /**
     * Create a new notification instance.
     */
    public function __construct(Client $client, string $temporaryPassword = null)
    {
        $this->client = $client;
        $this->temporaryPassword = $temporaryPassword;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Add email if client has email
        if ($notifiable->email) {
            $channels[] = 'mail';
        }

        // Add SMS if client has phone
        if ($notifiable->phone) {
            $channels[] = 'sms';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
                    ->subject('Welcome to Our Microfinance Services')
                    ->greeting("Dear {$notifiable->first_name} {$notifiable->last_name},")
                    ->line('Welcome to our microfinance platform! Your account has been successfully created.')
                    ->line("Client ID: {$this->client->client_number}")
                    ->line("Registration Date: " . $this->client->created_at->format('d M Y'));

        if ($this->temporaryPassword) {
            $message->line('Your account credentials:')
                   ->line("Email: {$notifiable->email}")
                   ->line("Temporary Password: {$this->temporaryPassword}")
                   ->line('Please change your password after your first login for security.');
        }

        $message->line('Our services include:')
               ->line('• Quick and easy loan applications')
               ->line('• Flexible repayment options')
               ->line('• Competitive interest rates')
               ->line('• 24/7 customer support')
               ->line('We are committed to helping you achieve your financial goals.')
               ->action('Access Your Account', url('/login'))
               ->line('If you have any questions, please don\'t hesitate to contact our customer service team.')
               ->line('Thank you for choosing our services!');

        return $message;
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $message = "Welcome {$notifiable->first_name}! Your account has been created successfully. Client ID: {$this->client->client_number}.";
        
        if ($this->temporaryPassword) {
            $message .= " Your temporary password is: {$this->temporaryPassword}. Please change it after login.";
        }
        
        $message .= " We're here to help you achieve your financial goals. Contact us for assistance.";
        
        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'welcome_client',
            'client_id' => $this->client->id,
            'client_number' => $this->client->client_number,
            'registration_date' => $this->client->created_at,
            'has_temporary_password' => !is_null($this->temporaryPassword),
            'message' => "Welcome to our microfinance platform! Your account has been successfully created.",
            'action_url' => url('/login'),
        ];
    }
}