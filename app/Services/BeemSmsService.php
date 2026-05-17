<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BeemSmsService
{
    protected $apiKey;
    protected $secretKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.beem.api_key');
        $this->secretKey = config('services.beem.secret_key');
        $this->baseUrl = config('services.beem.base_url', 'https://apisms.beem.africa/v1');
    }

    /**
     * Send SMS to a single recipient.
     */
    public function sendSms(string $recipient, string $message, string $senderId = null): array
    {
        return $this->sendBulkSms([$recipient], $message, $senderId);
    }

    /**
     * Send SMS to multiple recipients.
     */
    public function sendBulkSms(array $recipients, string $message, string $senderId = null): array
    {
        try {
            $senderId = $senderId ?: config('services.beem.sender_id', 'PHIDLMS');
            
            $payload = [
                'source_addr' => $senderId,
                'encoding' => 0,
                'schedule_time' => '',
                'message' => $message,
                'recipients' => array_map(function ($recipient) {
                    return [
                        'recipient_id' => uniqid(),
                        'dest_addr' => $this->formatPhoneNumber($recipient)
                    ];
                }, $recipients)
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/send', $payload);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('SMS sent successfully', [
                    'recipients' => $recipients,
                    'message_length' => strlen($message),
                    'response' => $data
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'data' => $data
                ];
            } else {
                Log::error('Failed to send SMS', [
                    'recipients' => $recipients,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return [
                    'success' => false,
                    'message' => 'Failed to send SMS',
                    'error' => $response->body()
                ];
            }
        } catch (\Exception $e) {
            Log::error('SMS service error', [
                'recipients' => $recipients,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'SMS service error: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get SMS delivery status.
     */
    public function getDeliveryStatus(string $requestId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
            ])->get($this->baseUrl . '/reports', [
                'request_id' => $requestId
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to get delivery status',
                    'error' => $response->body()
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error getting delivery status: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get account balance.
     */
    public function getBalance(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->apiKey . ':' . $this->secretKey),
            ])->get($this->baseUrl . '/balance');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to get balance',
                    'error' => $response->body()
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error getting balance: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Format phone number for Beem SMS.
     */
    private function formatPhoneNumber(string $phoneNumber): string
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

    /**
     * Validate phone number format.
     */
    public function isValidPhoneNumber(string $phoneNumber): bool
    {
        $formatted = $this->formatPhoneNumber($phoneNumber);
        
        // Tanzania mobile numbers should be 12 digits (255 + 9 digits)
        return strlen($formatted) === 12 && str_starts_with($formatted, '255');
    }

    /**
     * Calculate SMS cost based on message length.
     */
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

    /**
     * Send OTP SMS.
     */
    public function sendOtp(string $recipient, string $otp, int $expiryMinutes = 5): array
    {
        $message = "Your OTP code is: {$otp}. This code will expire in {$expiryMinutes} minutes. Do not share this code with anyone.";
        
        return $this->sendSms($recipient, $message);
    }

    /**
     * Send loan reminder SMS.
     */
    public function sendLoanReminder(string $recipient, string $clientName, float $amount, string $dueDate): array
    {
        $message = "Dear {$clientName}, this is a reminder that your loan payment of TZS " . number_format($amount, 2) . " is due on {$dueDate}. Please make your payment on time to avoid penalties.";
        
        return $this->sendSms($recipient, $message);
    }

    /**
     * Send payment confirmation SMS.
     */
    public function sendPaymentConfirmation(string $recipient, string $clientName, float $amount, string $receiptNumber): array
    {
        $message = "Dear {$clientName}, we have received your payment of TZS " . number_format($amount, 2) . ". Receipt number: {$receiptNumber}. Thank you for your payment.";
        
        return $this->sendSms($recipient, $message);
    }

    /**
     * Send loan approval SMS.
     */
    public function sendLoanApproval(string $recipient, string $clientName, float $amount, string $loanNumber): array
    {
        $message = "Dear {$clientName}, congratulations! Your loan application for TZS " . number_format($amount, 2) . " has been approved. Loan number: {$loanNumber}. Please visit our office for disbursement.";
        
        return $this->sendSms($recipient, $message);
    }

    /**
     * Send loan rejection SMS.
     */
    public function sendLoanRejection(string $recipient, string $clientName, string $reason = null): array
    {
        $message = "Dear {$clientName}, we regret to inform you that your loan application has been declined.";
        
        if ($reason) {
            $message .= " Reason: {$reason}.";
        }
        
        $message .= " Please contact us for more information.";
        
        return $this->sendSms($recipient, $message);
    }
}