<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $invoice;

    /**
     * Create a new notification instance.
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject("New Invoice Generated - {$this->invoice->invoice_number}")
                    ->greeting("Dear {$notifiable->name},")
                    ->line('A new invoice has been generated for your subscription.')
                    ->line("Invoice Number: {$this->invoice->invoice_number}")
                    ->line("Amount: " . number_format($this->invoice->amount, 2) . " {$this->invoice->currency}")
                    ->line("Tax: " . number_format($this->invoice->tax_amount, 2) . " {$this->invoice->currency}")
                    ->line("Total Amount: " . number_format($this->invoice->total_amount, 2) . " {$this->invoice->currency}")
                    ->line("Due Date: " . $this->invoice->due_date->format('d M Y'))
                    ->line("Billing Period: " . $this->invoice->billing_period_start->format('d M Y') . " - " . $this->invoice->billing_period_end->format('d M Y'))
                    ->line('Please ensure payment is made before the due date to avoid service interruption.')
                    ->action('View Invoice', route('billing.invoices.show', $this->invoice->id))
                    ->action('Pay Now', route('billing.invoices.pay', $this->invoice->id))
                    ->line('Thank you for your continued business!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'invoice_generated',
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'amount' => $this->invoice->amount,
            'tax_amount' => $this->invoice->tax_amount,
            'total_amount' => $this->invoice->total_amount,
            'currency' => $this->invoice->currency,
            'due_date' => $this->invoice->due_date,
            'billing_period_start' => $this->invoice->billing_period_start,
            'billing_period_end' => $this->invoice->billing_period_end,
            'message' => "New invoice {$this->invoice->invoice_number} has been generated.",
            'action_url' => route('billing.invoices.show', $this->invoice->id),
        ];
    }
}