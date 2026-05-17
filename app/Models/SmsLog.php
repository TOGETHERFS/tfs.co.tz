<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'sender_id',
        'message',
        'recipients',
        'status',
        'provider_request_id',
        'provider_response',
        'error',
        'sent_at',
        'delivered_at',
        'provider',
        'cost',
        'campaign_id',
        'message_type',
        'recipient_count',
        'delivery_reports',
        'failed_at',
        'retry_count',
        'scheduled_at'
    ];

    protected $casts = [
        'recipients' => 'array',
        'provider_response' => 'array',
        'delivery_reports' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'cost' => 'integer',
        'recipient_count' => 'integer',
        'retry_count' => 'integer'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';
    const STATUS_SCHEDULED = 'scheduled';

    const MESSAGE_TYPE_SINGLE = 'single';
    const MESSAGE_TYPE_BULK = 'bulk';
    const MESSAGE_TYPE_CAMPAIGN = 'campaign';
    const MESSAGE_TYPE_OTP = 'otp';
    const MESSAGE_TYPE_NOTIFICATION = 'notification';

    /**
     * Get the tenant that owns the SMS log.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who sent the SMS.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the campaign this SMS belongs to.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SmsCampaign::class, 'campaign_id');
    }

    /**
     * Scope to get sent SMS.
     */
    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope to get delivered SMS.
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    /**
     * Scope to get failed SMS.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to get pending SMS.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get SMS by provider.
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope to get SMS by message type.
     */
    public function scopeByMessageType($query, string $messageType)
    {
        return $query->where('message_type', $messageType);
    }

    /**
     * Check if SMS is sent.
     */
    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    /**
     * Check if SMS is delivered.
     */
    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    /**
     * Check if SMS failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if SMS is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Mark SMS as sent.
     */
    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now()
        ]);
    }

    /**
     * Mark SMS as delivered.
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now()
        ]);
    }

    /**
     * Mark SMS as failed.
     */
    public function markAsFailed(string $error = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failed_at' => now(),
            'error' => $error
        ]);
    }

    /**
     * Increment retry count.
     */
    public function incrementRetry(): void
    {
        $this->increment('retry_count');
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_SENT => 'info',
            self::STATUS_DELIVERED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_SCHEDULED => 'secondary',
            default => 'primary'
        };
    }

    /**
     * Get formatted cost.
     */
    public function getFormattedCostAttribute(): string
    {
        return $this->cost . ' credits';
    }
}