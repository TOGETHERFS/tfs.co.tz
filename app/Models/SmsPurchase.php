<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsPurchase extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'quantity',
        'unit_price',
        'total_amount',
        'status',
        'approved_by',
        'approved_at',
        'notes',
        'payment_reference',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    /**
     * Tenant that made the purchase.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Requesting user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Approver user (admin).
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope pending purchases.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope approved purchases.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}