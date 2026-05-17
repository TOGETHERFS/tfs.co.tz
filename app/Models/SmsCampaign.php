<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'name',
        'message',
        'sender_id',
        'recipients',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'total_recipients',
        'sent_count',
        'delivered_count',
        'failed_count',
        'estimated_cost',
        'actual_cost',
        'respect_dnd',
        'settings'
    ];

    protected $casts = [
        'recipients' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_recipients' => 'integer',
        'sent_count' => 'integer',
        'delivered_count' => 'integer',
        'failed_count' => 'integer',
        'estimated_cost' => 'integer',
        'actual_cost' => 'integer',
        'respect_dnd' => 'boolean',
        'settings' => 'array'
    ];

    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_SENDING = 'sending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_PAUSED = 'paused';

    /**
     * Get the tenant that owns the campaign.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who created the campaign.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get SMS logs for this campaign.
     */
    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class, 'campaign_id');
    }

    /**
     * Scope to get draft campaigns.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope to get scheduled campaigns.
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    /**
     * Scope to get sending campaigns.
     */
    public function scopeSending($query)
    {
        return $query->where('status', self::STATUS_SENDING);
    }

    /**
     * Scope to get completed campaigns.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to get campaigns ready to send.
     */
    public function scopeReadyToSend($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Check if campaign is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if campaign is scheduled.
     */
    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    /**
     * Check if campaign is sending.
     */
    public function isSending(): bool
    {
        return $this->status === self::STATUS_SENDING;
    }

    /**
     * Check if campaign is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if campaign is failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if campaign is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Check if campaign is paused.
     */
    public function isPaused(): bool
    {
        return $this->status === self::STATUS_PAUSED;
    }

    /**
     * Check if campaign can be edited.
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED]);
    }

    /**
     * Check if campaign can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_SCHEDULED, self::STATUS_SENDING, self::STATUS_PAUSED]);
    }

    /**
     * Check if campaign can be paused.
     */
    public function canBePaused(): bool
    {
        return $this->status === self::STATUS_SENDING;
    }

    /**
     * Check if campaign can be resumed.
     */
    public function canBeResumed(): bool
    {
        return $this->status === self::STATUS_PAUSED;
    }

    /**
     * Schedule the campaign.
     */
    public function schedule(\DateTime $scheduledAt = null): void
    {
        $this->update([
            'status' => self::STATUS_SCHEDULED,
            'scheduled_at' => $scheduledAt ?? now()
        ]);
    }

    /**
     * Start the campaign.
     */
    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_SENDING,
            'started_at' => now()
        ]);
    }

    /**
     * Complete the campaign.
     */
    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now()
        ]);
    }

    /**
     * Fail the campaign.
     */
    public function fail(): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now()
        ]);
    }

    /**
     * Cancel the campaign.
     */
    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'completed_at' => now()
        ]);
    }

    /**
     * Pause the campaign.
     */
    public function pause(): void
    {
        $this->update(['status' => self::STATUS_PAUSED]);
    }

    /**
     * Resume the campaign.
     */
    public function resume(): void
    {
        $this->update(['status' => self::STATUS_SENDING]);
    }

    /**
     * Update campaign statistics.
     */
    public function updateStats(int $sent = 0, int $delivered = 0, int $failed = 0, int $cost = 0): void
    {
        $this->increment('sent_count', $sent);
        $this->increment('delivered_count', $delivered);
        $this->increment('failed_count', $failed);
        $this->increment('actual_cost', $cost);
    }

    /**
     * Get progress percentage.
     */
    public function getProgressPercentage(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }

        $processed = $this->sent_count + $this->failed_count;
        return round(($processed / $this->total_recipients) * 100, 2);
    }

    /**
     * Get delivery rate percentage.
     */
    public function getDeliveryRate(): float
    {
        if ($this->sent_count === 0) {
            return 0;
        }

        return round(($this->delivered_count / $this->sent_count) * 100, 2);
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_SCHEDULED => 'info',
            self::STATUS_SENDING => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'dark',
            self::STATUS_PAUSED => 'warning',
            default => 'primary'
        };
    }

    /**
     * Get estimated cost per SMS.
     */
    public function getCostPerSms(): float
    {
        if ($this->total_recipients === 0) {
            return 0;
        }

        return $this->estimated_cost / $this->total_recipients;
    }

    /**
     * Get actual cost per SMS.
     */
    public function getActualCostPerSms(): float
    {
        if ($this->sent_count === 0) {
            return 0;
        }

        return $this->actual_cost / $this->sent_count;
    }
}