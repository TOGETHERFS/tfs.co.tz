<?php

namespace App\Notifications;

use App\Models\SmsWallet;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklySmsUsageReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $wallet;
    protected $usage;
    protected $weekStart;
    protected $weekEnd;

    /**
     * Create a new notification instance.
     */
    public function __construct(SmsWallet $wallet, int $usage, Carbon $weekStart, Carbon $weekEnd)
    {
        $this->wallet = $wallet;
        $this->usage = $usage;
        $this->weekStart = $weekStart;
        $this->weekEnd = $weekEnd;
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
        $dailyAverage = $this->usage > 0 ? round($this->usage / 7) : 0;
        $previousWeekUsage = $this->getPreviousWeekUsage();
        $changePercent = $previousWeekUsage > 0 ? round((($this->usage - $previousWeekUsage) / $previousWeekUsage) * 100) : 0;
        
        return (new MailMessage)
            ->subject('Weekly SMS Usage Report - ' . $this->weekStart->format('M d') . ' to ' . $this->weekEnd->format('M d, Y'))
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Here is your weekly SMS usage report:')
            ->line('**Week:** ' . $this->weekStart->format('M d') . ' - ' . $this->weekEnd->format('M d, Y'))
            ->line('**Total SMS Credits Used:** ' . number_format($this->usage))
            ->line('**Daily Average:** ' . number_format($dailyAverage) . ' credits')
            ->line('**Current Wallet Balance:** ' . number_format($this->wallet->balance) . ' credits')
            ->when($previousWeekUsage > 0, function ($message) use ($changePercent) {
                $trend = $changePercent > 0 ? 'increase' : 'decrease';
                return $message->line('**Change from Previous Week:** ' . abs($changePercent) . '% ' . $trend);
            })
            ->action('View Detailed Analytics', url('/sms-wallet/analytics'))
            ->line('Thank you for using our SMS service!');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        $dailyAverage = $this->usage > 0 ? round($this->usage / 7) : 0;
        $previousWeekUsage = $this->getPreviousWeekUsage();
        $changePercent = $previousWeekUsage > 0 ? round((($this->usage - $previousWeekUsage) / $previousWeekUsage) * 100) : 0;

        return [
            'type' => 'weekly_sms_usage_report',
            'title' => 'Weekly SMS Usage Report',
            'message' => 'You used ' . number_format($this->usage) . ' SMS credits this week (' . $this->weekStart->format('M d') . ' - ' . $this->weekEnd->format('M d') . '). Daily average: ' . number_format($dailyAverage) . ' credits.',
            'data' => [
                'wallet_id' => $this->wallet->id,
                'usage' => $this->usage,
                'week_start' => $this->weekStart->format('Y-m-d'),
                'week_end' => $this->weekEnd->format('Y-m-d'),
                'daily_average' => $dailyAverage,
                'current_balance' => $this->wallet->balance,
                'previous_week_usage' => $previousWeekUsage,
                'change_percent' => $changePercent,
                'tenant_id' => $this->wallet->tenant_id,
            ],
            'action_url' => url('/sms-wallet/analytics'),
            'action_text' => 'View Analytics',
            'priority' => 'low',
            'icon' => 'chart-line',
            'color' => 'indigo'
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
     * Get usage for the previous week.
     */
    protected function getPreviousWeekUsage(): int
    {
        $previousWeekStart = $this->weekStart->copy()->subWeek();
        $previousWeekEnd = $this->weekEnd->copy()->subWeek();
        
        return $this->wallet->transactions()
            ->where('type', 'debit')
            ->whereBetween('created_at', [$previousWeekStart, $previousWeekEnd])
            ->sum('amount');
    }
}