<?php

namespace App\Notifications;

use App\Models\SmsWallet;
use App\Models\SmsTopup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SmsWalletAutoTopupNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $wallet;
    protected $topup;

    /**
     * Create a new notification instance.
     */
    public function __construct(SmsWallet $wallet, SmsTopup $topup)
    {
        $this->wallet = $wallet;
        $this->topup = $topup;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['database'];
        
        // Add email if enabled
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
            ->subject('Automatic SMS Wallet Top-up Initiated')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your SMS wallet balance was low, so we automatically initiated a top-up.')
            ->line('Previous Balance: ' . number_format($this->wallet->balance - $this->topup->credits_purchased) . ' credits')
            ->line('Top-up Amount: TZS ' . number_format($this->topup->amount, 2))
            ->line('Credits Added: ' . number_format($this->topup->credits_purchased) . ' SMS credits')
            ->line('New Balance: ' . number_format($this->wallet->balance) . ' credits')
            ->line('Transaction ID: ' . $this->topup->transaction_id)
            ->action('View Wallet', url('/sms-wallet'))
            ->line('Auto top-up helps ensure uninterrupted SMS service.');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'sms_wallet_auto_topup',
            'title' => 'Automatic Top-up Initiated',
            'message' => 'Your SMS wallet was automatically topped up with ' . number_format($this->topup->credits_purchased) . ' credits for TZS ' . number_format($this->topup->amount, 2) . ' due to low balance.',
            'data' => [
                'wallet_id' => $this->wallet->id,
                'topup_id' => $this->topup->id,
                'transaction_id' => $this->topup->transaction_id,
                'amount' => $this->topup->amount,
                'credits_purchased' => $this->topup->credits_purchased,
                'previous_balance' => $this->wallet->balance - $this->topup->credits_purchased,
                'new_balance' => $this->wallet->balance,
                'tenant_id' => $this->wallet->tenant_id,
            ],
            'action_url' => url('/sms-wallet'),
            'action_text' => 'View Wallet',
            'priority' => 'normal',
            'icon' => 'refresh',
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