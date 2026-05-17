<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlanAddon extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'slug',
        'unit_price',
        'currency',
        'is_active',
    ];

    protected $casts = [
        'unit_price' => 'integer',
        'is_active' => 'boolean',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}