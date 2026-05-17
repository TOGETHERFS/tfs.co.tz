<?php

namespace App\Notifications;

use App\Models\SmsWallet;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailySmsUsageSummaryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $wallet;
    protected $usage;
    protected $date;

    /**
     * Create a new notification instance.
     */
    public function __construct(SmsWallet $wallet, int $usage, Carbon $date)
    {
        $this->wallet = $wallet;
        $this->usage = $usage;
        $this->date = $date;
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
            ->subject('Daily SMS Usage Summary - ' . $this->date->format('M d, Y'))
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Here is your daily SMS usage summary for ' . $this->date->format('F d, Y') . ':')
            ->line('SMS Credits Used: ' . number_format($this->usage))
            ->line('Current Wallet Balance: ' . number_format($this->wallet->balance) . ' credits')
            ->line('Average Daily Usage (Last 7 days): ' . number_format($this->getAverageUsage()) . ' credits')
            ->action('View Detailed Report', url('/sms-wallet/reports'))
            ->line('Thank you for using our SMS service!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'daily_sms_usage_summary',
            'title' => 'Daily SMS Usage Summary',
            'message' => 'You used ' . number_format($this->usage) . ' SMS credits on ' . $this->date->format('M d, Y') . '. Current balance: ' . number_format($this->wallet->balance) . ' credits.',
            'data' => [
                'wallet_id' => $this->wallet->id,
                'usage' => $this->usage,
                'date' => $this->date->format('Y-m-d'),
                'current_balance' => $this->wallet->balance,
                'average_usage' => $this->getAverageUsage(),
                'tenant_id' => $this->wallet->tenant_id,
            ],
            'action_url' => url('/sms-wallet/reports'),
            'action_text' => 'View Report',
            'priority' => 'low',
            'icon' => 'chart-bar',
            'color' => 'gray'
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * Calculate average usage for the last 7 days.
     */
    protected function getAverageUsage(): int
    {
        $sevenDaysAgo = $this->date->copy()->subDays(6);
        
        $totalUsage = $this->wallet->transactions()
            ->where('type', 'debit')
            ->whereBetween('created_at', [$sevenDaysAgo->startOfDay(), $this->date->endOfDay()])
            ->sum('amount');
            
        return (int) round($totalUsage / 7);
    }
}