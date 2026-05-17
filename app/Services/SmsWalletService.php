<?php

namespace App\Services;

use App\Models\SmsWallet;
use App\Models\SmsTopup;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SmsWalletService
{
    /**
     * Get or create SMS wallet for a tenant.
     */
    public function getWallet(int $tenantId): SmsWallet
    {
        return SmsWallet::firstOrCreate(
            ['tenant_id' => $tenantId],
            [
                'balance' => 0,
                'ledger' => [],
                'low_balance_threshold' => 100,
                'auto_topup_enabled' => false,
                'auto_topup_amount' => 500,
                'auto_topup_threshold' => 50
            ]
        );
    }

    /**
     * Check if tenant has sufficient balance for SMS.
     */
    public function hasBalance(int $tenantId, int $requiredCredits): bool
    {
        $wallet = $this->getWallet($tenantId);
        return $wallet->balance >= $requiredCredits;
    }

    /**
     * Get wallet balance for a tenant.
     */
    public function getBalance(int $tenantId): int
    {
        $wallet = $this->getWallet($tenantId);
        return $wallet->balance;
    }

    /**
     * Add credits to wallet.
     */
    public function addCredits(int $tenantId, int $amount, string $description = 'Credit addition', ?string $reference = null): bool
    {
        try {
            return DB::transaction(function () use ($tenantId, $amount, $description, $reference) {
                $wallet = $this->getWallet($tenantId);
                
                // Update balance
                $oldBalance = $wallet->balance;
                $newBalance = $oldBalance + $amount;
                
                // Add ledger entry
                $ledger = $wallet->ledger ?? [];
                $ledger[] = [
                    'type' => 'credit',
                    'amount' => $amount,
                    'balance_before' => $oldBalance,
                    'balance_after' => $newBalance,
                    'description' => $description,
                    'reference' => $reference,
                    'timestamp' => now()->toISOString(),
                    'created_by' => auth()->id()
                ];

                $wallet->update([
                    'balance' => $newBalance,
                    'ledger' => $ledger
                ]);

                Log::info("SMS credits added", [
                    'tenant_id' => $tenantId,
                    'amount' => $amount,
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                    'description' => $description,
                    'reference' => $reference
                ]);

                return true;
            });
        } catch (\Exception $e) {
            Log::error("Failed to add SMS credits", [
                'tenant_id' => $tenantId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Deduct credits from wallet.
     */
    public function deductCredits(int $tenantId, int $amount, string $description = 'Credit deduction', ?string $reference = null): bool
    {
        try {
            return DB::transaction(function () use ($tenantId, $amount, $description, $reference) {
                $wallet = $this->getWallet($tenantId);
                
                // Check if sufficient balance
                if ($wallet->balance < $amount) {
                    Log::warning("Insufficient SMS balance", [
                        'tenant_id' => $tenantId,
                        'required' => $amount,
                        'available' => $wallet->balance
                    ]);
                    return false;
                }

                // Update balance
                $oldBalance = $wallet->balance;
                $newBalance = $oldBalance - $amount;
                
                // Add ledger entry
                $ledger = $wallet->ledger ?? [];
                $ledger[] = [
                    'type' => 'debit',
                    'amount' => $amount,
                    'balance_before' => $oldBalance,
                    'balance_after' => $newBalance,
                    'description' => $description,
                    'reference' => $reference,
                    'timestamp' => now()->toISOString(),
                    'created_by' => auth()->id()
                ];

                $wallet->update([
                    'balance' => $newBalance,
                    'ledger' => $ledger
                ]);

                // Check if auto-topup should be triggered
                $this->checkAutoTopup($wallet);

                Log::info("SMS credits deducted", [
                    'tenant_id' => $tenantId,
                    'amount' => $amount,
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                    'description' => $description,
                    'reference' => $reference
                ]);

                return true;
            });
        } catch (\Exception $e) {
            Log::error("Failed to deduct SMS credits", [
                'tenant_id' => $tenantId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Reserve credits for a pending SMS operation.
     */
    public function reserveCredits(int $tenantId, int $amount, string $description = 'Credit reservation', ?string $reference = null): bool
    {
        try {
            return DB::transaction(function () use ($tenantId, $amount, $description, $reference) {
                $wallet = $this->getWallet($tenantId);
                
                // Check if sufficient balance
                if ($wallet->balance < $amount) {
                    return false;
                }

                // Add ledger entry for reservation (doesn't change balance)
                $ledger = $wallet->ledger ?? [];
                $ledger[] = [
                    'type' => 'reservation',
                    'amount' => $amount,
                    'balance_before' => $wallet->balance,
                    'balance_after' => $wallet->balance,
                    'description' => $description,
                    'reference' => $reference,
                    'timestamp' => now()->toISOString(),
                    'created_by' => auth()->id()
                ];

                $wallet->update(['ledger' => $ledger]);

                return true;
            });
        } catch (\Exception $e) {
            Log::error("Failed to reserve SMS credits", [
                'tenant_id' => $tenantId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Release reserved credits (when SMS fails).
     */
    public function releaseReservedCredits(int $tenantId, int $amount, string $description = 'Credit release', ?string $reference = null): bool
    {
        try {
            return DB::transaction(function () use ($tenantId, $amount, $description, $reference) {
                $wallet = $this->getWallet($tenantId);
                
                // Add ledger entry for release
                $ledger = $wallet->ledger ?? [];
                $ledger[] = [
                    'type' => 'release',
                    'amount' => $amount,
                    'balance_before' => $wallet->balance,
                    'balance_after' => $wallet->balance,
                    'description' => $description,
                    'reference' => $reference,
                    'timestamp' => now()->toISOString(),
                    'created_by' => auth()->id()
                ];

                $wallet->update(['ledger' => $ledger]);

                return true;
            });
        } catch (\Exception $e) {
            Log::error("Failed to release reserved SMS credits", [
                'tenant_id' => $tenantId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Transfer credits between wallets.
     */
    public function transferCredits(int $fromTenantId, int $toTenantId, int $amount, string $description = 'Credit transfer'): bool
    {
        try {
            return DB::transaction(function () use ($fromTenantId, $toTenantId, $amount, $description) {
                $reference = 'TXN-' . now()->format('YmdHis') . '-' . rand(1000, 9999);
                
                // Deduct from source wallet
                $deductSuccess = $this->deductCredits(
                    $fromTenantId,
                    $amount,
                    $description . ' (outgoing)',
                    $reference
                );

                if (!$deductSuccess) {
                    return false;
                }

                // Add to destination wallet
                $addSuccess = $this->addCredits(
                    $toTenantId,
                    $amount,
                    $description . ' (incoming)',
                    $reference
                );

                if (!$addSuccess) {
                    // Rollback: add credits back to source wallet
                    $this->addCredits(
                        $fromTenantId,
                        $amount,
                        'Transfer rollback',
                        $reference
                    );
                    return false;
                }

                return true;
            });
        } catch (\Exception $e) {
            Log::error("Failed to transfer SMS credits", [
                'from_tenant_id' => $fromTenantId,
                'to_tenant_id' => $toTenantId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get wallet transaction history.
     */
    public function getTransactionHistory(int $tenantId, int $limit = 50): array
    {
        $wallet = $this->getWallet($tenantId);
        $ledger = $wallet->ledger ?? [];
        
        // Sort by timestamp descending and limit
        usort($ledger, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return array_slice($ledger, 0, $limit);
    }

    /**
     * Get wallet statistics.
     */
    public function getWalletStats(int $tenantId): array
    {
        $wallet = $this->getWallet($tenantId);
        $ledger = $wallet->ledger ?? [];
        
        $stats = [
            'current_balance' => $wallet->balance,
            'total_credits_added' => 0,
            'total_credits_used' => 0,
            'total_transactions' => count($ledger),
            'last_transaction' => null,
            'is_low_balance' => $wallet->isLowBalance(),
            'auto_topup_enabled' => $wallet->auto_topup_enabled
        ];

        foreach ($ledger as $entry) {
            if ($entry['type'] === 'credit') {
                $stats['total_credits_added'] += $entry['amount'];
            } elseif ($entry['type'] === 'debit') {
                $stats['total_credits_used'] += $entry['amount'];
            }
            
            if (!$stats['last_transaction'] || strtotime($entry['timestamp']) > strtotime($stats['last_transaction'])) {
                $stats['last_transaction'] = $entry['timestamp'];
            }
        }

        return $stats;
    }

    /**
     * Check and trigger auto-topup if needed.
     */
    protected function checkAutoTopup(SmsWallet $wallet): void
    {
        if (!$wallet->auto_topup_enabled) {
            return;
        }

        if ($wallet->balance <= $wallet->auto_topup_threshold) {
            // Check if there's already a pending auto-topup
            $pendingTopup = SmsTopup::where('tenant_id', $wallet->tenant_id)
                ->where('status', 'pending')
                ->where('notes', 'like', '%auto-topup%')
                ->where('created_at', '>=', now()->subHours(1))
                ->exists();

            if (!$pendingTopup) {
                $this->triggerAutoTopup($wallet);
            }
        }
    }

    /**
     * Trigger auto-topup for a wallet.
     */
    protected function triggerAutoTopup(SmsWallet $wallet): void
    {
        try {
            $topup = SmsTopup::create([
                'tenant_id' => $wallet->tenant_id,
                'amount' => $wallet->auto_topup_amount * 0.02, // Assuming 0.02 USD per SMS
                'units' => $wallet->auto_topup_amount,
                'status' => 'pending',
                'internal_ref' => SmsTopup::generateInternalRef(),
                'currency' => 'TZS',
                'notes' => 'Auto-topup triggered due to low balance'
            ]);

            Log::info("Auto-topup triggered", [
                'tenant_id' => $wallet->tenant_id,
                'topup_id' => $topup->id,
                'amount' => $wallet->auto_topup_amount,
                'current_balance' => $wallet->balance,
                'threshold' => $wallet->auto_topup_threshold
            ]);

            // Here you would typically integrate with payment gateway
            // For now, we'll just log the event
            
        } catch (\Exception $e) {
            Log::error("Failed to trigger auto-topup", [
                'tenant_id' => $wallet->tenant_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process a successful topup.
     */
    public function processTopup(SmsTopup $topup): bool
    {
        if ($topup->status !== 'pending') {
            return false;
        }

        try {
            return DB::transaction(function () use ($topup) {
                // Add credits to wallet
                $success = $this->addCredits(
                    $topup->tenant_id,
                    $topup->units,
                    "SMS topup - {$topup->internal_ref}",
                    $topup->internal_ref
                );

                if ($success) {
                    $topup->markAsPaid();
                    return true;
                }

                return false;
            });
        } catch (\Exception $e) {
            Log::error("Failed to process SMS topup", [
                'topup_id' => $topup->id,
                'tenant_id' => $topup->tenant_id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get low balance wallets.
     */
    public function getLowBalanceWallets(): \Illuminate\Database\Eloquent\Collection
    {
        return SmsWallet::whereRaw('balance <= low_balance_threshold')
            ->with('tenant')
            ->get();
    }

    /**
     * Get wallet usage summary for a period.
     */
    public function getUsageSummary(int $tenantId, \Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $wallet = $this->getWallet($tenantId);
        $ledger = $wallet->ledger ?? [];
        
        $summary = [
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
            'credits_added' => 0,
            'credits_used' => 0,
            'net_change' => 0,
            'transaction_count' => 0
        ];

        foreach ($ledger as $entry) {
            $entryDate = \Carbon\Carbon::parse($entry['timestamp']);
            
            if ($entryDate->between($startDate, $endDate)) {
                $summary['transaction_count']++;
                
                if ($entry['type'] === 'credit') {
                    $summary['credits_added'] += $entry['amount'];
                } elseif ($entry['type'] === 'debit') {
                    $summary['credits_used'] += $entry['amount'];
                }
            }
        }

        $summary['net_change'] = $summary['credits_added'] - $summary['credits_used'];

        return $summary;
    }

    /**
     * Bulk update wallet settings.
     */
    public function bulkUpdateSettings(array $tenantIds, array $settings): int
    {
        $updated = 0;
        
        foreach ($tenantIds as $tenantId) {
            try {
                $wallet = $this->getWallet($tenantId);
                $wallet->update($settings);
                $updated++;
            } catch (\Exception $e) {
                Log::error("Failed to update wallet settings", [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $updated;
    }
}