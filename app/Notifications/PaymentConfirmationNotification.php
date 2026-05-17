<?php

namespace App\Notifications;

use App\Models\Repayment;
use App\Services\BeemSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $repayment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Repayment $repayment)
    {
        $this->repayment = $repayment;
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
                    ->subject('Payment Confirmation')
                    ->greeting('Dear ' . $notifiable->full_name . ',')
                    ->line('We have successfully received your payment. Thank you!')
                    ->line('Payment Details:')
                    ->line('• Receipt Number: ' . $this->repayment->receipt_number)
                    ->line('• Amount Paid: TZS ' . number_format($this->repayment->amount, 2))
                    ->line('• Payment Date: ' . $this->repayment->payment_date->format('d M Y'))
                    ->line('• Payment Method: ' . ucfirst(str_replace('_', ' ', $this->repayment->payment_method)))
                    ->line('• Loan Number: ' . $this->repayment->loan->loan_number)
                    ->line('• Remaining Balance: TZS ' . number_format($this->repayment->loan->outstanding_balance, 2))
                    ->when($this->repayment->loan->outstanding_balance <= 0, function ($mail) {
                        return $mail->line('🎉 Congratulations! You have fully paid off your loan.');
                    })
                    ->line('Keep this receipt for your records.')
                    ->line('Thank you for your timely payment!')
                    ->salutation('Best regards, ' . config('app.name'));
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms($notifiable)
    {
        $smsService = app(BeemSmsService::class);
        
        return $smsService->sendPaymentConfirmation(
            $notifiable->phone,
            $notifiable->full_name,
            $this->repayment->amount,
            $this->repayment->receipt_number
        );
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'payment_confirmation',
            'title' => 'Payment Received',
            'message' => 'Your payment of TZS ' . number_format($this->repayment->amount, 2) . ' has been received successfully.',
            'repayment_id' => $this->repayment->id,
            'receipt_number' => $this->repayment->receipt_number,
            'amount' => $this->repayment->amount,
            'payment_date' => $this->repayment->payment_date,
            'loan_id' => $this->repayment->loan_id,
            'loan_number' => $this->repayment->loan->loan_number,
            'remaining_balance' => $this->repayment->loan->outstanding_balance,
            'action_url' => route('repayments.show', $this->repayment),
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType($notifiable): string
    {
        return 'payment_confirmation';
    }
}