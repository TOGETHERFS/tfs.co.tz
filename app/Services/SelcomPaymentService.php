<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SelcomPaymentService
{
    private $apiKey;
    private $apiSecret;
    private $baseUrl;
    private $vendor;
    private $isEnabled;

    public function __construct()
    {
        $this->apiKey = config('services.selcom.api_key');
        $this->apiSecret = config('services.selcom.api_secret');
        $this->baseUrl = config('services.selcom.base_url', 'https://apigw.selcommobile.com');
        // Vendor is the TILL number or merchant ID provided by Selcom
        $this->vendor = config('services.selcom.till_number') ?? config('services.selcom.merchant_id');
        $this->isEnabled = config('services.selcom.enabled', false);
    }

    /**
     * Check if Selcom is properly configured
     * 
     * @return array ['configured' => bool, 'errors' => array]
     */
    public function checkConfiguration(): array
    {
        $errors = [];

        if (!$this->isEnabled) {
            $errors[] = 'Selcom payment gateway is not enabled';
        }

        if (empty($this->apiKey)) {
            $errors[] = 'SELCOM_API_KEY is not configured';
        }

        if (empty($this->apiSecret)) {
            $errors[] = 'SELCOM_API_SECRET is not configured';
        }

        if (empty($this->vendor)) {
            $errors[] = 'SELCOM_TILL_NUMBER or SELCOM_MERCHANT_ID is not configured';
        }

        return [
            'configured' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Check if Selcom is properly configured and enabled
     */
    public function isConfigured(): bool
    {
        return $this->isEnabled 
            && !empty($this->apiKey) 
            && !empty($this->apiSecret) 
            && !empty($this->vendor);
    }

    /**
     * Generate authorization header for Selcom API
     * Format: SELCOM {base64_encoded_api_key}
     */
    private function generateAuthHeader(): string
    {
        return 'SELCOM ' . base64_encode($this->apiKey);
    }

    /**
     * Generate timestamp in ISO 8601 format
     */
    private function generateTimestamp(): string
    {
        return Carbon::now()->format('Y-m-d\TH:i:sP');
    }

    /**
     * Generate digest for request authentication
     * Selcom requires HMAC-SHA256 signature of timestamp + sorted fields
     */
    private function generateDigest(array $data, string $timestamp): string
    {
        // Build signing string: start with timestamp, then add sorted field=value pairs
        $signParts = [];
        
        // Sort data by keys for consistent signing
        ksort($data);
        
        foreach ($data as $key => $value) {
            // Only include non-null, non-array values in signature
            if (!is_null($value) && !is_array($value) && $value !== '') {
                $signParts[] = "{$key}={$value}";
            }
        }
        
        $signingString = "timestamp={$timestamp}";
        if (!empty($signParts)) {
            $signingString .= "&" . implode("&", $signParts);
        }

        // Generate HMAC SHA256 signature
        $signature = hash_hmac('sha256', $signingString, $this->apiSecret, true);
        
        return base64_encode($signature);
    }

    /**
     * Get signed fields from data array (comma-separated list of keys)
     */
    private function getSignedFields(array $data): string
    {
        // Sort keys and filter out null/empty values
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
     * Make authenticated request to Selcom API
     */
    private function makeRequest(string $endpoint, array $data = [], string $method = 'POST')
    {
        $timestamp = $this->generateTimestamp();
        $digest = $this->generateDigest($data, $timestamp);
        $signedFields = $this->getSignedFields($data);

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => $this->generateAuthHeader(),
            'Timestamp' => $timestamp,
            'Digest-Method' => 'HS256',
            'Digest' => $digest,
            'Signed-Fields' => $signedFields,
        ];

        $url = $this->baseUrl . $endpoint;

        try {
            Log::info('Selcom API Request Starting', [
                'endpoint' => $endpoint,
                'method' => $method,
                'url' => $url,
                'data' => $data,
                'headers' => array_merge($headers, ['Authorization' => 'SELCOM ***HIDDEN***']),
            ]);

            if (strtoupper($method) === 'GET') {
                $response = Http::withHeaders($headers)
                    ->timeout(30)
                    ->get($url, $data);
            } else {
                $response = Http::withHeaders($headers)
                    ->timeout(30)
                    ->post($url, $data);
            }

            $responseData = $response->json();

            Log::info('Selcom API Response', [
                'endpoint' => $endpoint,
                'status_code' => $response->status(),
                'response' => $responseData
            ]);

            return $responseData;
        } catch (\Exception $e) {
            Log::error('Selcom API Error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            // Return error response instead of throwing to allow graceful handling
            return [
                'result' => 'FAIL',
                'resultcode' => 'ERROR',
                'message' => 'Connection error: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Initiate a payment with Selcom (legacy method for backward compatibility).
     *
     * @param array $payload
     * @return array
     */
    public function initiate(array $payload): array
    {
        return $this->createOrder($payload);
    }

    /**
     * Create a minimal order for checkout (Selcom Checkout API)
     * This is the recommended approach for simple payments
     */
    public function createOrder(array $paymentData)
    {
        $orderId = $paymentData['order_id'] ?? $paymentData['reference'] ?? 'ORD_' . time();
        
        $data = [
            'vendor' => $this->vendor,
            'order_id' => $orderId,
            'buyer_email' => $paymentData['email'] ?? '',
            'buyer_name' => $paymentData['buyer_name'] ?? 'Customer',
            'buyer_phone' => $this->formatPhoneNumber($paymentData['phone_number'] ?? '255700000000'),
            'amount' => (int) $paymentData['amount'],
            'currency' => 'TZS',
            'webhook' => base64_encode($paymentData['callback_url'] ?? route('webhooks.selcom')),
            'buyer_remarks' => $paymentData['description'] ?? 'Payment',
            'merchant_remarks' => $paymentData['description'] ?? 'Payment',
            'no_of_items' => 1,
        ];

        // Add redirect URLs if provided (must be base64 encoded)
        if (isset($paymentData['success_url'])) {
            $data['redirect_url'] = base64_encode($paymentData['success_url']);
        }
        
        if (isset($paymentData['cancel_url'])) {
            $data['cancel_url'] = base64_encode($paymentData['cancel_url']);
        }

        return $this->makeRequest('/v1/checkout/create-order-minimal', $data);
    }

    /**
     * Create a full order with billing/shipping details
     */
    public function createFullOrder(array $paymentData)
    {
        $orderId = $paymentData['order_id'] ?? $paymentData['reference'] ?? 'ORD_' . time();
        
        $data = [
            'vendor' => $this->vendor,
            'order_id' => $orderId,
            'buyer_email' => $paymentData['email'] ?? '',
            'buyer_name' => $paymentData['buyer_name'] ?? 'Customer',
            'buyer_phone' => $this->formatPhoneNumber($paymentData['phone_number'] ?? '255700000000'),
            'amount' => (int) $paymentData['amount'],
            'currency' => 'TZS',
            'redirect_url' => base64_encode($paymentData['success_url'] ?? url('/')),
            'cancel_url' => base64_encode($paymentData['cancel_url'] ?? url('/')),
            'webhook' => base64_encode($paymentData['callback_url'] ?? route('webhooks.selcom')),
            'buyer_remarks' => $paymentData['description'] ?? 'Payment',
            'merchant_remarks' => $paymentData['description'] ?? 'Payment',
            'no_of_items' => 1,
        ];

        return $this->makeRequest('/v1/checkout/create-order', $data);
    }

    /**
     * Create hosted payment page for redirect flow
     */
    public function createHostedPayment(array $paymentData)
    {
        $orderId = $paymentData['order_id'] ?? $paymentData['reference'] ?? 'ORD_' . time();
        
        $data = [
            'vendor' => $this->vendor,
            'order_id' => $orderId,
            'buyer_email' => $paymentData['email'] ?? '',
            'buyer_name' => $paymentData['buyer_name'] ?? 'Customer',
            'buyer_phone' => $this->formatPhoneNumber($paymentData['phone_number'] ?? '255700000000'),
            'amount' => (int) $paymentData['amount'],
            'currency' => 'TZS',
            'redirect_url' => base64_encode($paymentData['success_url'] ?? url('/')),
            'cancel_url' => base64_encode($paymentData['cancel_url'] ?? url('/')),
            'webhook' => base64_encode($paymentData['callback_url'] ?? route('webhooks.selcom')),
            'buyer_remarks' => $paymentData['description'] ?? 'Payment',
            'merchant_remarks' => $paymentData['description'] ?? 'Payment',
            'no_of_items' => 1,
        ];

        $response = $this->makeRequest('/v1/checkout/create-order-minimal', $data);
        
        Log::info('Selcom create-order-minimal response', ['response' => $response, 'order_id' => $orderId]);
        
        // Build the payment URL from the response
        if (isset($response['result']) && $response['result'] === 'SUCCESS' && isset($response['data'][0])) {
            $responseData = $response['data'][0];
            
            // Priority 1: Use payment_gateway_url from response (base64 encoded)
            if (!empty($responseData['payment_gateway_url'])) {
                $response['payment_url'] = base64_decode($responseData['payment_gateway_url']);
            }
            // Priority 2: Use gateway_buyer_uuid to construct URL
            elseif (!empty($responseData['gateway_buyer_uuid'])) {
                $response['payment_url'] = $this->baseUrl . '/v1/checkout/checkout-page/' . $responseData['gateway_buyer_uuid'];
            }
            
            Log::info('Selcom payment URL generated', [
                'payment_url' => $response['payment_url'] ?? 'NOT_GENERATED',
                'gateway_buyer_uuid' => $responseData['gateway_buyer_uuid'] ?? null,
                'payment_gateway_url_encoded' => $responseData['payment_gateway_url'] ?? null,
                'order_id' => $orderId,
            ]);
        }
        
        return $response;
    }

    /**
     * Process wallet pull payment (USSD Push to customer phone)
     * Customer will receive a prompt on their phone to authorize payment
     */
    public function processWalletPayment(array $paymentData)
    {
        $data = [
            'transid' => $paymentData['transaction_id'] ?? 'TXN_' . time(),
            'order_id' => $paymentData['order_id'],
            'msisdn' => $this->formatPhoneNumber($paymentData['phone_number']),
        ];

        return $this->makeRequest('/v1/checkout/wallet-payment', $data);
    }

    /**
     * Query order status
     */
    public function queryOrderStatus(string $orderId)
    {
        $data = [
            'order_id' => $orderId,
        ];

        return $this->makeRequest('/v1/checkout/order-status', $data, 'GET');
    }

    /**
     * Query transaction status (alias for queryOrderStatus)
     */
    public function queryTransactionStatus(string $orderId)
    {
        return $this->queryOrderStatus($orderId);
    }

    /**
     * Verify payment callback
     */
    public function verifyCallback(array $callbackData): bool
    {
        // Verify the callback signature
        $expectedSignature = $this->generateDigest($callbackData, $callbackData['timestamp'] ?? '');
        
        return hash_equals($expectedSignature, $callbackData['digest'] ?? '');
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(string $orderId)
    {
        $data = [
            'order_id' => $orderId,
        ];

        return $this->makeRequest('/v1/checkout/cancel-order', $data);
    }

    /**
     * List all orders
     */
    public function listOrders(int $fromDate = null, int $toDate = null)
    {
        $data = [];
        
        if ($fromDate) {
            $data['from_date'] = $fromDate;
        }
        if ($toDate) {
            $data['to_date'] = $toDate;
        }

        return $this->makeRequest('/v1/checkout/list-orders', $data, 'GET');
    }

    /**
     * Get float/TILL balance
     */
    public function getBalance()
    {
        $data = [
            'vendor' => $this->vendor,
        ];

        return $this->makeRequest('/v1/float/balance', $data, 'GET');
    }

    /**
     * Validate payment amount and currency
     */
    public function validatePayment(array $paymentData): array
    {
        $errors = [];

        if (!isset($paymentData['amount']) || $paymentData['amount'] <= 0) {
            $errors[] = 'Invalid payment amount';
        }

        if (!isset($paymentData['phone_number']) || !$this->isValidTanzanianPhone($paymentData['phone_number'])) {
            $errors[] = 'Invalid phone number format';
        }

        if (!isset($paymentData['reference']) || empty($paymentData['reference'])) {
            $errors[] = 'Payment reference is required';
        }

        return $errors;
    }

    /**
     * Validate Tanzanian phone number format
     */
    private function isValidTanzanianPhone(string $phone): bool
    {
        // Remove any spaces, dashes, or plus signs
        $phone = preg_replace('/[\s\-\+]/', '', $phone);
        
        // Check if it matches Tanzanian phone number patterns
        return preg_match('/^(255|0)?[67]\d{8}$/', $phone);
    }

    /**
     * Format phone number for Selcom API
     */
    public function formatPhoneNumber(string $phone): string
    {
        // Remove any spaces, dashes, or plus signs
        $phone = preg_replace('/[\s\-\+]/', '', $phone);
        
        // Add country code if not present
        if (substr($phone, 0, 3) !== '255') {
            if (substr($phone, 0, 1) === '0') {
                $phone = '255' . substr($phone, 1);
            } else {
                $phone = '255' . $phone;
            }
        }
        
        return $phone;
    }

    /**
     * Get payment methods supported by Selcom
     */
    public function getSupportedPaymentMethods(): array
    {
        return [
            'selcom_till' => 'Selcom TILL Payment',
            'selcom_wallet' => 'Selcom Wallet',
            'selcom_qr' => 'Selcom QR Code',
            'mobile_money' => 'Mobile Money (via Selcom)',
        ];
    }

    /**
     * Check if Selcom service is available
     */
    public function isServiceAvailable(): bool
    {
        try {
            // Simple connectivity check
            $response = Http::timeout(10)->get($this->baseUrl);
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Selcom service unavailable', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Process TILL payment (customer pays at TILL/Agent)
     * Creates order that customer can pay using TILL number
     */
    public function processTillPayment(array $paymentData): array
    {
        $orderId = $paymentData['reference'] ?? $paymentData['order_id'] ?? 'TILL_' . time();
        
        $data = [
            'vendor' => $this->vendor,
            'order_id' => $orderId,
            'buyer_email' => $paymentData['email'] ?? '',
            'buyer_name' => $paymentData['buyer_name'] ?? 'Customer',
            'buyer_phone' => $this->formatPhoneNumber($paymentData['phone_number'] ?? ''),
            'amount' => (int) $paymentData['amount'],
            'currency' => 'TZS',
            'webhook' => base64_encode($paymentData['callback_url'] ?? route('webhooks.selcom')),
            'buyer_remarks' => $paymentData['description'] ?? 'TILL Payment',
            'merchant_remarks' => $paymentData['description'] ?? 'TILL Payment',
            'no_of_items' => 1,
        ];

        $response = $this->makeRequest('/v1/checkout/create-order-minimal', $data);
        
        // Add order_id to response for tracking
        if (isset($response['result']) && $response['result'] === 'SUCCESS') {
            $response['order_id'] = $orderId;
            $response['till_number'] = $this->vendor;
            $response['payment_instructions'] = "Pay TZS " . number_format($paymentData['amount']) . " to TILL: {$this->vendor} with reference: {$orderId}";
        }
        
        return $response;
    }

    /**
     * Process general payment (creates order and initiates wallet payment)
     * This combines order creation with USSD push
     */
    public function processPayment(array $paymentData): array
    {
        $orderId = $paymentData['reference'] ?? $paymentData['order_id'] ?? 'PAY_' . time();
        
        // Step 1: Create order
        $orderData = [
            'vendor' => $this->vendor,
            'order_id' => $orderId,
            'buyer_email' => $paymentData['email'] ?? '',
            'buyer_name' => $paymentData['buyer_name'] ?? 'Customer',
            'buyer_phone' => $this->formatPhoneNumber($paymentData['phone_number'] ?? ''),
            'amount' => (int) $paymentData['amount'],
            'currency' => 'TZS',
            'webhook' => base64_encode($paymentData['callback_url'] ?? route('webhooks.selcom')),
            'buyer_remarks' => $paymentData['description'] ?? 'Payment',
            'merchant_remarks' => $paymentData['description'] ?? 'Payment',
            'no_of_items' => 1,
        ];

        $orderResponse = $this->makeRequest('/v1/checkout/create-order-minimal', $orderData);
        
        if (!isset($orderResponse['result']) || $orderResponse['result'] !== 'SUCCESS') {
            return $orderResponse;
        }

        // Step 2: Trigger USSD push payment
        $walletData = [
            'transid' => $paymentData['transaction_id'] ?? 'TXN_' . time(),
            'order_id' => $orderId,
            'msisdn' => $this->formatPhoneNumber($paymentData['phone_number']),
        ];

        $walletResponse = $this->makeRequest('/v1/checkout/wallet-payment', $walletData);
        
        // Merge responses
        return [
            'result' => $walletResponse['result'] ?? 'FAILED',
            'message' => $walletResponse['message'] ?? $orderResponse['message'] ?? 'Payment initiated',
            'order_id' => $orderId,
            'transid' => $walletData['transid'],
            'order_response' => $orderResponse,
            'wallet_response' => $walletResponse,
        ];
    }

    /**
     * Generate QR code for payment
     * Customer scans QR to pay via mobile money or bank app
     */
    public function generatePaymentQR(array $paymentData): array
    {
        $orderId = $paymentData['reference'] ?? $paymentData['order_id'] ?? 'QR_' . time();
        
        // Create order first
        $orderData = [
            'vendor' => $this->vendor,
            'order_id' => $orderId,
            'buyer_email' => $paymentData['email'] ?? '',
            'buyer_name' => $paymentData['buyer_name'] ?? 'Customer',
            'buyer_phone' => $this->formatPhoneNumber($paymentData['phone_number'] ?? '255700000000'),
            'amount' => (int) $paymentData['amount'],
            'currency' => 'TZS',
            'webhook' => base64_encode($paymentData['callback_url'] ?? route('webhooks.selcom')),
            'buyer_remarks' => $paymentData['description'] ?? 'QR Payment',
            'merchant_remarks' => $paymentData['description'] ?? 'QR Payment',
            'no_of_items' => 1,
        ];

        $orderResponse = $this->makeRequest('/v1/checkout/create-order-minimal', $orderData);
        
        if (!isset($orderResponse['result']) || $orderResponse['result'] !== 'SUCCESS') {
            return $orderResponse;
        }

        // Get gateway_buyer_uuid for QR generation
        $gatewayBuyerUuid = $orderResponse['data'][0]['gateway_buyer_uuid'] ?? null;
        
        if ($gatewayBuyerUuid) {
            // Build QR payment URL
            $qrPaymentUrl = $this->baseUrl . '/v1/checkout/qr-payment/' . $gatewayBuyerUuid;
            
            return [
                'result' => 'SUCCESS',
                'message' => 'QR code generated successfully',
                'order_id' => $orderId,
                'qr_url' => $qrPaymentUrl,
                'payment_url' => $this->baseUrl . '/v1/checkout/checkout-page/' . $gatewayBuyerUuid,
                'gateway_buyer_uuid' => $gatewayBuyerUuid,
                'amount' => $paymentData['amount'],
                'order_response' => $orderResponse,
            ];
        }

        return [
            'result' => 'FAILED',
            'message' => 'Failed to generate QR code - missing gateway UUID',
            'order_id' => $orderId,
            'order_response' => $orderResponse,
        ];
    }
}