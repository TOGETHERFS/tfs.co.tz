<?php

namespace App\Services\SmsProviders;

use App\Contracts\SmsProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RouteAfricaProvider implements SmsProviderInterface
{
    protected array $config;
    protected string $baseUrl;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->baseUrl = $config['base_url'] ?? 'https://api.esmsafrica.io/api';
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
                'sender_id' => $senderId,
                'message' => $message,
                'recipients' => array_map(function ($recipient) {
                    return $this->formatPhoneNumber($recipient);
                }, $recipients)
            ];

            $response = Http::withHeaders([
                'X-Account-ID' => $this->config['account_id'],
                'X-API-Key' => $this->config['api_key'],
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/sms/send', $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Route Africa SMS sent successfully', [
                    'recipients' => $recipients,
                    'message_length' => strlen($message),
                    'response' => $data
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'data' => $data,
                    'provider' => 'route_africa',
                    'request_id' => $data['message_id'] ?? $data['id'] ?? null
                ];
            } else {
                Log::error('Route Africa SMS failed', [
                    'recipients' => $recipients,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return [
                    'success' => false,
                    'message' => 'Failed to send SMS',
                    'error' => $response->body(),
                    'provider' => 'route_africa'
                ];
            }
        } catch (\Exception $e) {
            Log::error('Route Africa SMS service error', [
                'recipients' => $recipients,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'SMS service error: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'provider' => 'route_africa'
            ];
        }
    }

    public function getDeliveryStatus(string $requestId): array
    {
        try {
            $response = Http::withHeaders([
                'X-Account-ID' => $this->config['account_id'],
                'X-API-Key' => $this->config['api_key'],
            ])->get($this->baseUrl . '/sms/status/' . $requestId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'provider' => 'route_africa'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to get delivery status',
                    'error' => $response->body(),
                    'provider' => 'route_africa'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error getting delivery status: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'provider' => 'route_africa'
            ];
        }
    }

    public function getBalance(): array
    {
        try {
            $response = Http::withHeaders([
                'X-Account-ID' => $this->config['account_id'],
                'X-API-Key' => $this->config['api_key'],
            ])->get($this->baseUrl . '/account/balance');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'provider' => 'route_africa'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to get balance',
                    'error' => $response->body(),
                    'provider' => 'route_africa'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error getting balance: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'provider' => 'route_africa'
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
        
        return '+' . $phoneNumber;
    }

    public function isValidPhoneNumber(string $phoneNumber): bool
    {
        $formatted = $this->formatPhoneNumber($phoneNumber);
        
        // Tanzania mobile numbers should be +255 followed by 9 digits
        return preg_match('/^\+255[67]\d{8}$/', $formatted);
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
        return 'route_africa';
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): void
    {
        $this->config = $config;
        $this->baseUrl = $config['base_url'] ?? 'https://api.esmsafrica.io/api';
    }

    public function testConnection(): array
    {
        try {
            $response = $this->getBalance();
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'provider' => 'route_africa'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Connection failed: ' . $response['message'],
                    'provider' => 'route_africa'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage(),
                'provider' => 'route_africa'
            ];
        }
    }
}