<?php

namespace App\Services;

use App\Models\User;
use App\Models\Tenant;
use App\Models\SenderId;
use App\Models\SmsWallet;
use App\Models\SmsTopup;
use App\Notifications\SmsWalletLowBalanceNotification;
use App\Notifications\SmsWalletTopupSuccessNotification;
use App\Notifications\SmsWalletTopupFailedNotification;
use App\Notifications\SenderIdStatusNotification;
use App\Notifications\SmsWalletAutoTopupNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;

class SmsNotificationService
{
    /**
     * Send low balance notification to tenant users.
     */
    public function sendLowBalanceNotification(SmsWallet $wallet): void
    {
        try {
            $tenant = $wallet->tenant;
            if (!$tenant) {
                return;
            }

            // Get users who should receive notifications (managers and admins)
            $users = $tenant->users()
                ->whereIn('role', ['manager', 'admin'])
                ->where('is_active', true)
                ->get();

            if ($users->isEmpty()) {
                Log::warning("No users found to notify for low balance in tenant {$tenant->id}");
                return;
            }

            // Send notification to each user
            foreach ($users as $user) {
                $user->notify(new SmsWalletLowBalanceNotification($wallet));
            }

            Log::info("Low balance notification sent", [
                'tenant_id' => $tenant->id,
                'wallet_id' => $wallet->id,
                'balance' => $wallet->balance,
                'threshold' => $wallet->low_balance_threshold,
                'users_notified' => $users->count()
            ]);

        } catch (\Exception $e) {
            Log::error("Error sending low balance notification", [
                'wallet_id' => $wallet->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send topup success notification.
     */
    public function sendTopupSuccessNotification(SmsTopup $topup): void
    {
        try {
            $tenant = $topup->tenant;
            if (!$tenant) {
                return;
            }

            // Get users who should receive notifications
            $users = $tenant->users()
                ->whereIn('role', ['manager', 'admin'])
                ->where('is_active', true)
                ->get();

            if ($users->isEmpty()) {
                return;
            }

            // Send notification to each user
            foreach ($users as $user) {
                $user->notify(new SmsWalletTopupSuccessNotification($topup));
            }

            Log::info("Topup success notification sent", [
                'tenant_id' => $tenant->id,
                'topup_id' => $topup->id,
                'amount' => $topup->amount,
                'credits' => $topup->credits_purchased,
                'users_notified' => $users->count()
            ]);

        } catch (\Exception $e) {
            Log::error("Error sending topup success notification", [
                'topup_id' => $topup->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send topup failed notification.
     */
    public function sendTopupFailedNotification(SmsTopup $topup): void
    {
        try {
            $tenant = $topup->tenant;
            if (!$tenant) {
                return;
            }

            // Get users who should receive notifications
            $users = $tenant->users()
                ->whereIn('role', ['manager', 'admin'])
                ->where('is_active', true)
                ->get();

            if ($users->isEmpty()) {
                return;
            }

            // Send notification to each user
            foreach ($users as $user) {
                $user->notify(new SmsWalletTopupFailedNotification($topup));
            }

            Log::info("Topup failed notification sent", [
                'tenant_id' => $tenant->id,
                'topup_id' => $topup->id,
                'amount' => $topup->amount,
                'users_notified' => $users->count()
            ]);

        } catch (\Exception $e) {
            Log::error("Error sending topup failed notification", [
                'topup_id' => $topup->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send sender ID status notification.
     */
    public function sendSenderIdStatusNotification(SenderId $senderId, string $previousStatus = null): void
    {
        try {
            $tenant = $senderId->tenant;
            if (!$tenant) {
                return;
            }

            // Get users who should receive notifications
            $users = $tenant->users()
                ->whereIn('role', ['manager', 'admin'])
                ->where('is_active', true)
                ->get();

            if ($users->isEmpty()) {
                return;
            }

            // Send notification to each user
            foreach ($users as $user) {
                $user->notify(new SenderIdStatusNotification($senderId, $previousStatus));
            }

            Log::info("Sender ID status notification sent", [
                'tenant_id' => $tenant->id,
                'sender_id' => $senderId->id,
                'sender_id_name' => $senderId->sender_id,
                'status' => $senderId->status,
                'previous_status' => $previousStatus,
                'users_notified' => $users->count()
            ]);

        } catch (\Exception $e) {
            Log::error("Error sending sender ID status notification", [
                'sender_id' => $senderId->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send auto topup notification.
     */
    public function sendAutoTopupNotification(SmsWallet $wallet, SmsTopup $topup): void
    {
        try {
            $tenant = $wallet->tenant;
            if (!$tenant) {
                return;
            }

            // Get users who should receive notifications
            $users = $tenant->users()
                ->whereIn('role', ['manager', 'admin'])
                ->where('is_active', true)
                ->get();

            if ($users->isEmpty()) {
                return;
            }

            // Send notification to each user
            foreach ($users as $user) {
                $user->notify(new SmsWalletAutoTopupNotification($wallet, $topup));
            }

            Log::info("Auto topup notification sent", [
                'tenant_id' => $tenant->id,
                'wallet_id' => $wallet->id,
                'topup_id' => $topup->id,
                'amount' => $topup->amount,
                'users_notified' => $users->count()
            ]);

        } catch (\Exception $e) {
            Log::error("Error sending auto topup notification", [
                'wallet_id' => $wallet->id,
                'topup_id' => $topup->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send bulk notification to multiple tenants.
     */
    public function sendBulkNotification(array $tenantIds, string $subject, string $message, array $channels = ['database']): void
    {
        try {
            $tenants = Tenant::whereIn('id', $tenantIds)->get();
            
            foreach ($tenants as $tenant) {
                $users = $tenant->users()
                    ->whereIn('role', ['manager', 'admin'])
                    ->where('is_active', true)
                    ->get();

                foreach ($users as $user) {
                    $user->notify(new \App\Notifications\BulkSmsNotification($subject, $message, $channels));
                }
            }

            Log::info("Bulk notification sent", [
                'tenant_count' => count($tenantIds),
                'subject' => $subject,
                'channels' => $channels
            ]);

        } catch (\Exception $e) {
            Log::error("Error sending bulk notification", [
                'tenant_ids' => $tenantIds,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check and send low balance notifications for all wallets.
     */
    public function checkAndSendLowBalanceNotifications(): void
    {
        try {
            // Get wallets with low balance that haven't been notified recently
            $lowBalanceWallets = SmsWallet::whereRaw('balance <= low_balance_threshold')
                ->where('low_balance_notifications', true)
                ->where(function ($query) {
                    $query->whereNull('last_low_balance_notification')
                        ->orWhere('last_low_balance_notification', '<', now()->subHours(24));
                })
                ->get();

            foreach ($lowBalanceWallets as $wallet) {
                $this->sendLowBalanceNotification($wallet);
                
                // Update last notification timestamp
                $wallet->update([
                    'last_low_balance_notification' => now()
                ]);
            }

            Log::info("Low balance notifications check completed", [
                'wallets_checked' => $lowBalanceWallets->count()
            ]);

        } catch (\Exception $e) {
            Log::error("Error checking low balance notifications", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send daily SMS usage summary to tenants.
     */
    public function sendDailyUsageSummary(): void
    {
        try {
            $tenants = Tenant::whereHas('smsWallet')->get();
            
            foreach ($tenants as $tenant) {
                $wallet = $tenant->smsWallet;
                if (!$wallet || !$wallet->daily_summary_enabled) {
                    continue;
                }

                // Get yesterday's usage
                $yesterday = now()->subDay();
                $usage = $wallet->transactions()
                    ->where('type', 'debit')
                    ->whereDate('created_at', $yesterday)
                    ->sum('amount');

                if ($usage > 0) {
                    $users = $tenant->users()
                        ->whereIn('role', ['manager', 'admin'])
                        ->where('is_active', true)
                        ->get();

                    foreach ($users as $user) {
                        $user->notify(new \App\Notifications\DailySmsUsageSummaryNotification($wallet, $usage, $yesterday));
                    }
                }
            }

            Log::info("Daily usage summary notifications sent");

        } catch (\Exception $e) {
            Log::error("Error sending daily usage summary", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send weekly SMS usage report to tenants.
     */
    public function sendWeeklyUsageReport(): void
    {
        try {
            $tenants = Tenant::whereHas('smsWallet')->get();
            
            foreach ($tenants as $tenant) {
                $wallet = $tenant->smsWallet;
                if (!$wallet || !$wallet->weekly_report_enabled) {
                    continue;
                }

                // Get last week's usage
                $lastWeekStart = now()->subWeek()->startOfWeek();
                $lastWeekEnd = now()->subWeek()->endOfWeek();
                
                $usage = $wallet->transactions()
                    ->where('type', 'debit')
                    ->whereBetween('created_at', [$lastWeekStart, $lastWeekEnd])
                    ->sum('amount');

                $users = $tenant->users()
                    ->whereIn('role', ['manager', 'admin'])
                    ->where('is_active', true)
                    ->get();

                foreach ($users as $user) {
                    $user->notify(new \App\Notifications\WeeklySmsUsageReportNotification($wallet, $usage, $lastWeekStart, $lastWeekEnd));
                }
            }

            Log::info("Weekly usage report notifications sent");

        } catch (\Exception $e) {
            Log::error("Error sending weekly usage report", [
                'error' => $e->getMessage()
            ]);
        }
    }
}