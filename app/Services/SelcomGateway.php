<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\Invoice;

class SelcomGateway
{
    /**
     * Create a payment intent for Selcom.
     */
    public function createPaymentIntent(Tenant $tenant, Invoice $invoice): array
    {
        $reference = 'SEL-' . strtoupper(uniqid());
        return [
            'intent_id' => 'intent_' . substr($reference, -8),
            'reference' => $reference,
            'amount' => (int) $invoice->amount,
            'currency' => $invoice->currency,
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'status' => 'pending',
            'provider' => 'selcom',
        ];
    }

    /**
     * Charge mobile money via Selcom (stub).
     */
    public function chargeMobileMoney(string $msisdn, int $amount, string $reference): array
    {
        return [
            'status' => 'pending',
            'channel' => 'MNO',
            'msisdn' => $msisdn,
            'amount' => $amount,
            'reference' => $reference,
            'message' => 'Awaiting user confirmation',
        ];
    }

    /**
     * Handle webhook (verification would be here, but handled in controller).
     */
    public function handleWebhook(array $payload): array
    {
        return $payload;
    }
}