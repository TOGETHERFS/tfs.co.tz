<?php

namespace App\Services;

use App\Models\Loan;
use App\Models\Repayment;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\Sms\SmsSenderId;
use App\Services\Sms\BeemAfricaService;
use Illuminate\Support\Facades\Log;

/**
 * NotificationSmsService
 *
 * Handles all automatic SMS notifications:
 *  1. Loan disbursement to borrower  (tenant sender ID, tenant credits)
 *  2. Repayment confirmation to borrower (tenant sender ID, tenant credits)
 *  3. Subscription payment confirmation to tenant admin (PHIDTECH sender, system credits)
 *  4. SMS package activation confirmation to tenant admin (PHIDTECH sender, system credits)
 *  5. Welcome SMS to new tenant admin on registration (PHIDTECH sender, system credits)
 */
class NotificationSmsService
{
    protected BeemAfricaService $beem;

    public function __construct(BeemAfricaService $beem)
    {
        $this->beem = $beem;
    }

    // ─── 1. Loan Disbursement ─────────────────────────────────────────────────

    public function sendLoanDisbursedSms(Loan $loan): void
    {
        try {
            $client = $loan->client;
            if (!$client || !$client->phone) {
                Log::channel('sms')->warning('Loan disbursement SMS skipped: no client phone', ['loan_id' => $loan->id]);
                return;
            }

            $senderId = $this->getTenantSenderId($loan->tenant_id);
            if (!$senderId) {
                Log::channel('sms')->warning('Loan disbursement SMS skipped: no approved sender ID for tenant', ['tenant_id' => $loan->tenant_id]);
                return;
            }

            $tenant  = Tenant::find($loan->tenant_id);
            $name    = trim($client->first_name . ' ' . $client->last_name);
            $amount  = number_format((float) $loan->total_amount, 2);
            $balance = number_format((float) $loan->outstanding_balance, 2);
            $loanNo  = $loan->loan_number ?? ('LN' . $loan->id);
            $org     = $tenant->name ?? 'Your MFI';

            $message = "Dear {$name}, your loan {$loanNo} of TZS {$amount} has been disbursed. "
                     . "Outstanding balance: TZS {$balance}. "
                     . "- {$org}";

            $this->sendViaTenantCredits($loan->tenant_id, $senderId, $client->phone, $message, 'loan_disbursed', $loan->id);

        } catch (\Throwable $e) {
            Log::channel('sms')->error('sendLoanDisbursedSms failed', ['loan_id' => $loan->id, 'error' => $e->getMessage()]);
        }
    }

    // ─── 2. Repayment Confirmation ────────────────────────────────────────────

    public function sendRepaymentConfirmationSms(Repayment $repayment): void
    {
        try {
            $loan   = $repayment->loan;
            $client = $loan?->client;

            if (!$client || !$client->phone) {
                Log::channel('sms')->warning('Repayment SMS skipped: no client phone', ['repayment_id' => $repayment->id]);
                return;
            }

            $tenantId = $repayment->tenant_id ?? $loan->tenant_id;
            $senderId = $this->getTenantSenderId($tenantId);
            if (!$senderId) {
                Log::channel('sms')->warning('Repayment SMS skipped: no approved sender ID for tenant', ['tenant_id' => $tenantId]);
                return;
            }

            $tenant    = Tenant::find($tenantId);
            $name      = trim($client->first_name . ' ' . $client->last_name);
            $paid      = number_format((float) $repayment->amount, 2);
            $remaining = number_format((float) max(0, $loan->outstanding_balance), 2);
            $loanNo    = $loan->loan_number ?? ('LN' . $loan->id);
            $receipt   = $repayment->receipt_number ?? $repayment->id;
            $org       = $tenant->name ?? 'Your MFI';

            $message = "Dear {$name}, payment of TZS {$paid} received for loan {$loanNo}. "
                     . "Receipt: {$receipt}. Remaining balance: TZS {$remaining}. "
                     . "- {$org}";

            $this->sendViaTenantCredits($tenantId, $senderId, $client->phone, $message, 'repayment_confirmation', null, $loan->id);

        } catch (\Throwable $e) {
            Log::channel('sms')->error('sendRepaymentConfirmationSms failed', ['repayment_id' => $repayment->id, 'error' => $e->getMessage()]);
        }
    }

    // ─── 3. Subscription Payment Confirmation to Tenant Admin ────────────────

    public function sendSubscriptionPaymentSms(Tenant $tenant, float $amount, string $planName, string $renewsUntil): void
    {
        try {
            $paid    = number_format($amount, 2);
            $message = "PHIDTECH ALERT: Subscription payment received. "
                     . "Tenant: {$tenant->name} | Plan: {$planName} | Amount: TZS {$paid} | Active until: {$renewsUntil}.";

            $this->sendViaSystem('PHIDTECH', self::SUPERADMIN_PHONE, $message, 'subscription_payment', $tenant->id);

        } catch (\Throwable $e) {
            Log::channel('sms')->error('sendSubscriptionPaymentSms failed', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
        }
    }

    // ─── 4. SMS Package Activation Confirmation to Tenant Admin ──────────────

    public function sendSmsPackageActivatedSms(Tenant $tenant, int $quantity, float $amount): void
    {
        try {
            $phone = $tenant->phone;
            if (!$phone) {
                Log::channel('sms')->warning('SMS package SMS skipped: tenant has no phone', ['tenant_id' => $tenant->id]);
                return;
            }

            $paid    = number_format($amount, 2);
            $qty     = number_format($quantity);
            $message = "PHIDTECH: Dear {$tenant->name}, your payment of TZS {$paid} has been received. "
                     . "{$qty} SMS credits have been added to your account and are ready to use. "
                     . "Thank you - PHIDTECH.";

            $this->sendViaSystem('PHIDTECH', $phone, $message, 'sms_package_activated', $tenant->id);

        } catch (\Throwable $e) {
            Log::channel('sms')->error('sendSmsPackageActivatedSms failed', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
        }
    }

    // ─── 5. Welcome SMS to New Tenant ─────────────────────────────────────────

    public function sendWelcomeSms(Tenant $tenant, string $email, string $password, string $phone): void
    {
        try {
            $message = "PHIDTECH ALERT: New tenant registered. "
                     . "Name: {$tenant->name} | Email: {$email} | Phone: {$phone}.";

            $this->sendViaSystem('PHIDTECH', self::SUPERADMIN_PHONE, $message, 'welcome', $tenant->id);

        } catch (\Throwable $e) {
            Log::channel('sms')->error('sendWelcomeSms failed', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
        }
    }

    // ─── Superadmin Alerts (always to 255682188544) ───────────────────────────

    const SUPERADMIN_PHONE = '255682188544';

    public function notifySuperadminNewTenant(Tenant $tenant, string $email, string $phone): void
    {
        try {
            $message = "PHIDTECH ALERT: New tenant registered. "
                     . "Name: {$tenant->name} | Email: {$email} | Phone: {$phone}. "
                     . "Review at phidlms.co.tz/admin";

            $this->sendViaSystem('PHIDTECH', self::SUPERADMIN_PHONE, $message, 'superadmin_new_tenant', $tenant->id);
        } catch (\Throwable $e) {
            Log::channel('sms')->error('notifySuperadminNewTenant failed', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
        }
    }

    public function notifySuperadminSubscriptionPayment(Tenant $tenant, float $amount, string $planName): void
    {
        try {
            $paid = number_format($amount, 2);
            $message = "PHIDTECH ALERT: Subscription payment received. "
                     . "Tenant: {$tenant->name} | Plan: {$planName} | Amount: TZS {$paid}.";

            $this->sendViaSystem('PHIDTECH', self::SUPERADMIN_PHONE, $message, 'superadmin_subscription_payment', $tenant->id);
        } catch (\Throwable $e) {
            Log::channel('sms')->error('notifySuperadminSubscriptionPayment failed', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
        }
    }

    public function notifySuperadminSmsPackagePayment(Tenant $tenant, int $quantity, float $amount): void
    {
        try {
            $paid = number_format($amount, 2);
            $qty  = number_format($quantity);
            $message = "PHIDTECH ALERT: SMS package payment received. "
                     . "Tenant: {$tenant->name} | Credits: {$qty} | Amount: TZS {$paid}.";

            $this->sendViaSystem('PHIDTECH', self::SUPERADMIN_PHONE, $message, 'superadmin_sms_package_payment', $tenant->id);
        } catch (\Throwable $e) {
            Log::channel('sms')->error('notifySuperadminSmsPackagePayment failed', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Get the active sender ID for a tenant (falls back to any active system sender).
     */
    protected function getTenantSenderId(int $tenantId): ?string
    {
        $sid = SmsSenderId::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->first();

        if ($sid) return $sid->sender_id;

        // fallback: system-wide approved sender IDs
        $system = SmsSenderId::whereNull('tenant_id')
            ->where('is_active', true)
            ->first();

        return $system?->sender_id;
    }

    /**
     * Send using tenant's own SMS credits (deducts from SmsBalance).
     */
    protected function sendViaTenantCredits(int $tenantId, string $senderId, string $phone, string $message, string $type, ?int $disbursementLoanId = null, ?int $loanId = null): void
    {
        try {
            $balance = \App\Models\Sms\SmsBalance::getOrCreateForTenant($tenantId);
            $smsCount = (int) ceil(strlen($message) / 160);

            if (!$balance->hasEnoughBalance($smsCount)) {
                Log::channel('sms')->warning("Insufficient tenant SMS credits for {$type}", [
                    'tenant_id' => $tenantId,
                    'required' => $smsCount,
                    'available' => $balance->balance,
                ]);
                return;
            }

            $result = $this->beem->sendSms($senderId, $phone, $message);

            if ($result['success']) {
                $balance->deduct($smsCount);
                $this->logSms($tenantId, $senderId, $phone, $message, 'sent', $type, $loanId ?? $disbursementLoanId, $result);
            } else {
                $this->logSms($tenantId, $senderId, $phone, $message, 'failed', $type, $loanId ?? $disbursementLoanId, $result);
            }
        } catch (\Throwable $e) {
            Log::channel('sms')->error("sendViaTenantCredits failed [{$type}]", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send directly via Beem Africa (system-level, no tenant credit deduction).
     * Used for PHIDTECH notifications (subscription, SMS package, welcome).
     */
    protected function sendViaSystem(string $senderId, string $phone, string $message, string $type, int $tenantId): void
    {
        try {
            $result = $this->beem->sendSms($senderId, $phone, $message);

            $status = $result['success'] ? 'sent' : 'failed';
            $this->logSms(null, $senderId, $phone, $message, $status, $type, null, $result, $tenantId);

            if (!$result['success']) {
                Log::channel('sms')->warning("System SMS failed [{$type}]", ['phone' => $phone, 'error' => $result['error'] ?? '']);
            }
        } catch (\Throwable $e) {
            Log::channel('sms')->error("sendViaSystem failed [{$type}]", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Persist SMS log into sms_messages table.
     */
    protected function logSms(?int $tenantId, string $senderId, string $phone, string $message, string $status, string $type, ?int $loanId, array $result, ?int $contextTenantId = null): void
    {
        try {
            \App\Models\Sms\SmsMessage::create([
                'tenant_id'   => $tenantId ?? $contextTenantId,
                'user_id'     => null,
                'sender_id'   => $senderId,
                'recipient'   => $phone,
                'message'     => $message,
                'sms_count'   => (int) ceil(strlen($message) / 160),
                'status'      => $status,
                'message_type'=> $type,
                'loan_id'     => $loanId,
                'provider_message_id' => $result['message_id'] ?? $result['provider_message_id'] ?? null,
                'provider_response'   => isset($result['response']) ? json_encode($result['response']) : null,
                'failure_reason'      => $result['error'] ?? null,
                'sent_at'     => $status === 'sent' ? now() : null,
            ]);
        } catch (\Throwable $e) {
            Log::channel('sms')->warning('SMS log write failed', ['error' => $e->getMessage()]);
        }
    }
}
