<?php

namespace App\Notifications;

use App\Models\LoanSchedule;
use App\Services\BeemSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $schedule;
    protected $reminderType;

    /**
     * Create a new notification instance.
     */
    public function __construct(LoanSchedule $schedule, string $reminderType = 'upcoming')
    {
        $this->schedule = $schedule;
        $this->reminderType = $reminderType; // 'upcoming', 'due_today', 'overdue'
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['database'];
        
        if ($notifiable->email) {
            $channels[] = 'mail';
        }
        
        if ($notifiable->phone) {
            $channels[] = 'sms';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $subject = $this->getEmailSubject();
        $greeting = 'Dear ' . $notifiable->full_name . ',';
        $message = $this->getEmailMessage();

        return (new MailMessage)
                    ->subject($subject)
                    ->greeting($greeting)
                    ->line($message)
                    ->line('Payment Details:')
                    ->line('• Loan Number: ' . $this->schedule->loan->loan_number)
                    ->line('• Due Date: ' . $this->schedule->due_date->format('d M Y'))
                    ->line('• Amount Due: TZS ' . number_format($this->schedule->total_amount, 2))
                    ->line('• Outstanding Balance: TZS ' . number_format($this->schedule->loan->outstanding_balance, 2))
                    ->when($this->reminderType === 'overdue', function ($mail) {
                        return $mail->line('⚠️ This payment is overdue. Please make your payment immediately to avoid additional penalties.');
                    })
                    ->line('Please make your payment on time to maintain a good credit record.')
                    ->line('Thank you for your cooperation.')
                    ->salutation('Best regards, ' . config('app.name'));
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms($notifiable)
    {
        $smsService = app(BeemSmsService::class);
        
        return $smsService->sendLoanReminder(
            $notifiable->phone,
            $notifiable->full_name,
            $this->schedule->total_amount,
            $this->schedule->due_date->format('d M Y')
        );
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'payment_reminder',
            'reminder_type' => $this->reminderType,
            'title' => $this->getNotificationTitle(),
            'message' => $this->getNotificationMessage(),
            'loan_id' => $this->schedule->loan_id,
            'schedule_id' => $this->schedule->id,
            'loan_number' => $this->schedule->loan->loan_number,
            'due_date' => $this->schedule->due_date,
            'amount' => $this->schedule->total_amount,
            'action_url' => route('loans.show', $this->schedule->loan),
        ];
    }

    /**
     * Get email subject based on reminder type.
     */
    private function getEmailSubject(): string
    {
        return match ($this->reminderType) {
            'upcoming' => 'Payment Reminder - Due Soon',
            'due_today' => 'Payment Due Today',
            'overdue' => 'Overdue Payment Notice',
            default => 'Payment Reminder',
        };
    }

    /**
     * Get email message based on reminder type.
     */
    private function getEmailMessage(): string
    {
        return match ($this->reminderType) {
            'upcoming' => 'This is a friendly reminder that your loan payment is due soon.',
            'due_today' => 'Your loan payment is due today. Please make your payment to avoid late fees.',
            'overdue' => 'Your loan payment is overdue. Please make your payment immediately to avoid additional penalties.',
            default => 'This is a reminder about your upcoming loan payment.',
        };
    }

    /**
     * Get notification title for database storage.
     */
    private function getNotificationTitle(): string
    {
        return match ($this->reminderType) {
            'upcoming' => 'Payment Due Soon',
            'due_today' => 'Payment Due Today',
            'overdue' => 'Payment Overdue',
            default => 'Payment Reminder',
        };
    }

    /**
     * Get notification message for database storage.
     */
    private function getNotificationMessage(): string
    {
        $amount = number_format($this->schedule->total_amount, 2);
        $dueDate = $this->schedule->due_date->format('d M Y');
        
        return match ($this->reminderType) {
            'upcoming' => "Your payment of TZS {$amount} is due on {$dueDate}.",
            'due_today' => "Your payment of TZS {$amount} is due today.",
            'overdue' => "Your payment of TZS {$amount} was due on {$dueDate} and is now overdue.",
            default => "Payment reminder for TZS {$amount} due on {$dueDate}.",
        };
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType($notifiable): string
    {
        return 'payment_reminder';
    }
}