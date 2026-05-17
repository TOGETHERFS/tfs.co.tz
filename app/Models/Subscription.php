<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'status',
        'current_period_start',
        'current_period_end',
        'grace_days',
        'cancel_at_period_end',
    ];

    protected $casts = [
        'current_period_start' => 'datetime',
        'current_period_end' => 'datetime',
        'grace_days' => 'integer',
        'cancel_at_period_end' => 'boolean',
    ];

    /**
     * Get the tenant that owns the subscription.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the plan that owns the subscription.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Scope a query to only include active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if subscription is active.
     */
    public function isActive()
    {
        return $this->status === 'active' && $this->current_period_end->isFuture();
    }

    /**
     * Check if subscription is in grace period.
     */
    public function isInGracePeriod()
    {
        if ($this->status !== 'active') {
            return false;
        }

        $graceEndDate = $this->current_period_end->addDays($this->grace_days);
        return now()->between($this->current_period_end, $graceEndDate);
    }

    public function items()
    {
        return $this->hasMany(SubscriptionItem::class);
    }
}