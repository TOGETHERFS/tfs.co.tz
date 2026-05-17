<?php

namespace App\Notifications;

use App\Models\Loan;
use App\Services\BeemSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanDisbursedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $loan;

    /**
     * Create a new notification instance.
     */
    public function __construct(Loan $loan)
    {
        $this->loan = $loan;
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
        $firstPayment = $this->loan->schedules()->orderBy('due_date')->first();
        
        return (new MailMessage)
                    ->subject('Loan Disbursed Successfully')
                    ->greeting("Dear {$notifiable->first_name},")
                    ->line('Congratulations! Your loan has been successfully disbursed.')
                    ->line("Loan Number: {$this->loan->loan_number}")
                    ->line("Disbursed Amount: " . number_format($this->loan->principal_amount, 2) . " {$this->loan->currency}")
                    ->line("Interest Rate: {$this->loan->interest_rate}% per {$this->loan->interest_type}")
                    ->line("Loan Term: {$this->loan->loan_term} {$this->loan->loan_term_period}")
                    ->line("Total Repayment: " . number_format($this->loan->total_amount, 2) . " {$this->loan->currency}")
                    ->when($firstPayment, function ($message) use ($firstPayment) {
                        return $message->line("First Payment Due: " . $firstPayment->due_date->format('d M Y'))
                                      ->line("First Payment Amount: " . number_format($firstPayment->amount, 2) . " {$this->loan->currency}");
                    })
                    ->line('Please ensure timely repayments to maintain a good credit history.')
                    ->action('View Loan Details', route('loans.show', $this->loan->id))
                    ->line('Thank you for choosing our services!');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        $amount = number_format($this->loan->principal_amount, 2);
        $firstPayment = $this->loan->schedules()->orderBy('due_date')->first();
        
        $message = "Congratulations {$notifiable->first_name}! Your loan #{$this->loan->loan_number} of {$amount} {$this->loan->currency} has been disbursed.";
        
        if ($firstPayment) {
            $paymentAmount = number_format($firstPayment->amount, 2);
            $message .= " First payment of {$paymentAmount} {$this->loan->currency} is due on " . $firstPayment->due_date->format('d/m/Y') . ".";
        }
        
        $message .= " Ensure timely repayments. Thank you!";
        
        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $firstPayment = $this->loan->schedules()->orderBy('due_date')->first();
        
        return [
            'type' => 'loan_disbursed',
            'loan_id' => $this->loan->id,
            'loan_number' => $this->loan->loan_number,
            'amount' => $this->loan->principal_amount,
            'currency' => $this->loan->currency,
            'total_amount' => $this->loan->total_amount,
            'disbursed_at' => $this->loan->disbursed_at,
            'first_payment_due' => $firstPayment?->due_date,
            'first_payment_amount' => $firstPayment?->amount,
            'message' => "Your loan #{$this->loan->loan_number} has been disbursed successfully.",
            'action_url' => route('loans.show', $this->loan->id),
        ];
    }
}