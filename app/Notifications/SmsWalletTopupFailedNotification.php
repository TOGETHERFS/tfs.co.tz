<?php

namespace App\Notifications;

use App\Models\SmsTopup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SmsWalletTopupFailedNotification extends Notification implements ShouldQueue
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
            ->subject('SMS Wallet Top-up Failed')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Unfortunately, your SMS wallet top-up payment has failed.')
            ->line('Transaction ID: ' . $this->topup->transaction_id)
            ->line('Amount: TZS ' . number_format($this->topup->amount, 2))
            ->line('Failure Reason: ' . ($this->topup->failure_reason ?? 'Payment processing error'))
            ->line('Please try again or contact support if the problem persists.')
            ->action('Try Again', url('/sms-topup'))
            ->line('We apologize for any inconvenience.');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'sms_wallet_topup_failed',
            'title' => 'SMS Wallet Top-up Failed',
            'message' => 'Your SMS wallet top-up payment of TZS ' . number_format($this->topup->amount, 2) . ' has failed. ' . ($this->topup->failure_reason ?? 'Please try again.'),
            'data' => [
                'topup_id' => $this->topup->id,
                'transaction_id' => $this->topup->transaction_id,
                'amount' => $this->topup->amount,
                'failure_reason' => $this->topup->failure_reason,
                'tenant_id' => $this->topup->tenant_id,
                'payment_method' => $this->topup->payment_method,
            ],
            'action_url' => url('/sms-topup'),
            'action_text' => 'Try Again',
            'priority' => 'high',
            'icon' => 'x-circle',
            'color' => 'red'
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