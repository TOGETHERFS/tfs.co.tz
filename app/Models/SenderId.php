<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SenderId extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'sender_id',
        'business_name',
        'business_description',
        'business_registration',
        'use_case',
        'status',
        'rejection_reason',
        'approved_by',
        'approved_at',
        'rejected_at',
        'documents',
        'is_active'
    ];

    protected $casts = [
        'documents' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'is_active' => 'boolean'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_SUSPENDED = 'suspended';

    /**
     * Get the tenant that owns the sender ID.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user who approved this sender ID.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Alias for approver relationship (for compatibility).
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get SMS logs using this sender ID.
     */
    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class, 'sender_id', 'sender_id');
    }

    /**
     * Get SMS campaigns using this sender ID.
     */
    public function smsCampaigns(): HasMany
    {
        return $this->hasMany(SmsCampaign::class, 'sender_id', 'sender_id');
    }

    /**
     * Alias for smsCampaigns relationship (for compatibility).
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(SmsCampaign::class, 'sender_id', 'sender_id');
    }

    /**
     * Scope to get pending sender IDs.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get approved sender IDs.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope to get rejected sender IDs.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    /**
     * Scope to get active sender IDs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('status', self::STATUS_APPROVED);
    }

    /**
     * Check if sender ID is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if sender ID is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if sender ID is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if sender ID is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    /**
     * Check if sender ID can be used.
     */
    public function canBeUsed(): bool
    {
        return $this->is_active && $this->isApproved();
    }

    /**
     * Approve the sender ID.
     */
    public function approve(int $approvedBy): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approvedBy,
            'approved_at' => now(),
            'rejection_reason' => null,
            'rejected_at' => null,
            'is_active' => true
        ]);
    }

    /**
     * Reject the sender ID.
     */
    public function reject(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'rejected_at' => now(),
            'approved_by' => null,
            'approved_at' => null,
            'is_active' => false
        ]);
    }

    /**
     * Suspend the sender ID.
     */
    public function suspend(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_SUSPENDED,
            'rejection_reason' => $reason,
            'is_active' => false
        ]);
    }

    /**
     * Activate the sender ID.
     */
    public function activate(): void
    {
        if ($this->isApproved()) {
            $this->update(['is_active' => true]);
        }
    }

    /**
     * Deactivate the sender ID.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_SUSPENDED => 'secondary',
            default => 'primary'
        };
    }

    /**
     * Get formatted sender ID for display.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->sender_id . ' (' . $this->business_name . ')';
    }

    /**
     * Validate sender ID format.
     */
    public static function isValidSenderId(string $senderId): bool
    {
        // Sender ID should be 3-11 characters, alphanumeric
        return preg_match('/^[A-Za-z0-9]{3,11}$/', $senderId);
    }

    /**
     * Check if sender ID is available.
     */
    public static function isAvailable(string $senderId, int $tenantId = null): bool
    {
        $query = static::where('sender_id', $senderId);
        
        if ($tenantId) {
            $query->where('tenant_id', '!=', $tenantId);
        }
        
        return !$query->exists();
    }
}
