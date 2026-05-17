<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'contact_email',
        'phone',
        'status',
        'sms_credits',
        'messaging_enabled',
        'plan_id',
        'trial_ends_at',
        'plan_slug',
        'plan_renews_at',
        'is_on_trial',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'plan_renews_at' => 'datetime',
        'messaging_enabled' => 'boolean',
        'is_on_trial' => 'boolean',
    ];

    /**
     * Get the plan that owns the tenant.
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the subscriptions for the tenant.
     */
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the invoices for the tenant.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the payments for the tenant.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the SMS wallet for the tenant.
     */
    public function smsWallet()
    {
        return $this->hasOne(SmsWallet::class);
    }

    /**
     * Get the users for the tenant.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the clients for the tenant.
     */
    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    /**
     * Get the loan products for the tenant.
     */
    public function loanProducts()
    {
        return $this->hasMany(LoanProduct::class);
    }

    /**
     * Get the loans for the tenant.
     */
    public function loans()
    {
        return $this->hasMany(Loan::class);
    }

    /**
     * Get the SMS purchase requests for the tenant.
     */
    public function smsPurchases()
    {
        return $this->hasMany(\App\Models\SmsPurchase::class);
    }

    /**
     * Accessor: treat status === 'active' as is_active = true.
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Scope a query to only include active tenants.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check if tenant is on trial.
     */
    public function isOnTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Get the current plan details.
     */
    public function getCurrentPlan()
    {
        if (!$this->plan_slug) {
            return \App\Support\Pricing::getDefaultPlan();
        }
        
        return \App\Support\Pricing::getPlan($this->plan_slug);
    }

    /**
     * Get the maximum number of branches allowed for current plan.
     */
    public function getMaxBranches()
    {
        return \App\Support\Pricing::getMaxBranches($this->plan_slug ?? 'starter');
    }

    /**
     * Check if tenant can create more branches.
     */
    public function canCreateBranch()
    {
        $currentBranchCount = $this->branches()->count();
        return $currentBranchCount < $this->getMaxBranches();
    }

    /**
     * Get the number of branches remaining.
     */
    public function getRemainingBranches()
    {
        $currentBranchCount = $this->branches()->count();
        $maxBranches = $this->getMaxBranches();
        return max(0, $maxBranches - $currentBranchCount);
    }

    /**
     * Check if plan has expired.
     */
    public function isPlanExpired()
    {
        return $this->plan_renews_at && $this->plan_renews_at->isPast();
    }

    /**
     * Get days until plan renewal.
     */
    public function getDaysUntilRenewal()
    {
        if (!$this->plan_renews_at) {
            return null;
        }
        
        return now()->diffInDays($this->plan_renews_at, false);
    }

    /**
     * Update tenant plan.
     */
    public function updatePlan($planSlug)
    {
        $plan = \App\Support\Pricing::getPlan($planSlug);
        if (!$plan) {
            throw new \InvalidArgumentException("Invalid plan slug: {$planSlug}");
        }

        $this->update([
            'plan_slug' => $planSlug,
            'plan_renews_at' => \App\Support\Pricing::calculateNextRenewal($planSlug),
        ]);
    }

    /**
     * Get the branches for the tenant.
     */
    public function branches()
    {
        return $this->hasMany(\App\Models\Branch::class);
    }
}