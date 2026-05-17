<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryAdvance extends Model
{
    protected $table = 'salary_advances';

    protected $fillable = [
        'tenant_id', 'user_id', 'amount', 'reason',
        'status', 'reviewed_by', 'reviewed_at', 'review_note', 'requested_date',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'reviewed_at'  => 'datetime',
        'requested_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
