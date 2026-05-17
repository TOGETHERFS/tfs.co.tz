<?php

namespace App\Services\Sms;

use App\Models\Sms\SmsBalance;
use App\Models\Sms\SmsMessage;
use App\Models\Sms\SmsSenderId;
use App\Models\Sms\SmsTransaction;
use App\Models\Sms\SmsTemplate;
use App\Models\Client;
use App\Models\Loan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected BeemAfricaService $beemService;
    protected int $tenantId;
    protected int $userId;

    public function __construct(BeemAfricaService $beemService)
    {
        $this->beemService = $beemService;
    }

    public function setContext(int $tenantId, int $userId): self
    {
        $this->tenantId = $tenantId;
        $this->userId = $userId;
        return $this;
    }

    public function getBalance(): SmsBalance
    {
        return SmsBalance::getOrCreateForTenant($this->tenantId);
    }

    public function hasEnoughBalance(int $count = 1): bool
    {
        return $this->getBalance()->hasEnoughBalance($count);
    }

    public function getSenderIds(): array
    {
        return SmsSenderId::where('tenant_id', $this->tenantId)
            ->where('is_active', true)
            ->get()
            ->toArray();
    }

    public function getDefaultSenderId(): ?string
    {
        $senderId = SmsSenderId::getDefaultForTenant($this->tenantId);
        if ($senderId) {
            return $senderId->sender_id;
        }

        $systemDefault = SmsSenderId::getSystemDefault();
        return $systemDefault?->sender_id;
    }

    public function sendSingle(string $recipient, string $message, ?string $senderId = null, array $options = []): array
    {
        $senderId = $senderId ?? $this->getDefaultSenderId();
        
        if (!$senderId) {
            return ['success' => false, 'error' => 'No sender ID available'];
        }

        if (!$this->beemService->validatePhoneNumber($recipient)) {
            return ['success' => false, 'error' => 'Invalid phone number format'];
        }

        $smsCount = SmsMessage::calculateSmsCount($message);
        $balance = $this->getBalance();

        if (!$balance->hasEnoughBalance($smsCount)) {
            return ['success' => false, 'error' => 'Insufficient SMS balance'];
        }

        DB::beginTransaction();
        try {
            // Create message record
            $smsMessage = SmsMessage::create([
                'tenant_id' => $this->tenantId,
                'user_id' => $this->userId,
                'sender_id' => $senderId,
                'recipient' => $recipient,
                'message' => $message,
                'sms_count' => $smsCount,
                'status' => SmsMessage::STATUS_QUEUED,
                'message_type' => $options['type'] ?? SmsMessage::TYPE_SINGLE,
                'client_id' => $options['client_id'] ?? null,
                'loan_id' => $options['loan_id'] ?? null,
                'batch_id' => $options['batch_id'] ?? null,
            ]);

            // Deduct balance
            $balanceBefore = $balance->balance;
            $balance->deduct($smsCount);

            // Record transaction
            SmsTransaction::recordUsage(
                $this->tenantId,
                $this->userId,
                $smsCount,
                $balanceBefore,
                "SMS to {$recipient}"
            );

            // Send via Beem Africa
            $result = $this->beemService->sendSms($senderId, $recipient, $message);

            if ($result['success']) {
                $smsMessage->markAsSent(
                    $result['message_id'] ?? '',
                    json_encode($result['response'] ?? [])
                );
                DB::commit();

                return [
                    'success' => true,
                    'message_id' => $smsMessage->id,
                    'provider_message_id' => $result['message_id'],
                ];
            } else {
                // Refund on failure
                $balance->refund($smsCount);
                $balance->recordFailed($smsCount);
                $smsMessage->markAsFailed(
                    $result['error'] ?? 'Unknown error',
                    json_encode($result['response'] ?? [])
                );
                DB::commit();

                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to send SMS',
                    'message_id' => $smsMessage->id,
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('sms')->error('SMS Send Error', [
                'tenant_id' => $this->tenantId,
                'recipient' => $recipient,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => 'System error: ' . $e->getMessage()];
        }
    }

    public function sendBulk(array $recipients, string $message, ?string $senderId = null, array $options = []): array
    {
        $senderId = $senderId ?? $this->getDefaultSenderId();
        
        if (!$senderId) {
            return ['success' => false, 'error' => 'No sender ID available'];
        }

        // Validate and deduplicate recipients
        $validRecipients = [];
        $invalidRecipients = [];
        $seen = [];

        foreach ($recipients as $recipient) {
            $phone = is_array($recipient) ? ($recipient['phone'] ?? $recipient['recipient']) : $recipient;
            
            if (isset($seen[$phone])) {
                continue; // Skip duplicates
            }
            $seen[$phone] = true;

            if ($this->beemService->validatePhoneNumber($phone)) {
                $validRecipients[] = [
                    'phone' => $phone,
                    'client_id' => is_array($recipient) ? ($recipient['client_id'] ?? null) : null,
                ];
            } else {
                $invalidRecipients[] = $phone;
            }
        }

        if (empty($validRecipients)) {
            return ['success' => false, 'error' => 'No valid recipients'];
        }

        $smsCount = SmsMessage::calculateSmsCount($message);
        $totalSms = $smsCount * count($validRecipients);
        $balance = $this->getBalance();

        if (!$balance->hasEnoughBalance($totalSms)) {
            return [
                'success' => false,
                'error' => "Insufficient SMS balance. Need {$totalSms}, have {$balance->balance}",
            ];
        }

        $batchId = SmsMessage::generateBatchId();
        $results = [
            'success' => true,
            'batch_id' => $batchId,
            'total' => count($validRecipients),
            'sent' => 0,
            'failed' => 0,
            'invalid' => count($invalidRecipients),
            'messages' => [],
        ];

        foreach ($validRecipients as $recipient) {
            $result = $this->sendSingle($recipient['phone'], $message, $senderId, [
                'type' => $options['type'] ?? SmsMessage::TYPE_BULK,
                'client_id' => $recipient['client_id'],
                'loan_id' => $options['loan_id'] ?? null,
                'batch_id' => $batchId,
            ]);

            if ($result['success']) {
                $results['sent']++;
            } else {
                $results['failed']++;
            }
            $results['messages'][] = $result;
        }

        return $results;
    }

    public function sendToClient(Client $client, string $message, array $options = []): array
    {
        if (!$client->phone) {
            return ['success' => false, 'error' => 'Client has no phone number'];
        }

        return $this->sendSingle($client->phone, $message, null, array_merge($options, [
            'client_id' => $client->id,
        ]));
    }

    public function sendLoanNotification(Loan $loan, string $templateCategory, array $extraData = []): array
    {
        $client = $loan->client;
        if (!$client || !$client->phone) {
            return ['success' => false, 'error' => 'Loan client has no phone number'];
        }

        $template = SmsTemplate::forTenant($this->tenantId)
            ->byCategory($templateCategory)
            ->active()
            ->first();

        if (!$template) {
            return ['success' => false, 'error' => 'No template found for category: ' . $templateCategory];
        }

        $data = array_merge([
            'borrower_name' => $client->first_name . ' ' . $client->last_name,
            'first_name' => $client->first_name,
            'loan_balance' => number_format($loan->outstanding_balance ?? 0, 2),
            'loan_number' => $loan->loan_number,
            'due_amount' => number_format($loan->monthly_payment ?? 0, 2),
        ], $extraData);

        $message = $template->render($data);

        return $this->sendSingle($client->phone, $message, null, [
            'type' => SmsMessage::TYPE_NOTIFICATION,
            'client_id' => $client->id,
            'loan_id' => $loan->id,
        ]);
    }

    public function getMessageHistory(int $limit = 50, array $filters = [])
    {
        $query = SmsMessage::forTenant($this->tenantId)
            ->with(['client', 'user'])
            ->latest();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('message_type', $filters['type']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('recipient', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('message', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->paginate($limit);
    }

    public function getUsageStats(string $period = 'month'): array
    {
        $startDate = match($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $messages = SmsMessage::forTenant($this->tenantId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('status, COUNT(*) as count, SUM(sms_count) as total_sms')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return [
            'total_messages' => $messages->sum('count'),
            'total_sms' => $messages->sum('total_sms'),
            'delivered' => $messages->get(SmsMessage::STATUS_DELIVERED)?->count ?? 0,
            'sent' => $messages->get(SmsMessage::STATUS_SENT)?->count ?? 0,
            'failed' => $messages->get(SmsMessage::STATUS_FAILED)?->count ?? 0,
            'queued' => $messages->get(SmsMessage::STATUS_QUEUED)?->count ?? 0,
        ];
    }
}
