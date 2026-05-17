<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'invoice_id', 'provider', 'reference', 'amount', 'status', 'paid_at', 'payload', 'payment_method', 'notes'
    ];

    protected $casts = [
        'amount' => 'integer',
        'paid_at' => 'datetime',
        'payload' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['success', 'completed']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function isCompleted()
    {
        return in_array($this->status, ['success', 'completed']);
    }

    public function markAsCompleted()
    {
        $this->update(['status' => 'success', 'paid_at' => now()]);
    }
}