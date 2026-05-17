<?php

namespace App\Notifications;

use App\Models\SmsTopup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SmsWalletTopupSuccessNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $topup;

    /**
     * Create a new notification instance.
     */
    public function __construct(SmsTopup $topup)
    {
        $this->topup = $topup;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['database'];
        
        // Add email if enabled
        if ($this->topup->tenant->smsWallet && $this->topup->tenant->smsWallet->email_notifications_enabled) {
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
            ->subject('SMS Wallet Top-up Successful')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your SMS wallet has been successfully topped up!')
            ->line('Transaction ID: ' . $this->topup->transaction_id)
            ->line('Amount Paid: TZS ' . number_format($this->topup->amount, 2))
            ->line('Credits Added: ' . number_format($this->topup->credits_purchased) . ' SMS credits')
            ->line('New Balance: ' . number_format($this->topup->tenant->smsWallet->balance ?? 0) . ' credits')
            ->action('View Wallet', url('/sms-wallet'))
            ->line('Thank you for your payment!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'sms_wallet_topup_success',
            'title' => 'SMS Wallet Top-up Successful',
            'message' => 'Your SMS wallet has been topped up with ' . number_format($this->topup->credits_purchased) . ' credits for TZS ' . number_format($this->topup->amount, 2) . '.',
            'data' => [
                'topup_id' => $this->topup->id,
                'transaction_id' => $this->topup->transaction_id,
                'amount' => $this->topup->amount,
                'credits_purchased' => $this->topup->credits_purchased,
                'tenant_id' => $this->topup->tenant_id,
                'payment_method' => $this->topup->payment_method,
            ],
            'action_url' => url('/sms-wallet'),
            'action_text' => 'View Wallet',
            'priority' => 'normal',
            'icon' => 'check-circle',
            'color' => 'green'
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