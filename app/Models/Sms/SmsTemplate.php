<?php

namespace App\Models\Sms;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'category',
        'content',
        'variables',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    const CATEGORY_LOAN_APPROVAL = 'loan_approval';
    const CATEGORY_REPAYMENT_REMINDER = 'repayment_reminder';
    const CATEGORY_PENALTY_ALERT = 'penalty_alert';
    const CATEGORY_ARREARS_ALERT = 'arrears_alert';
    const CATEGORY_MARKETING = 'marketing';
    const CATEGORY_GENERAL = 'general';

    const AVAILABLE_VARIABLES = [
        '{borrower_name}' => 'Borrower full name',
        '{first_name}' => 'Borrower first name',
        '{loan_balance}' => 'Outstanding loan balance',
        '{due_date}' => 'Next payment due date',
        '{due_amount}' => 'Amount due',
        '{loan_number}' => 'Loan reference number',
        '{company_name}' => 'Your company name',
        '{days_overdue}' => 'Number of days overdue',
        '{penalty_amount}' => 'Penalty amount',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTenant($query, ?int $tenantId)
    {
        return $query->where(function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)
              ->orWhere('is_system', true);
        });
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function render(array $data): string
    {
        $content = $this->content;
        foreach ($data as $key => $value) {
            $placeholder = '{' . $key . '}';
            $content = str_replace($placeholder, $value, $content);
        }
        return $content;
    }

    public static function getCategoryOptions(): array
    {
        return [
            self::CATEGORY_LOAN_APPROVAL => 'Loan Approval',
            self::CATEGORY_REPAYMENT_REMINDER => 'Repayment Reminder',
            self::CATEGORY_PENALTY_ALERT => 'Penalty Alert',
            self::CATEGORY_ARREARS_ALERT => 'Arrears Alert',
            self::CATEGORY_MARKETING => 'Marketing',
            self::CATEGORY_GENERAL => 'General',
        ];
    }
}
