<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    protected $table = 'salaries';

    protected $fillable = [
        'tenant_id', 'user_id', 'month', 'basic_salary',
        'allowances', 'allowances_breakdown',
        'deductions', 'deductions_breakdown',
        'employer_contributions',
        'net_salary', 'payment_date', 'status', 'created_by',
    ];

    protected $casts = [
        'basic_salary'           => 'decimal:2',
        'allowances'             => 'decimal:2',
        'deductions'             => 'decimal:2',
        'net_salary'             => 'decimal:2',
        'payment_date'           => 'date',
        'allowances_breakdown'   => 'array',
        'deductions_breakdown'   => 'array',
        'employer_contributions' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
