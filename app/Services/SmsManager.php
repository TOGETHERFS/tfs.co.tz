<?php

namespace App\Services;

use App\Contracts\SmsProviderInterface;
use App\Services\SmsProviders\BeemAfricaProvider;
use App\Services\SmsProviders\RouteAfricaProvider;
use App\Models\SmsProvider;
use App\Models\SmsWallet;
use App\Models\SmsLog;
use App\Models\Tenant;
use App\Models\Sms\SmsBalance;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SmsManager
{
    protected array $providers = [];
    protected ?SmsProviderInterface $activeProvider = null;

    public function __construct()
    {
        $this->loadProviders();
    }

    /**
     * Load active SMS providers from database.
     */
    protected function loadProviders(): void
    {
        $providers = SmsProvider::where('is_active', true)
            ->orderBy('priority', 'asc')
            ->get();

        foreach ($providers as $provider) {
            $this->providers[$provider->name] = $this->createProviderInstance($provider);
        }
    }

    /**
     * Create provider instance based on configuration.
     */
    protected function createProviderInstance(SmsProvider $provider): SmsProviderInterface
    {
        $config = $provider->config;

        return match ($provider->name) {
            'beem_africa' => new BeemAfricaProvider($config),
            'route_africa' => new RouteAfricaProvider($config),
            default => throw new \InvalidArgumentException("Unknown provider: {$provider->name}")
        };
    }

    /**
     * Get the best available provider for sending SMS.
     */
    protected function getBestProvider(): ?SmsProviderInterface
    {
        if (empty($this->providers)) {
            return null;
        }

        // Get primary provider first
        $primaryProvider = SmsProvider::where('is_primary', true)
            ->where('is_active', true)
            ->first();

        if ($primaryProvider && isset($this->providers[$primaryProvider->name])) {
            return $this->providers[$primaryProvider->name];
        }

        // Fallback to first available provider
        return reset($this->providers);
    }

    /**
     * Send SMS to a single recipient (used by SmsSendingService::processSingleSms).
     */
    public function sendSingle(string $recipient, string $message, ?string $senderId = null): array
    {
        $provider = $this->getBestProvider();
        if (!$provider) {
            return [
                'success' => false,
                'message' => 'No SMS provider available',
                'provider' => null,
                'provider_request_id' => null,
                'response' => null,
            ];
        }

        $result = $provider->sendSms($recipient, $message, $senderId);

        return [
            'success'            => $result['success'] ?? false,
            'message'            => $result['message'] ?? 'Unknown error',
            'provider'           => $provider->getProviderName(),
            'provider_request_id'=> $result['request_id'] ?? null,
            'response'           => $result['data'] ?? null,
        ];
    }

    /**
     * Send SMS to a single recipient (tenant-aware wrapper).
     */
    public function sendSms(int $tenantId, string $recipient, string $message, string $senderId = null, int $userId = null): array
    {
        return $this->sendBulkSms($tenantId, [$recipient], $message, $senderId, $userId);
    }

    /**
     * Send SMS to multiple recipients.
     */
    public function sendBulkSms(int $tenantId, array $recipients, string $message, string $senderId = null, int $userId = null): array
    {
        try {
            // Check if tenant has messaging enabled (superadmin bypasses this check)
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                return [
                    'success' => false,
                    'message' => 'Tenant not found'
                ];
            }
            $isSuperAdmin = auth()->check() && in_array(
                strtolower(auth()->user()->role ?? ''),
                ['super_admin', 'superadmin', 'super-admin']
            );
            if (!$isSuperAdmin && !$tenant->messaging_enabled) {
                return [
                    'success' => false,
                    'message' => 'SMS messaging is not enabled for this tenant'
                ];
            }

            // Get SMS balance (authoritative credit store used by admin credits page)
            $smsBalance = SmsBalance::getOrCreateForTenant($tenantId);

            // Calculate SMS cost
            $provider = $this->getBestProvider();
            if (!$provider) {
                return [
                    'success' => false,
                    'message' => 'No SMS provider available'
                ];
            }

            $smsCount = $provider->calculateSmsCount($message);
            $totalSms = count($recipients) * $smsCount;

            // Check balance
            if (!$smsBalance->hasEnoughBalance($totalSms)) {
                return [
                    'success' => false,
                    'message' => 'Insufficient SMS credits. Required: ' . $totalSms . ', Available: ' . $smsBalance->balance
                ];
            }

            // Deduct credits before sending
            DB::transaction(function () use ($smsBalance, $totalSms) {
                $smsBalance->decrement('balance', $totalSms);
                $smsBalance->increment('total_used', $totalSms);
            });

            // Send SMS
            $result = $provider->sendBulkSms($recipients, $message, $senderId);

            // Log SMS
            $smsLog = SmsLog::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'sender_id' => $senderId,
                'message' => $message,
                'recipients' => $recipients,
                'status' => $result['success'] ? 'sent' : 'failed',
                'provider_request_id' => $result['request_id'] ?? null,
                'provider' => $provider->getProviderName(),
                'cost' => $totalSms,
                'message_type' => 'bulk',
                'recipient_count' => count($recipients),
                'provider_response' => $result['data'] ?? null,
                'error' => $result['success'] ? null : ($result['error'] ?? null),
                'sent_at' => $result['success'] ? now() : null,
                'failed_at' => $result['success'] ? null : now(),
            ]);

            if (!$result['success']) {
                // Refund credits on failure
                DB::transaction(function () use ($smsBalance, $totalSms) {
                    $smsBalance->increment('balance', $totalSms);
                    $smsBalance->decrement('total_used', $totalSms);
                });
            }

            return array_merge($result, [
                'sms_log_id' => $smsLog->id,
                'credits_used' => $totalSms,
                'remaining_balance' => $smsBalance->fresh()->balance
            ]);

        } catch (\Exception $e) {
            Log::error('SMS Manager error', [
                'tenant_id' => $tenantId,
                'recipients' => $recipients,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'SMS service error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get delivery status for an SMS.
     */
    public function getDeliveryStatus(string $requestId, string $provider = null): array
    {
        if ($provider && isset($this->providers[$provider])) {
            return $this->providers[$provider]->getDeliveryStatus($requestId);
        }

        // Try all providers if provider not specified
        foreach ($this->providers as $providerInstance) {
            $result = $providerInstance->getDeliveryStatus($requestId);
            if ($result['success']) {
                return $result;
            }
        }

        return [
            'success' => false,
            'message' => 'Could not get delivery status'
        ];
    }

    /**
     * Get provider balance.
     */
    public function getProviderBalance(string $providerName): array
    {
        if (!isset($this->providers[$providerName])) {
            return [
                'success' => false,
                'message' => 'Provider not found'
            ];
        }

        return $this->providers[$providerName]->getBalance();
    }

    /**
     * Get provider sender IDs.
     */
    public function getProviderSenderIds(string $providerName): array
    {
        if (!isset($this->providers[$providerName])) {
            return [
                'success' => false,
                'message' => 'Provider not found'
            ];
        }

        if (!method_exists($this->providers[$providerName], 'getSenderIds')) {
            return [
                'success' => false,
                'message' => 'Provider does not support fetching sender IDs'
            ];
        }

        return $this->providers[$providerName]->getSenderIds();
    }

    /**
     * Test provider connection.
     */
    public function testProvider(string $providerName): array
    {
        if (!isset($this->providers[$providerName])) {
            return [
                'success' => false,
                'message' => 'Provider not found'
            ];
        }

        return $this->providers[$providerName]->testConnection();
    }

    /**
     * Get all available providers.
     */
    public function getProviders(): array
    {
        return array_keys($this->providers);
    }

    /**
     * Add credits to tenant wallet.
     */
    public function addCredits(int $tenantId, int $credits, string $description = 'Manual credit'): bool
    {
        try {
            $wallet = SmsWallet::firstOrCreate(
                ['tenant_id' => $tenantId],
                ['balance' => 0]
            );

            DB::transaction(function () use ($wallet, $credits, $description) {
                $wallet->increment('balance', $credits);
                
                // Update ledger
                $ledger = $wallet->ledger ?? [];
                $ledger[] = [
                    'type' => 'credit',
                    'amount' => $credits,
                    'description' => $description,
                    'timestamp' => now()->toISOString()
                ];
                $wallet->update(['ledger' => $ledger]);
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Error adding SMS credits', [
                'tenant_id' => $tenantId,
                'credits' => $credits,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Deduct credits from tenant wallet.
     */
    public function deductCredits(int $tenantId, int $credits, string $description = 'Manual deduction'): bool
    {
        try {
            $wallet = SmsWallet::where('tenant_id', $tenantId)->first();
            if (!$wallet || $wallet->balance < $credits) {
                return false;
            }

            DB::transaction(function () use ($wallet, $credits, $description) {
                $wallet->decrement('balance', $credits);
                
                // Update ledger
                $ledger = $wallet->ledger ?? [];
                $ledger[] = [
                    'type' => 'debit',
                    'amount' => $credits,
                    'description' => $description,
                    'timestamp' => now()->toISOString()
                ];
                $wallet->update(['ledger' => $ledger]);
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Error deducting SMS credits', [
                'tenant_id' => $tenantId,
                'credits' => $credits,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get tenant wallet balance.
     */
    public function getWalletBalance(int $tenantId): int
    {
        $wallet = SmsWallet::where('tenant_id', $tenantId)->first();
        return $wallet ? $wallet->balance : 0;
    }
}