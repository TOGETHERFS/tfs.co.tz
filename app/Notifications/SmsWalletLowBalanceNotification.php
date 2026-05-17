<?php

namespace App\Notifications;

use App\Models\SmsWallet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class SmsWalletLowBalanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $wallet;

    /**
     * Create a new notification instance.
     */
    public function __construct(SmsWallet $wallet)
    {
        $this->wallet = $wallet;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['database'];
        
        // Add email if enabled in wallet settings
        if ($this->wallet->email_notifications_enabled) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SMS Wallet Low Balance Alert')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your SMS wallet balance is running low.')
            ->line('Current Balance: ' . number_format($this->wallet->balance) . ' credits')
            ->line('Low Balance Threshold: ' . number_format($this->wallet->low_balance_threshold) . ' credits')
            ->line('To avoid service interruption, please top up your SMS wallet.')
            ->action('Top Up Now', url('/sms-topup'))
            ->line('Thank you for using our SMS service!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'sms_wallet_low_balance',
            'title' => 'SMS Wallet Low Balance',
            'message' => 'Your SMS wallet balance (' . number_format($this->wallet->balance) . ' credits) is below the threshold (' . number_format($this->wallet->low_balance_threshold) . ' credits).',
            'data' => [
                'wallet_id' => $this->wallet->id,
                'current_balance' => $this->wallet->balance,
                'threshold' => $this->wallet->low_balance_threshold,
                'tenant_id' => $this->wallet->tenant_id,
            ],
            'action_url' => url('/sms-topup'),
            'action_text' => 'Top Up Now',
            'priority' => 'high',
            'icon' => 'warning',
            'color' => 'orange'
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