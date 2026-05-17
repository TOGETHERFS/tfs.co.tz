<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminExpense extends Model
{
    use SoftDeletes;

    protected $table = 'admin_expenses';

    protected $fillable = [
        'category', 'description', 'amount', 'expense_date',
        'payment_method', 'reference', 'receipt', 'notes', 'created_by',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'expense_date' => 'date',
    ];

    public static array $categories = [
        'marketing'  => 'Marketing',
        'hosting'    => 'Hosting / Server',
        'salary'     => 'Salaries',
        'ads'        => 'Running Ads',
        'sms_cost'   => 'SMS Buying Cost',
        'office'     => 'Office / Admin',
        'software'   => 'Software / Tools',
        'other'      => 'Other',
    ];
}
