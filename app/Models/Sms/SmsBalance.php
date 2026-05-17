<?php

namespace App\Models\Sms;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'balance',
        'total_purchased',
        'total_used',
        'total_failed',
    ];

    protected $casts = [
        'balance' => 'integer',
        'total_purchased' => 'integer',
        'total_used' => 'integer',
        'total_failed' => 'integer',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function hasEnoughBalance(int $count = 1): bool
    {
        return $this->balance >= $count;
    }

    public function deduct(int $count = 1): bool
    {
        if (!$this->hasEnoughBalance($count)) {
            return false;
        }

        $this->decrement('balance', $count);
        $this->increment('total_used', $count);
        return true;
    }

    public function credit(int $count): void
    {
        $this->increment('balance', $count);
        $this->increment('total_purchased', $count);
    }

    public function refund(int $count): void
    {
        $this->increment('balance', $count);
        $this->decrement('total_used', $count);
    }

    public function recordFailed(int $count = 1): void
    {
        $this->increment('total_failed', $count);
    }

    public static function getOrCreateForTenant(int $tenantId): self
    {
        return self::firstOrCreate(
            ['tenant_id' => $tenantId],
            ['balance' => 0, 'total_purchased' => 0, 'total_used' => 0, 'total_failed' => 0]
        );
    }
}
