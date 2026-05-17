<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'period',
        'price',
        'price_per_staff',
        'price_per_branch',
        'currency',
        'is_active',
        // added limits and features
        'branch_limit',
        'staff_limit',
        'features',
        // SMS pricing
        'sms_price_per_unit',
        'sms_volume_limit',
    ];

    protected $casts = [
        'price' => 'integer',
        'price_per_staff' => 'integer',
        'price_per_branch' => 'integer',
        'branch_limit' => 'integer',
        'staff_limit' => 'integer',
        'is_active' => 'boolean',
        'features' => 'array',
        'sms_price_per_unit' => 'decimal:2',
        'sms_volume_limit' => 'integer',
    ];

    /**
     * Get the tenants for the plan.
     */
    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    /**
     * Get the subscriptions for the plan.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the invoices for the plan.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the addons for the plan.
     */
    public function addons()
    {
        return $this->hasMany(PlanAddon::class);
    }

    /**
     * Scope a query to only include active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}