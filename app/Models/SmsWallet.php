<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'balance',
        'ledger',
        'low_balance_threshold',
        'auto_topup_enabled',
        'auto_topup_amount',
        'auto_topup_threshold',
        'email_notifications_enabled',
        'low_balance_notifications',
        'topup_notifications',
        'sender_id_notifications',
        'daily_usage_notifications',
        'weekly_usage_notifications',
        'last_low_balance_notification'
    ];

    protected $casts = [
        'balance' => 'integer',
        'ledger' => 'array',
        'low_balance_threshold' => 'integer',
        'auto_topup_enabled' => 'boolean',
        'auto_topup_amount' => 'integer',
        'auto_topup_threshold' => 'integer',
        'email_notifications_enabled' => 'boolean',
        'low_balance_notifications' => 'boolean',
        'topup_notifications' => 'boolean',
        'sender_id_notifications' => 'boolean',
        'daily_usage_notifications' => 'boolean',
        'weekly_usage_notifications' => 'boolean',
        'last_low_balance_notification' => 'datetime'
    ];

    /**
     * Get the tenant that owns the wallet.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the topups for this wallet.
     */
    public function topups(): HasMany
    {
        return $this->hasMany(SmsTopup::class, 'tenant_id', 'tenant_id');
    }

    /**
     * Get the SMS logs for this wallet.
     */
    public function smsLogs(): HasMany
    {
        return $this->hasMany(SmsLog::class, 'tenant_id', 'tenant_id');
    }

    /**
     * Check if balance is low.
     */
    public function isLowBalance(): bool
    {
        return $this->balance <= ($this->low_balance_threshold ?? 100);
    }

    /**
     * Check if auto topup should be triggered.
     */
    public function shouldAutoTopup(): bool
    {
        return $this->auto_topup_enabled 
            && $this->balance <= ($this->auto_topup_threshold ?? 50);
    }

    /**
     * Add credits to the wallet.
     */
    public function addCredits(int $amount, string $description = 'Credit added'): void
    {
        $this->increment('balance', $amount);
        
        $this->addLedgerEntry('credit', $amount, $description);
    }

    /**
     * Deduct credits from the wallet.
     */
    public function deductCredits(int $amount, string $description = 'Credit deducted'): bool
    {
        if ($this->balance < $amount) {
            return false;
        }

        $this->decrement('balance', $amount);
        
        $this->addLedgerEntry('debit', $amount, $description);
        
        return true;
    }

    /**
     * Add entry to ledger.
     */
    protected function addLedgerEntry(string $type, int $amount, string $description): void
    {
        $ledger = $this->ledger ?? [];
        
        $ledger[] = [
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'balance_after' => $this->fresh()->balance,
            'timestamp' => now()->toISOString()
        ];

        // Keep only last 1000 entries
        if (count($ledger) > 1000) {
            $ledger = array_slice($ledger, -1000);
        }

        $this->update(['ledger' => $ledger]);
    }

    /**
     * Get recent ledger entries.
     */
    public function getRecentTransactions(int $limit = 50): array
    {
        $ledger = $this->ledger ?? [];
        
        return array_slice(array_reverse($ledger), 0, $limit);
    }

    /**
     * Get total credits added.
     */
    public function getTotalCreditsAdded(): int
    {
        $ledger = $this->ledger ?? [];
        
        return collect($ledger)
            ->where('type', 'credit')
            ->sum('amount');
    }

    /**
     * Get total credits used.
     */
    public function getTotalCreditsUsed(): int
    {
        $ledger = $this->ledger ?? [];
        
        return collect($ledger)
            ->where('type', 'debit')
            ->sum('amount');
    }
}