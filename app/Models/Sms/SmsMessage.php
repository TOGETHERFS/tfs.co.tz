<?php

namespace App\Models\Sms;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Client;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'sender_id',
        'recipient',
        'message',
        'sms_count',
        'status',
        'provider_message_id',
        'provider_status',
        'provider_response',
        'failure_reason',
        'message_type',
        'batch_id',
        'client_id',
        'loan_id',
        'sent_at',
        'delivered_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    const STATUS_QUEUED = 'queued';
    const STATUS_SENT = 'sent';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';
    const STATUS_REJECTED = 'rejected';

    const TYPE_SINGLE = 'single';
    const TYPE_BULK = 'bulk';
    const TYPE_NOTIFICATION = 'notification';
    const TYPE_REMINDER = 'reminder';
    const TYPE_MARKETING = 'marketing';

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeSent($query)
    {
        return $query->whereIn('status', [self::STATUS_SENT, self::STATUS_DELIVERED]);
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', [self::STATUS_FAILED, self::STATUS_REJECTED]);
    }

    public function scopeByBatch($query, string $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    public function markAsSent(string $providerMessageId, ?string $providerResponse = null): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'provider_message_id' => $providerMessageId,
            'provider_response' => $providerResponse,
            'sent_at' => now(),
        ]);
    }

    public function markAsDelivered(?string $providerStatus = null): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'provider_status' => $providerStatus,
            'delivered_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason, ?string $providerResponse = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
            'provider_response' => $providerResponse,
        ]);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            self::STATUS_QUEUED => 'bg-yellow-100 text-yellow-800',
            self::STATUS_SENT => 'bg-blue-100 text-blue-800',
            self::STATUS_DELIVERED => 'bg-green-100 text-green-800',
            self::STATUS_FAILED, self::STATUS_REJECTED => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }

    public static function calculateSmsCount(string $message): int
    {
        $length = mb_strlen($message);
        if ($length <= 160) return 1;
        return ceil($length / 153);
    }

    public static function generateBatchId(): string
    {
        return 'BATCH-' . date('YmdHis') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }
}
