<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\SelcomSmsTopupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SelcomSmsWebhookController extends Controller
{
    protected SelcomSmsTopupService $selcomService;

    public function __construct(SelcomSmsTopupService $selcomService)
    {
        $this->selcomService = $selcomService;
    }

    /**
     * Handle Selcom webhook for SMS topup payments.
     */
    public function handleTopupWebhook(Request $request)
    {
        try {
            // Log incoming webhook
            Log::info("Selcom SMS topup webhook received", [
                'headers' => $request->headers->all(),
                'payload' => $request->all()
            ]);

            // Get webhook data
            $webhookData = $request->all();

            // Validate required fields
            if (!isset($webhookData['order_id']) || !isset($webhookData['payment_status'])) {
                Log::warning("Invalid Selcom SMS webhook payload", $webhookData);
                return response()->json(['error' => 'Invalid payload'], 400);
            }

            // Process webhook
            $result = $this->selcomService->handleWebhook($webhookData);

            if ($result['success']) {
                Log::info("Selcom SMS topup webhook processed successfully", [
                    'order_id' => $webhookData['order_id'],
                    'status' => $webhookData['payment_status']
                ]);

                return response()->json(['status' => 'success'], 200);
            } else {
                Log::error("Failed to process Selcom SMS topup webhook", [
                    'order_id' => $webhookData['order_id'],
                    'error' => $result['message']
                ]);

                return response()->json(['error' => $result['message']], 400);
            }

        } catch (\Exception $e) {
            Log::error("Exception in Selcom SMS topup webhook", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle test webhook (for development/testing).
     */
    public function handleTestWebhook(Request $request)
    {
        Log::info("Selcom SMS test webhook received", [
            'payload' => $request->all()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Test webhook received',
            'timestamp' => now()->toISOString()
        ]);
    }
}