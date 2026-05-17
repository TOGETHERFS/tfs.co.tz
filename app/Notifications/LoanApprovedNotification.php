<?php

namespace App\Notifications;

use App\Models\Loan;
use App\Services\BeemSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $loan;

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
        return (new MailMessage)
                    ->subject('Loan Application Approved')
                    ->greeting('Dear ' . $notifiable->full_name . ',')
                    ->line('Congratulations! Your loan application has been approved.')
                    ->line('Loan Details:')
                    ->line('• Loan Number: ' . $this->loan->loan_number)
                    ->line('• Amount: TZS ' . number_format($this->loan->principal, 2))
                    ->line('• Interest Rate: ' . $this->loan->interest_rate . '%')
                    ->line('• Term: ' . $this->loan->term . ' months')
                    ->line('• Monthly Payment: TZS ' . number_format($this->loan->monthly_payment, 2))
                    ->line('Please visit our office to complete the disbursement process.')
                    ->line('Thank you for choosing our services!')
                    ->salutation('Best regards, ' . config('app.name'));
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms($notifiable)
    {
        $smsService = app(BeemSmsService::class);
        
        return $smsService->sendLoanApproval(
            $notifiable->phone,
            $notifiable->full_name,
            $this->loan->principal,
            $this->loan->loan_number
        );
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'loan_approved',
            'title' => 'Loan Application Approved',
            'message' => 'Your loan application for TZS ' . number_format($this->loan->principal, 2) . ' has been approved.',
            'loan_id' => $this->loan->id,
            'loan_number' => $this->loan->loan_number,
            'amount' => $this->loan->principal,
            'action_url' => route('loans.show', $this->loan),
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType($notifiable): string
    {
        return 'loan_approved';
    }
}