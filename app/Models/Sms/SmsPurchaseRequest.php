<?php

namespace App\Models\Sms;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsPurchaseRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'package_id',
        'sms_count',
        'amount',
        'currency',
        'status',
        'payment_method',
        'payment_reference',
        'selcom_order_id',
        'selcom_transaction_id',
        'payment_response',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_response' => 'array',
        'paid_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(SmsPackage::class, 'package_id');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    public function markAsCompleted(string $transactionId, array $response = []): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'selcom_transaction_id' => $transactionId,
            'payment_response' => $response,
            'paid_at' => now(),
        ]);
    }

    public function markAsFailed(array $response = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'payment_response' => $response,
        ]);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_PROCESSING => 'bg-blue-100 text-blue-800',
            self::STATUS_COMPLETED => 'bg-green-100 text-green-800',
            self::STATUS_FAILED, self::STATUS_CANCELLED => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public static function generateOrderId(): string
    {
        return 'SMS-' . date('YmdHis') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }
}
