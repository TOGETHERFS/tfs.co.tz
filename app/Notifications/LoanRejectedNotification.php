<?php

namespace App\Notifications;

use App\Models\Loan;
use App\Services\BeemSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $loan;
    public $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(Loan $loan, string $reason = null)
    {
        $this->loan = $loan;
        $this->reason = $reason;
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
                    ->subject('Loan Application Rejected')
                    ->greeting("Dear {$notifiable->first_name},")
                    ->line('We regret to inform you that your loan application has been rejected.')
                    ->line("Loan Number: {$this->loan->loan_number}")
                    ->line("Applied Amount: " . number_format($this->loan->principal_amount, 2) . " {$this->loan->currency}");

        if ($this->reason) {
            $message->line("Reason: {$this->reason}");
        }

        $message->line('You may reapply for a loan after addressing the issues mentioned above.')
                ->line('If you have any questions, please contact our customer service team.')
                ->line('Thank you for choosing our services.');

        return $message;
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $amount = number_format($this->loan->principal_amount, 2);
        $message = "Dear {$notifiable->first_name}, your loan application #{$this->loan->loan_number} for {$amount} {$this->loan->currency} has been rejected.";
        
        if ($this->reason) {
            $message .= " Reason: {$this->reason}.";
        }
        
        $message .= " You may reapply after addressing the issues. Contact us for assistance.";
        
        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'loan_rejected',
            'loan_id' => $this->loan->id,
            'loan_number' => $this->loan->loan_number,
            'amount' => $this->loan->principal_amount,
            'currency' => $this->loan->currency,
            'reason' => $this->reason,
            'message' => "Your loan application #{$this->loan->loan_number} has been rejected.",
            'action_url' => route('loans.show', $this->loan->id),
        ];
    }
}