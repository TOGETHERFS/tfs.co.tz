<?php

namespace App\Services\Sms;

use App\Models\Sms\SmsProviderSetting;
use App\Models\Sms\SmsSenderId;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BeemAfricaService
{
    protected ?SmsProviderSetting $settings;
    protected string $baseUrl = 'https://apisms.beem.africa';
    protected string $otp_url = 'https://apiotp.beem.africa';

    public function __construct()
    {
        try {
            // Try new sms_provider_settings table first, fallback to sms_providers table
            $this->settings = SmsProviderSetting::getBeemAfrica();

            // If not found in sms_provider_settings, try sms_providers table
            if (!$this->settings) {
                $provider = \App\Models\SmsProvider::where('name', 'beem_africa')
                    ->where('is_active', true)
                    ->first();

                if ($provider && $provider->config) {
                    // Create a temporary SmsProviderSetting object from SmsProvider config
                    $this->settings = new SmsProviderSetting();
                    $this->settings->provider = 'beem_africa';
                    $this->settings->is_active = true;
                    $this->settings->api_key = $provider->config['api_key'] ?? null;
                    $this->settings->secret_key = $provider->config['secret_key'] ?? null;
                }
            }
        } catch (\Exception $e) {
            $this->settings = null;
        }
    }

    protected function getAuthHeader(): string
    {
        if (!$this->settings) {
            throw new \Exception('Beem Africa settings not configured');
        }
        return 'Basic ' . base64_encode($this->settings->api_key . ':' . $this->settings->secret_key);
    }

    protected function makeRequest(string $method, string $endpoint, array $data = [], string $baseUrl = null): array
    {
        if (!$this->settings || !$this->settings->api_key || !$this->settings->secret_key) {
            throw new \Exception('Beem Africa API credentials not configured');
        }

        $url = ($baseUrl ?? $this->baseUrl) . $endpoint;

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->getAuthHeader(),
                'Content-Type' => 'application/json',
            ])->timeout(30);

            if ($method === 'GET') {
                $response = $response->get($url, $data);
            } else {
                $response = $response->post($url, $data);
            }

            $result = $response->json();

            Log::channel('sms')->info('Beem Africa API Response', [
                'endpoint' => $endpoint,
                'url' => $url,
                'status' => $response->status(),
                'response' => $result,
            ]);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'data' => $result,
            ];
        } catch (\Exception $e) {
            Log::channel('sms')->error('Beem Africa API Error', [
                'endpoint' => $endpoint,
                'url' => $url ?? '',
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 0,
                'error' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Get SMS credit balance from Beem Africa.
     * Endpoint: GET https://apisms.beem.africa/public/v1/vendors/balance
     */
    public function getBalance(): array
    {
        try {
            $response = $this->makeRequest('GET', '/public/v1/vendors/balance');

            // Handle different response structures from Beem Africa
            if ($response['success']) {
                $data = $response['data'];
                $balance = 0;
                
                // Try different response formats
                if (isset($data['data']['credit_balance'])) {
                    $balance = (int) $data['data']['credit_balance'];
                } elseif (isset($data['credit_balance'])) {
                    $balance = (int) $data['credit_balance'];
                } elseif (isset($data['balance'])) {
                    $balance = (int) $data['balance'];
                }

                if ($this->settings) {
                    $this->settings->updateBalance($balance);
                }
                
                return [
                    'success' => true,
                    'balance' => $balance,
                    'raw_response' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => $response['error'] ?? $response['data']['message'] ?? 'Failed to fetch balance',
                'balance' => $this->settings->provider_balance ?? 0,
            ];
        } catch (\Exception $e) {
            Log::channel('sms')->error('Failed to get Beem Africa balance', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'balance' => $this->settings->provider_balance ?? 0,
            ];
        }
    }

    /**
     * Get registered sender IDs from Beem Africa.
     * Endpoint: GET https://apisms.beem.africa/public/v1/vendors/senderids
     */
    public function getSenderIds(): array
    {
        try {
            $response = $this->makeRequest('GET', '/public/v1/vendors/senderids');

            if ($response['success']) {
                $data = $response['data'];
                $senderIds = [];
                
                // Try different response formats
                if (isset($data['data']) && is_array($data['data'])) {
                    $senderIds = $data['data'];
                } elseif (isset($data['senderids']) && is_array($data['senderids'])) {
                    $senderIds = $data['senderids'];
                } elseif (is_array($data) && !isset($data['code'])) {
                    // Direct array response
                    $senderIds = $data;
                }

                return [
                    'success' => true,
                    'sender_ids' => $senderIds,
                    'raw_response' => $data,
                ];
            }

            return [
                'success' => false,
                'error' => $response['error'] ?? $response['data']['message'] ?? 'Failed to fetch sender IDs',
                'sender_ids' => [],
            ];
        } catch (\Exception $e) {
            Log::channel('sms')->error('Failed to get Beem Africa sender IDs', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'sender_ids' => [],
            ];
        }
    }

    /**
     * Sync sender IDs from Beem Africa to local database.
     */
    public function syncSenderIdsToDatabase(): array
    {
        $result = $this->getSenderIds();
        
        if (!$result['success']) {
            return $result;
        }

        $synced = 0;
        foreach ($result['sender_ids'] as $senderData) {
            // Extract sender ID - handle different response formats
            $senderId = $senderData['senderid'] ?? $senderData['sender_id'] ?? $senderData['name'] ?? null;
            $status = $senderData['status'] ?? 'unknown';
            
            if ($senderId) {
                SmsSenderId::updateOrCreate(
                    ['provider_id' => $senderId],
                    [
                        'sender_id' => $senderId,
                        'is_active' => in_array(strtolower($status), ['approved', 'active', '1', 'true']),
                        'provider_status' => $status,
                    ]
                );
                $synced++;
            }
        }

        return [
            'success' => true,
            'synced_count' => $synced,
            'sender_ids' => $result['sender_ids'],
        ];
    }

    public function sendSms(string $senderId, string $recipient, string $message): array
    {
        try {
            if (!$this->settings || !$this->settings->api_key || !$this->settings->secret_key) {
                Log::channel('sms')->error('Beem Africa credentials not configured');
                return [
                    'success' => false,
                    'error' => 'Beem Africa API credentials not configured. Please configure in SMS Provider settings.',
                ];
            }

            $recipient = $this->formatPhoneNumber($recipient);

            $data = [
                'source_addr' => $senderId,
                'encoding' => 0,
                'schedule_time' => '',
                'message' => $message,
                'recipients' => [
                    ['recipient_id' => 1, 'dest_addr' => $recipient]
                ],
            ];

            Log::channel('sms')->info('Sending SMS via Beem Africa', [
                'sender_id' => $senderId,
                'recipient' => $recipient,
                'message_length' => strlen($message),
            ]);

            $response = $this->makeRequest('POST', '/v1/send', $data);

            if ($response['success'] && isset($response['data']['successful'])) {
                Log::channel('sms')->info('SMS sent successfully', [
                    'request_id' => $response['data']['request_id'] ?? null,
                ]);
                return [
                    'success' => true,
                    'message_id' => $response['data']['request_id'] ?? null,
                    'response' => $response['data'],
                ];
            }

            $errorMsg = $response['data']['message'] ?? $response['error'] ?? 'Failed to send SMS';
            Log::channel('sms')->error('SMS send failed', [
                'error' => $errorMsg,
                'response' => $response,
            ]);

            return [
                'success' => false,
                'error' => $errorMsg,
                'response' => $response['data'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::channel('sms')->error('SMS send exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [
                'success' => false,
                'error' => 'SMS sending failed: ' . $e->getMessage(),
            ];
        }
    }

    public function sendBulkSms(string $senderId, array $recipients, string $message): array
    {
        $formattedRecipients = [];
        foreach ($recipients as $index => $recipient) {
            $formattedRecipients[] = [
                'recipient_id' => $index + 1,
                'dest_addr' => $this->formatPhoneNumber($recipient),
            ];
        }

        $data = [
            'source_addr' => $senderId,
            'encoding' => 0,
            'schedule_time' => '',
            'message' => $message,
            'recipients' => $formattedRecipients,
        ];

        $response = $this->makeRequest('POST', '/v1/send', $data);

        if ($response['success']) {
            return [
                'success' => true,
                'message_id' => $response['data']['request_id'] ?? null,
                'response' => $response['data'],
            ];
        }

        return [
            'success' => false,
            'error' => $response['data']['message'] ?? $response['error'] ?? 'Failed to send bulk SMS',
            'response' => $response['data'] ?? null,
        ];
    }

    public function getDeliveryReport(string $requestId): array
    {
        $response = $this->makeRequest('GET', '/public/v1/delivery-reports', [
            'request_id' => $requestId,
        ]);

        if ($response['success'] && isset($response['data']['data'])) {
            return [
                'success' => true,
                'reports' => $response['data']['data'],
            ];
        }

        return [
            'success' => false,
            'error' => $response['error'] ?? 'Failed to fetch delivery report',
            'reports' => [],
        ];
    }

    protected function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($phone) === 9) {
            $phone = '255' . $phone;
        } elseif (strlen($phone) === 10 && str_starts_with($phone, '0')) {
            $phone = '255' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '255') && strlen($phone) === 12) {
            // Already in correct format
        }

        return $phone;
    }

    public function validatePhoneNumber(string $phone): bool
    {
        $formatted = $this->formatPhoneNumber($phone);
        return strlen($formatted) === 12 && str_starts_with($formatted, '255');
    }

    public function isConfigured(): bool
    {
        return $this->settings 
            && $this->settings->api_key 
            && $this->settings->secret_key 
            && $this->settings->is_active;
    }

    public function getSettings(): ?SmsProviderSetting
    {
        return $this->settings;
    }
}
