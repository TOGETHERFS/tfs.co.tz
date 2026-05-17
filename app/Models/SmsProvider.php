<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'is_active',
        'is_primary',
        'config',
        'balance',
        'cost_per_sms',
        'priority'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'config' => 'array',
        'balance' => 'decimal:2',
        'cost_per_sms' => 'decimal:4',
        'priority' => 'integer'
    ];

    /**
     * Scope to get active providers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get primary provider.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Get the provider's API key.
     */
    public function getApiKeyAttribute(): ?string
    {
        return $this->config['api_key'] ?? null;
    }

    /**
     * Get the provider's secret key.
     */
    public function getSecretKeyAttribute(): ?string
    {
        return $this->config['secret_key'] ?? null;
    }

    /**
     * Get the provider's base URL.
     */
    public function getBaseUrlAttribute(): ?string
    {
        return $this->config['base_url'] ?? null;
    }

    /**
     * Check if provider is configured properly.
     */
    public function isConfigured(): bool
    {
        $config = $this->config ?? [];
        
        return match ($this->name) {
            'beem_africa' => isset($config['api_key'], $config['secret_key']),
            'route_africa' => isset($config['username'], $config['password']),
            default => false
        };
    }

    /**
     * Set provider as primary (and unset others).
     */
    public function setPrimary(): void
    {
        // Unset all other primary providers
        static::where('is_primary', true)->update(['is_primary' => false]);
        
        // Set this as primary
        $this->update(['is_primary' => true]);
    }
}