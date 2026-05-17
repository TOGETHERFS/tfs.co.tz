<?php

namespace App\Services;

use App\Models\SmsTopup;
use App\Models\Tenant;
use App\Services\SmsWalletService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class SelcomSmsTopupService
{
    protected SmsWalletService $walletService;
    protected ?string $baseUrl;
    protected ?string $apiKey;
    protected ?string $apiSecret;
    protected ?string $vendor;
    protected bool $isLive;

    public function __construct(SmsWalletService $walletService)
    {
        $this->walletService = $walletService;
        $this->baseUrl   = Config::get('services.selcom.base_url', 'https://apigw.selcommobile.com');
        $this->apiKey    = Config::get('services.selcom.api_key');
        $this->apiSecret = Config::get('services.selcom.api_secret');
        $this->vendor    = Config::get('services.selcom.till_number') ?? Config::get('services.selcom.merchant_id');
        $this->isLive    = Config::get('services.selcom.environment', 'sandbox') === 'production';
    }

    /**
     * Create SMS topup payment request.
     */
    public function createTopupPayment(
        int $tenantId,
        int $smsUnits,
        float $amount,
        string $currency = 'TZS',
        ?string $phoneNumber = null,
        ?string $email = null
    ): array {
        try {
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                return [
                    'success' => false,
                    'message' => 'Tenant not found'
                ];
            }

            // Create SMS topup record
            $topup = SmsTopup::create([
                'tenant_id' => $tenantId,
                'amount' => $amount,
                'units' => $smsUnits,
                'status' => 'pending',
                'internal_ref' => SmsTopup::generateInternalRef(),
                'currency' => $currency,
                'notes' => "SMS credits purchase - {$smsUnits} units"
            ]);

            // Prepare Selcom payment request
            $phone = $phoneNumber ?? $tenant->phone ?? '255700000000';
            // Normalise phone to 255XXXXXXXXX
            $phone = preg_replace('/[\s\-\+]/', '', $phone);
            if (substr($phone, 0, 3) !== '255') {
                $phone = substr($phone, 0, 1) === '0' ? '255' . substr($phone, 1) : '255' . $phone;
            }

            $paymentData = [
                'order_id'         => $topup->internal_ref,
                'buyer_email'      => $email ?? $tenant->email ?? 'noreply@phidlms.co.tz',
                'buyer_name'       => $tenant->name,
                'buyer_phone'      => $phone,
                'amount'           => (int) $amount,
                'currency'         => $currency,
                'redirect_url'     => base64_encode(route('tenant.sms.topup.success', ['topup' => $topup->id])),
                'cancel_url'       => base64_encode(route('tenant.sms.topup.cancelled', ['topup' => $topup->id])),
                'webhook'          => base64_encode(route('webhooks.selcom.sms-topup')),
                'buyer_remarks'    => "SMS Credits Purchase - {$smsUnits} units for {$tenant->name}",
                'merchant_remarks' => "SMS topup payment for tenant {$tenantId}",
                'no_of_items'      => 1,
            ];

            // Create payment request with Selcom
            $response = $this->makeSelcomRequest('create-order', $paymentData);

            if ($response['success']) {
                $selcomData = $response['data'];
                
                // Update topup with Selcom reference
                $topup->update([
                    'selcom_ref' => $selcomData['reference'] ?? null,
                    'selcom_payload' => $selcomData
                ]);

                return [
                    'success' => true,
                    'message' => 'Payment request created successfully',
                    'data' => [
                        'topup_id' => $topup->id,
                        'internal_ref' => $topup->internal_ref,
                        'selcom_ref' => $selcomData['reference'] ?? null,
                        'payment_url' => $selcomData['payment_url'] ?? null,
                        'amount' => $amount,
                        'currency' => $currency,
                        'units' => $smsUnits
                    ]
                ];
            } else {
                $topup->markAsFailed('Selcom payment request failed: ' . $response['message']);
                
                return [
                    'success' => false,
                    'message' => 'Failed to create payment request: ' . $response['message']
                ];
            }

        } catch (\Exception $e) {
            Log::error("Failed to create SMS topup payment", [
                'tenant_id' => $tenantId,
                'amount' => $amount,
                'units' => $smsUnits,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create payment request: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Handle Selcom webhook for SMS topup payments.
     */
    public function handleWebhook(array $webhookData): array
    {
        try {
            // Validate webhook signature
            if (!$this->validateWebhookSignature($webhookData)) {
                Log::warning("Invalid Selcom webhook signature for SMS topup", $webhookData);
                return [
                    'success' => false,
                    'message' => 'Invalid webhook signature'
                ];
            }

            $orderId = $webhookData['order_id'] ?? null;
            $status = $webhookData['payment_status'] ?? null;
            $selcomRef = $webhookData['reference'] ?? null;

            if (!$orderId) {
                Log::warning("Missing order_id in Selcom webhook", $webhookData);
                return [
                    'success' => false,
                    'message' => 'Missing order_id'
                ];
            }

            // Find the topup record
            $topup = SmsTopup::where('internal_ref', $orderId)->first();
            if (!$topup) {
                Log::warning("SMS topup not found for order_id", [
                    'order_id' => $orderId,
                    'webhook_data' => $webhookData
                ]);
                return [
                    'success' => false,
                    'message' => 'Topup record not found'
                ];
            }

            // Update topup with webhook data
            $topup->update([
                'selcom_ref' => $selcomRef,
                'selcom_payload' => array_merge($topup->selcom_payload ?? [], $webhookData)
            ]);

            // Process based on payment status
            switch (strtolower($status)) {
                case 'completed':
                case 'success':
                    return $this->processSuccessfulPayment($topup, $webhookData);
                
                case 'failed':
                case 'cancelled':
                    return $this->processFailedPayment($topup, $webhookData);
                
                case 'pending':
                    Log::info("SMS topup payment pending", [
                        'topup_id' => $topup->id,
                        'order_id' => $orderId
                    ]);
                    return [
                        'success' => true,
                        'message' => 'Payment pending'
                    ];
                
                default:
                    Log::warning("Unknown payment status in SMS topup webhook", [
                        'status' => $status,
                        'topup_id' => $topup->id,
                        'webhook_data' => $webhookData
                    ]);
                    return [
                        'success' => false,
                        'message' => 'Unknown payment status'
                    ];
            }

        } catch (\Exception $e) {
            Log::error("Failed to process SMS topup webhook", [
                'webhook_data' => $webhookData,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Webhook processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process successful payment.
     */
    protected function processSuccessfulPayment(SmsTopup $topup, array $webhookData): array
    {
        try {
            if ($topup->status === 'paid') {
                Log::info("SMS topup already processed", ['topup_id' => $topup->id]);
                return [
                    'success' => true,
                    'message' => 'Payment already processed'
                ];
            }

            // Mark topup as paid
            $topup->markAsPaid();

            // Add credits to wallet
            $success = $this->walletService->processTopup($topup);

            if ($success) {
                Log::info("SMS topup processed successfully", [
                    'topup_id' => $topup->id,
                    'tenant_id' => $topup->tenant_id,
                    'units' => $topup->units,
                    'amount' => $topup->amount
                ]);

                // TODO: Send notification to tenant about successful topup
                
                return [
                    'success' => true,
                    'message' => 'Payment processed successfully'
                ];
            } else {
                Log::error("Failed to add credits after successful payment", [
                    'topup_id' => $topup->id,
                    'tenant_id' => $topup->tenant_id
                ]);

                return [
                    'success' => false,
                    'message' => 'Failed to add credits'
                ];
            }

        } catch (\Exception $e) {
            Log::error("Failed to process successful SMS topup payment", [
                'topup_id' => $topup->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process payment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process failed payment.
     */
    protected function processFailedPayment(SmsTopup $topup, array $webhookData): array
    {
        try {
            $reason = $webhookData['failure_reason'] ?? 'Payment failed';
            $topup->markAsFailed($reason);

            Log::info("SMS topup payment failed", [
                'topup_id' => $topup->id,
                'tenant_id' => $topup->tenant_id,
                'reason' => $reason
            ]);

            // TODO: Send notification to tenant about failed payment

            return [
                'success' => true,
                'message' => 'Failed payment processed'
            ];

        } catch (\Exception $e) {
            Log::error("Failed to process failed SMS topup payment", [
                'topup_id' => $topup->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to process failed payment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check payment status with Selcom.
     */
    public function checkPaymentStatus(SmsTopup $topup): array
    {
        try {
            if (!$topup->selcom_ref) {
                return [
                    'success' => false,
                    'message' => 'No Selcom reference found'
                ];
            }

            $response = $this->makeSelcomRequest('order-status', [
                'order_id' => $topup->internal_ref
            ]);

            if ($response['success']) {
                $statusData = $response['data'];
                
                // Update topup with latest status
                $topup->update([
                    'selcom_payload' => array_merge($topup->selcom_payload ?? [], $statusData)
                ]);

                return [
                    'success' => true,
                    'data' => $statusData
                ];
            } else {
                return [
                    'success' => false,
                    'message' => $response['message']
                ];
            }

        } catch (\Exception $e) {
            Log::error("Failed to check SMS topup payment status", [
                'topup_id' => $topup->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to check payment status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Cancel pending payment.
     */
    public function cancelPayment(SmsTopup $topup): array
    {
        try {
            if ($topup->status !== 'pending') {
                return [
                    'success' => false,
                    'message' => 'Payment cannot be cancelled'
                ];
            }

            // Cancel with Selcom if reference exists
            if ($topup->selcom_ref) {
                $response = $this->makeSelcomRequest('cancel-order', [
                    'order_id' => $topup->internal_ref
                ]);

                if (!$response['success']) {
                    Log::warning("Failed to cancel payment with Selcom", [
                        'topup_id' => $topup->id,
                        'error' => $response['message']
                    ]);
                }
            }

            // Mark as cancelled locally
            $topup->markAsCancelled();

            return [
                'success' => true,
                'message' => 'Payment cancelled successfully'
            ];

        } catch (\Exception $e) {
            Log::error("Failed to cancel SMS topup payment", [
                'topup_id' => $topup->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to cancel payment: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get SMS credit packages.
     */
    public function getCreditPackages(): array
    {
        return [
            [
                'id' => 'basic',
                'name' => 'Basic Package',
                'units' => 100,
                'amount' => 2000, // TZS
                'currency' => 'TZS',
                'cost_per_sms' => 20,
                'description' => '100 SMS credits'
            ],
            [
                'id' => 'standard',
                'name' => 'Standard Package',
                'units' => 500,
                'amount' => 9500, // TZS (5% discount)
                'currency' => 'TZS',
                'cost_per_sms' => 19,
                'description' => '500 SMS credits (5% discount)'
            ],
            [
                'id' => 'premium',
                'name' => 'Premium Package',
                'units' => 1000,
                'amount' => 18000, // TZS (10% discount)
                'currency' => 'TZS',
                'cost_per_sms' => 18,
                'description' => '1,000 SMS credits (10% discount)'
            ],
            [
                'id' => 'enterprise',
                'name' => 'Enterprise Package',
                'units' => 5000,
                'amount' => 85000, // TZS (15% discount)
                'currency' => 'TZS',
                'cost_per_sms' => 17,
                'description' => '5,000 SMS credits (15% discount)'
            ]
        ];
    }

    /**
     * Make request to Selcom API using correct auth headers.
     */
    protected function makeSelcomRequest(string $endpoint, array $data): array
    {
        try {
            // Map short endpoint names to full paths
            $endpointMap = [
                'create-order'  => '/v1/checkout/create-order-minimal',
                'order-status'  => '/v1/checkout/order-status',
                'cancel-order'  => '/v1/checkout/cancel-order',
            ];
            $path = $endpointMap[$endpoint] ?? ('/' . ltrim($endpoint, '/'));
            $url  = rtrim($this->baseUrl, '/') . $path;

            // Selcom requires vendor in payload
            $data['vendor'] = $this->vendor ?? $this->apiKey;

            // Build auth headers (same method as SelcomPaymentService)
            $timestamp    = \Carbon\Carbon::now()->format('Y-m-d\TH:i:sP');
            $digest       = $this->generateDigest($data, $timestamp);
            $signedFields = $this->getSignedFields($data);

            $headers = [
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
                'Authorization' => 'SELCOM ' . base64_encode($this->apiKey),
                'Timestamp'     => $timestamp,
                'Digest-Method' => 'HS256',
                'Digest'        => $digest,
                'Signed-Fields' => $signedFields,
            ];

            Log::info('SelcomSmsTopup API Request', ['url' => $url, 'data' => $data]);

            $response     = Http::withHeaders($headers)->timeout(30)->post($url, $data);
            $responseData = $response->json();

            Log::info('SelcomSmsTopup API Response', ['status' => $response->status(), 'response' => $responseData]);

            if (isset($responseData['result']) && $responseData['result'] === 'SUCCESS') {
                // Extract payment URL from response data
                $paymentUrl = null;
                if (!empty($responseData['data'][0]['payment_gateway_url'])) {
                    $paymentUrl = base64_decode($responseData['data'][0]['payment_gateway_url']);
                } elseif (!empty($responseData['data'][0]['gateway_buyer_uuid'])) {
                    $paymentUrl = rtrim($this->baseUrl, '/') . '/v1/checkout/checkout-page/' . $responseData['data'][0]['gateway_buyer_uuid'];
                }

                return [
                    'success'     => true,
                    'data'        => array_merge($responseData['data'][0] ?? [], [
                        'payment_url' => $paymentUrl,
                    ]),
                ];
            } else {
                $message = $responseData['message'] ?? ('API request failed: ' . $response->status());
                Log::error('SelcomSmsTopup API failed', [
                    'endpoint' => $endpoint,
                    'status'   => $response->status(),
                    'response' => $responseData,
                ]);
                return ['success' => false, 'message' => $message];
            }

        } catch (\Exception $e) {
            Log::error('SelcomSmsTopup API exception', ['endpoint' => $endpoint, 'error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'API request failed: ' . $e->getMessage()];
        }
    }

    /**
     * Generate HMAC-SHA256 digest (same as SelcomPaymentService).
     */
    protected function generateDigest(array $data, string $timestamp): string
    {
        $signParts = [];
        ksort($data);
        foreach ($data as $key => $value) {
            if (!is_null($value) && !is_array($value) && $value !== '') {
                $signParts[] = "{$key}={$value}";
            }
        }
        $signingString = "timestamp={$timestamp}";
        if (!empty($signParts)) {
            $signingString .= '&' . implode('&', $signParts);
        }
        return base64_encode(hash_hmac('sha256', $signingString, $this->apiSecret, true));
    }

    /**
     * Get comma-separated list of signed field keys.
     */
    protected function getSignedFields(array $data): string
    {
        $keys = [];
        ksort($data);
        foreach ($data as $key => $value) {
            if (!is_null($value) && !is_array($value) && $value !== '') {
                $keys[] = $key;
            }
        }
        return implode(',', $keys);
    }

    /**
     * Generate Selcom API signature (legacy, kept for webhook validation).
     */
    protected function generateSignature(array $data): string
    {
        ksort($data);
        $queryString = http_build_query($data);
        return hash_hmac('sha256', $queryString, $this->apiSecret);
    }

    /**
     * Validate webhook signature.
     */
    protected function validateWebhookSignature(array $webhookData): bool
    {
        $receivedSignature = $webhookData['signature'] ?? '';
        unset($webhookData['signature']);
        
        $expectedSignature = $this->generateSignature($webhookData);
        
        return hash_equals($expectedSignature, $receivedSignature);
    }
}