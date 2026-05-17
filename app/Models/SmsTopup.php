<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsTopup extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'amount',
        'units',
        'status',
        'internal_ref',
        'selcom_ref',
        'currency',
        'selcom_payload',
        'paid_at',
        'expires_at',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'units' => 'integer',
        'selcom_payload' => 'array',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_FAILED = 'failed';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the tenant that owns the topup.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to get pending topups.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get paid topups.
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope to get failed topups.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to get expired topups.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED)
            ->orWhere(function ($query) {
                $query->where('status', self::STATUS_PENDING)
                    ->where('expires_at', '<', now());
            });
    }

    /**
     * Check if topup is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if topup is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if topup is failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if topup is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED 
            || ($this->status === self::STATUS_PENDING && $this->expires_at && $this->expires_at->isPast());
    }

    /**
     * Mark topup as paid.
     */
    public function markAsPaid(string $selcomRef = null): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
            'selcom_ref' => $selcomRef ?? $this->selcom_ref
        ]);

        // Add credits to wallet
        $wallet = SmsWallet::firstOrCreate(
            ['tenant_id' => $this->tenant_id],
            ['balance' => 0]
        );

        $wallet->addCredits($this->units, "SMS topup - {$this->internal_ref}");
    }

    /**
     * Mark topup as failed.
     */
    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'notes' => $reason
        ]);
    }

    /**
     * Mark topup as expired.
     */
    public function markAsExpired(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED
        ]);
    }

    /**
     * Mark topup as cancelled.
     */
    public function markAsCancelled(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'notes' => $reason
        ]);
    }

    /**
     * Generate internal reference.
     */
    public static function generateInternalRef(): string
    {
        return 'SMS-' . strtoupper(uniqid());
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_PAID => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_EXPIRED => 'secondary',
            self::STATUS_CANCELLED => 'dark',
            default => 'primary'
        };
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . ($this->currency ?? 'TZS');
    }
}