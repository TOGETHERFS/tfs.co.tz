<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    protected $table = 'leave_balances';

    protected $fillable = [
        'tenant_id', 'user_id', 'year', 'entitled_days', 'used_days',
    ];

    protected $casts = [
        'entitled_days' => 'integer',
        'used_days'     => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getRemainingDaysAttribute(): int
    {
        return max(0, $this->entitled_days - $this->used_days);
    }
}
