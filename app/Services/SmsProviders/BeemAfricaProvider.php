<?php

namespace App\Services\SmsProviders;

use App\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BeemAfricaProvider implements SmsProviderInterface
{
    protected array $config;
    protected string $baseUrl;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->baseUrl = $config['base_url'] ?? 'https://apisms.beem.africa/v1';
    }

    public function sendSms(string $recipient, string $message, string $senderId = null): array
    {
        return $this->sendBulkSms([$recipient], $message, $senderId);
    }

    public function sendBulkSms(array $recipients, string $message, string $senderId = null): array
    {
        try {
            $senderId = $senderId ?: ($this->config['sender_id'] ?? 'PHIDLMS');
            
            $payload = [
                'source_addr' => $senderId,
                'encoding' => 0,
                'schedule_time' => '',
                'message' => $message,
                'recipients' => array_map(function ($recipient, $index) {
                    return [
                        'recipient_id' => $index + 1,
                        'dest_addr' => $this->formatPhoneNumber($recipient)
                    ];
                }, $recipients, array_keys($recipients))
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->config['api_key'] . ':' . $this->config['secret_key']),
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/send', $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Beem Africa SMS sent successfully', [
                    'recipients' => $recipients,
                    'message_length' => strlen($message),
                    'response' => $data
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'data' => $data,
                    'provider' => 'beem_africa',
                    'request_id' => $data['request_id'] ?? null
                ];
            } else {
                Log::error('Beem Africa SMS failed', [
                    'recipients' => $recipients,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return [
                    'success' => false,
                    'message' => 'Beem Africa API error (HTTP ' . $response->status() . '): ' . $response->body(),
                    'error' => $response->body(),
                    'provider' => 'beem_africa'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Beem Africa SMS service error', [
                'recipients' => $recipients,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'SMS service error: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'provider' => 'beem_africa'
            ];
        }
    }

    public function getDeliveryStatus(string $requestId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->config['api_key'] . ':' . $this->config['secret_key']),
            ])->get($this->baseUrl . '/reports', [
                'request_id' => $requestId
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'provider' => 'beem_africa'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to get delivery status',
                    'error' => $response->body(),
                    'provider' => 'beem_africa'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error getting delivery status: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'provider' => 'beem_africa'
            ];
        }
    }

    public function getBalance(): array
    {
        try {
            // Beem Africa balance endpoint
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->config['api_key'] . ':' . $this->config['secret_key']),
                'Content-Type' => 'application/json',
            ])->get('https://apisms.beem.africa/public/v1/vendors/balance');

            if ($response->successful()) {
                $data = $response->json();
                // Beem returns: {"data": {"credit_balance": "xxx"}}
                $balance = $data['data']['credit_balance'] ?? $data['credit_balance'] ?? $data['balance'] ?? 0;
                return [
                    'success' => true,
                    'balance' => $balance,
                    'data' => $data,
                    'provider' => 'beem_africa'
                ];
            } else {
                Log::error('Beem Africa balance error', ['response' => $response->body()]);
                return [
                    'success' => false,
                    'message' => 'Failed to get balance',
                    'error' => $response->body(),
                    'provider' => 'beem_africa'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Beem Africa balance exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => 'Error getting balance: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'provider' => 'beem_africa'
            ];
        }
    }

    public function getSenderIds(): array
    {
        try {
            Log::info('Fetching sender IDs from Beem Africa');
            
            // Try the v1 endpoint first
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->config['api_key'] . ':' . $this->config['secret_key']),
                'Content-Type' => 'application/json',
            ])->timeout(30)->get('https://apisms.beem.africa/public/v1/vendors/senderids');

            Log::info('Beem Africa sender IDs response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Try multiple response formats
                $senderIds = [];
                if (isset($data['data']) && is_array($data['data'])) {
                    $senderIds = $data['data'];
                } elseif (isset($data['sender_ids']) && is_array($data['sender_ids'])) {
                    $senderIds = $data['sender_ids'];
                } elseif (isset($data['senderids']) && is_array($data['senderids'])) {
                    $senderIds = $data['senderids'];
                } elseif (is_array($data) && !isset($data['error'])) {
                    // Direct array response
                    $senderIds = $data;
                }
                
                Log::info('Parsed sender IDs', ['count' => count($senderIds), 'sender_ids' => $senderIds]);
                
                return [
                    'success' => true,
                    'sender_ids' => $senderIds,
                    'provider' => 'beem_africa',
                    'raw_response' => $data
                ];
            } else {
                Log::error('Beem Africa sender IDs API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'message' => 'Failed to get sender IDs from Beem Africa API (HTTP ' . $response->status() . ')',
                    'error' => $response->body(),
                    'provider' => 'beem_africa'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Beem Africa sender IDs exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Error getting sender IDs: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'provider' => 'beem_africa'
            ];
        }
    }

    public function formatPhoneNumber(string $phoneNumber): string
    {
        // Remove any non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If number starts with 0, replace with country code (assuming Tanzania +255)
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '255' . substr($phoneNumber, 1);
        }
        
        // If number doesn't start with country code, add it
        if (!str_starts_with($phoneNumber, '255')) {
            $phoneNumber = '255' . $phoneNumber;
        }
        
        return $phoneNumber;
    }

    public function isValidPhoneNumber(string $phoneNumber): bool
    {
        $formatted = $this->formatPhoneNumber($phoneNumber);
        
        // Tanzania mobile numbers should be 12 digits (255 + 9 digits)
        return strlen($formatted) === 12 && str_starts_with($formatted, '255');
    }

    public function calculateSmsCount(string $message): int
    {
        $length = strlen($message);
        
        if ($length <= 160) {
            return 1;
        } elseif ($length <= 306) {
            return 2;
        } elseif ($length <= 459) {
            return 3;
        } else {
            return ceil($length / 153);
        }
    }

    public function getProviderName(): string
    {
        return 'beem_africa';
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
        $this->baseUrl = $config['base_url'] ?? 'https://apisms.beem.africa/v1';
    }

    public function testConnection(): array
    {
        try {
            $response = $this->getBalance();
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'provider' => 'beem_africa'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Connection failed: ' . $response['message'],
                    'provider' => 'beem_africa'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'provider' => 'beem_africa'
            ];
        }
    }
}