<?php

namespace App\Models\Sms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SmsProviderSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'api_key',
        'secret_key',
        'default_sender_id',
        'cost_per_sms',
        'selling_price_per_sms',
        'provider_balance',
        'balance_synced_at',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'cost_per_sms' => 'decimal:4',
        'selling_price_per_sms' => 'decimal:4',
        'balance_synced_at' => 'datetime',
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    protected $hidden = [
        'api_key',
        'secret_key',
    ];

    public function setApiKeyAttribute($value)
    {
        $this->attributes['api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getApiKeyAttribute($value)
    {
        try {
            return $value ? Crypt::decryptString($value) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setSecretKeyAttribute($value)
    {
        $this->attributes['secret_key'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getSecretKeyAttribute($value)
    {
        try {
            return $value ? Crypt::decryptString($value) : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function getBeemAfrica(): ?self
    {
        return self::where('provider', 'beem_africa')
            ->where('is_active', true)
            ->first();
    }

    public function updateBalance(int $balance): void
    {
        $this->update([
            'provider_balance' => $balance,
            'balance_synced_at' => now(),
        ]);
    }

    public function getProfitMarginAttribute(): float
    {
        if ($this->cost_per_sms <= 0) return 0;
        return (($this->selling_price_per_sms - $this->cost_per_sms) / $this->cost_per_sms) * 100;
    }
}
