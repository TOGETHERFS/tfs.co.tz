<?php

namespace App\Models\Sms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'sms_count',
        'price',
        'currency',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    public function getPricePerSmsAttribute()
    {
        return $this->sms_count > 0 ? $this->price / $this->sms_count : 0;
    }

    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 0) . ' ' . $this->currency;
    }
}
