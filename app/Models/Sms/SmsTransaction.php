<?php

namespace App\Models\Sms;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'package_id',
        'payment_amount',
        'payment_reference',
        'payment_method',
        'payment_status',
        'description',
        'admin_reason',
        'admin_id',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
    ];

    const TYPE_PURCHASE = 'purchase';
    const TYPE_MANUAL_CREDIT = 'manual_credit';
    const TYPE_MANUAL_DEBIT = 'manual_debit';
    const TYPE_USAGE = 'usage';
    const TYPE_REFUND = 'refund';

    const PAYMENT_PENDING = 'pending';
    const PAYMENT_COMPLETED = 'completed';
    const PAYMENT_FAILED = 'failed';

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

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopePurchases($query)
    {
        return $query->where('type', self::TYPE_PURCHASE);
    }

    public function scopeCompleted($query)
    {
        return $query->where('payment_status', self::PAYMENT_COMPLETED);
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_PURCHASE => 'Package Purchase',
            self::TYPE_MANUAL_CREDIT => 'Manual Credit',
            self::TYPE_MANUAL_DEBIT => 'Manual Debit',
            self::TYPE_USAGE => 'SMS Usage',
            self::TYPE_REFUND => 'Refund',
            default => ucfirst($this->type),
        };
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return match($this->type) {
            self::TYPE_PURCHASE, self::TYPE_MANUAL_CREDIT, self::TYPE_REFUND => 'bg-green-100 text-green-800',
            self::TYPE_MANUAL_DEBIT, self::TYPE_USAGE => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public static function recordPurchase(int $tenantId, int $userId, SmsPackage $package, int $balanceBefore, string $paymentRef, string $paymentMethod): self
    {
        return self::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => self::TYPE_PURCHASE,
            'amount' => $package->sms_count,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore + $package->sms_count,
            'package_id' => $package->id,
            'payment_amount' => $package->price,
            'payment_reference' => $paymentRef,
            'payment_method' => $paymentMethod,
            'payment_status' => self::PAYMENT_COMPLETED,
            'description' => "Purchased {$package->name} ({$package->sms_count} SMS)",
        ]);
    }

    public static function recordManualCredit(int $tenantId, int $adminId, int $amount, int $balanceBefore, string $reason): self
    {
        return self::create([
            'tenant_id' => $tenantId,
            'admin_id' => $adminId,
            'type' => self::TYPE_MANUAL_CREDIT,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore + $amount,
            'payment_status' => self::PAYMENT_COMPLETED,
            'admin_reason' => $reason,
            'description' => "Manual credit of {$amount} SMS",
        ]);
    }

    public static function recordUsage(int $tenantId, int $userId, int $count, int $balanceBefore, ?string $description = null): self
    {
        return self::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => self::TYPE_USAGE,
            'amount' => -$count,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceBefore - $count,
            'payment_status' => self::PAYMENT_COMPLETED,
            'description' => $description ?? "Used {$count} SMS",
        ]);
    }
}
