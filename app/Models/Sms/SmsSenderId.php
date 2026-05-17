<?php

namespace App\Models\Sms;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsSenderId extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'sender_id',
        'provider_id',
        'provider_status',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public static function getDefaultForTenant(int $tenantId): ?self
    {
        return self::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first()
            ?? self::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->first();
    }

    public static function getSystemDefault(): ?self
    {
        return self::whereNull('tenant_id')
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
    }
}
