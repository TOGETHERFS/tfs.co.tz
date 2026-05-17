<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class LoanApproval extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'loan_id',
        'stage',
        'status',
        'user_id',
        'comment',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}