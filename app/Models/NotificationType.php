<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationType extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'category',
        'description',
        'default_channels',
        'template_data',
        'is_active',
        'user_configurable',
        'icon',
        'color',
        'priority',
    ];

    protected $casts = [
        'default_channels' => 'array',
        'template_data' => 'array',
        'is_active' => 'boolean',
        'user_configurable' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Get notification types by category
     */
    public static function getByCategory(string $category)
    {
        return static::where('category', $category)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get all categories with their notification types
     */
    public static function getAllGroupedByCategory()
    {
        return static::where('is_active', true)
            ->orderBy('category')
            ->orderBy('priority', 'desc')
            ->orderBy('name')
            ->get()
            ->groupBy('category');
    }

    /**
     * Get notification type by key
     */
    public static function getByKey(string $key)
    {
        return static::where('key', $key)->where('is_active', true)->first();
    }

    /**
     * Get user configurable notification types
     */
    public static function getUserConfigurable()
    {
        return static::where('is_active', true)
            ->where('user_configurable', true)
            ->orderBy('category')
            ->orderBy('priority', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get category display name
     */
    public function getCategoryDisplayNameAttribute()
    {
        $categories = [
            'payment_billing' => 'Payment & Billing',
            'account_security' => 'Account & Security',
            'kyc_compliance' => 'KYC & Compliance',
            'loan_management' => 'Loan Management',
            'operations_support' => 'Operations & Support',
            'tenant_branch' => 'Tenant & Branch Management',
        ];

        return $categories[$this->category] ?? ucfirst(str_replace('_', ' ', $this->category));
    }

    /**
     * Get priority display name
     */
    public function getPriorityDisplayNameAttribute()
    {
        $priorities = [
            1 => 'Low',
            2 => 'Medium',
            3 => 'High',
        ];

        return $priorities[$this->priority] ?? 'Unknown';
    }

    /**
     * Check if notification type supports a specific channel
     */
    public function supportsChannel(string $channel): bool
    {
        return in_array($channel, $this->default_channels ?? []);
    }

    /**
     * Get available channels
     */
    public static function getAvailableChannels(): array
    {
        return [
            'database' => 'In-App Notification',
            'mail' => 'Email',
            'sms' => 'SMS',
        ];
    }
}