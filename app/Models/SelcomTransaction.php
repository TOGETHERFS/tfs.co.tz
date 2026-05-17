<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class SelcomTransaction extends Model
{
    protected $fillable = [
        'transaction_id',
        'reference',
        'till_number',
        'amount',
        'currency',
        'status',
        'payment_method',
        'customer_phone',
        'customer_email',
        'description',
        'request_payload',
        'response_payload',
        'callback_data',
        'selcom_order_id',
        'selcom_transaction_id',
        'payment_date',
        'callback_received_at',
        'failure_reason',
        'retry_count',
        'repayment_id',
        'loan_id',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'callback_data' => 'array',
        'payment_date' => 'datetime',
        'callback_received_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    protected $dates = [
        'payment_date',
        'callback_received_at',
        'created_at',
        'updated_at',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_PROCESSING = 'processing';

    // Payment method constants
    const METHOD_TILL = 'till';
    const METHOD_WALLET = 'wallet';
    const METHOD_QR = 'qr';
    const METHOD_MOBILE_MONEY = 'mobile_money';

    /**
     * Get the repayment associated with this transaction
     */
    public function repayment(): BelongsTo
    {
        return $this->belongsTo(Repayment::class);
    }

    /**
     * Get the loan associated with this transaction
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Get the user who initiated this transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for failed transactions
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope for transactions by TILL number
     */
    public function scopeByTillNumber($query, $tillNumber)
    {
        return $query->where('till_number', $tillNumber);
    }

    /**
     * Scope for transactions by phone number
     */
    public function scopeByPhone($query, $phone)
    {
        return $query->where('customer_phone', $phone);
    }

    /**
     * Scope for transactions within date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Check if transaction is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if transaction is failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Mark transaction as completed
     */
    public function markAsCompleted($paymentDate = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'payment_date' => $paymentDate ?? now(),
        ]);
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed($reason = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Increment retry count
     */
    public function incrementRetryCount(): void
    {
        $this->increment('retry_count');
    }

    /**
     * Update callback data
     */
    public function updateCallbackData(array $data): void
    {
        $this->update([
            'callback_data' => $data,
            'callback_received_at' => now(),
        ]);
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            self::STATUS_COMPLETED => 'badge-success',
            self::STATUS_PENDING => 'badge-warning',
            self::STATUS_PROCESSING => 'badge-info',
            self::STATUS_FAILED => 'badge-danger',
            self::STATUS_CANCELLED => 'badge-secondary',
            default => 'badge-light',
        };
    }

    /**
     * Get human readable status
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Unknown',
        };
    }

    /**
     * Generate unique transaction ID
     */
    public static function generateTransactionId(): string
    {
        return 'SELCOM_' . strtoupper(uniqid()) . '_' . time();
    }

    /**
     * Create transaction record
     */
    public static function createTransaction(array $data): self
    {
        return self::create(array_merge($data, [
            'transaction_id' => $data['transaction_id'] ?? self::generateTransactionId(),
            'status' => $data['status'] ?? self::STATUS_PENDING,
            'currency' => $data['currency'] ?? 'TZS',
        ]));
    }
}
