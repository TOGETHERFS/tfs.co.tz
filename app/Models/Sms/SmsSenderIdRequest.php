<?php

namespace App\Models\Sms;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsSenderIdRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'requested_by',
        'sender_id',
        'company_name',
        'purpose',
        'sample_message',
        'status',
        'admin_notes',
        'approved_by',
        'approved_at',
        'provider_status',
        'provider_sender_id',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_SUBMITTED = 'submitted_to_provider';
    const STATUS_LIVE = 'live';

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function approve(int $adminId, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $adminId,
            'approved_at' => now(),
            'admin_notes' => $notes,
        ]);
    }

    public function reject(int $adminId, ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $adminId,
            'approved_at' => now(),
            'admin_notes' => $notes,
        ]);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_APPROVED => 'bg-blue-100 text-blue-800',
            self::STATUS_REJECTED => 'bg-red-100 text-red-800',
            self::STATUS_SUBMITTED => 'bg-purple-100 text-purple-800',
            self::STATUS_LIVE => 'bg-green-100 text-green-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function isLive(): bool
    {
        return $this->status === self::STATUS_LIVE;
    }

    public function makeLive(): void
    {
        $this->update(['status' => self::STATUS_LIVE]);
    }
}
