<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Repayment;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class WebhookController extends Controller
{
    /**
     * Handle payment gateway webhooks (e.g., Stripe, PayPal, Flutterwave).
     */
    public function paymentGateway(Request $request)
    {
        try {
            // Log the incoming webhook
            Log::info('Payment Gateway Webhook Received', [
                'headers' => $request->headers->all(),
                'payload' => $request->all()
            ]);

            // Verify webhook signature (implementation depends on payment provider)
            if (!$this->verifyWebhookSignature($request)) {
                Log::warning('Invalid webhook signature');
                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $payload = $request->all();
            $eventType = $payload['event_type'] ?? $payload['type'] ?? null;

            switch ($eventType) {
                case 'payment.success':
                case 'charge.success':
                    return $this->handleSuccessfulPayment($payload);
                
                case 'payment.failed':
                case 'charge.failed':
                    return $this->handleFailedPayment($payload);
                
                case 'payment.pending':
                    return $this->handlePendingPayment($payload);
                
                case 'refund.success':
                    return $this->handleRefund($payload);
                
                default:
                    Log::info('Unhandled webhook event type: ' . $eventType);
                    return response()->json(['message' => 'Event type not handled'], 200);
            }

        } catch (\Exception $e) {
            Log::error('Webhook processing error: ' . $e->getMessage(), [
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle mobile money webhooks (e.g., M-Pesa, MTN Mobile Money).
     */
    public function mobileMoney(Request $request)
    {
        try {
            Log::info('Mobile Money Webhook Received', [
                'headers' => $request->headers->all(),
                'payload' => $request->all()
            ]);

            $payload = $request->all();
            
            // Extract common mobile money fields
            $transactionId = $payload['transaction_id'] ?? $payload['TransID'] ?? null;
            $amount = $payload['amount'] ?? $payload['TransAmount'] ?? null;
            $phoneNumber = $payload['phone_number'] ?? $payload['MSISDN'] ?? null;
            $status = $payload['status'] ?? $payload['ResultCode'] ?? null;
            $reference = $payload['reference'] ?? $payload['BillRefNumber'] ?? null;

            if ($status === '0' || $status === 'success' || $status === 'SUCCESSFUL') {
                return $this->processMobileMoneyPayment([
                    'transaction_id' => $transactionId,
                    'amount' => $amount,
                    'phone_number' => $phoneNumber,
                    'reference' => $reference,
                    'raw_data' => $payload
                ]);
            } else {
                Log::warning('Mobile money payment failed', $payload);
                return response()->json(['message' => 'Payment failed'], 200);
            }

        } catch (\Exception $e) {
            Log::error('Mobile money webhook error: ' . $e->getMessage(), [
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle bank integration webhooks.
     */
    public function bankIntegration(Request $request)
    {
        try {
            Log::info('Bank Integration Webhook Received', [
                'headers' => $request->headers->all(),
                'payload' => $request->all()
            ]);

            $payload = $request->all();
            $eventType = $payload['event_type'] ?? null;

            switch ($eventType) {
                case 'transfer.completed':
                    return $this->handleBankTransfer($payload);
                
                case 'account.credited':
                    return $this->handleAccountCredit($payload);
                
                case 'account.debited':
                    return $this->handleAccountDebit($payload);
                
                default:
                    Log::info('Unhandled bank event type: ' . $eventType);
                    return response()->json(['message' => 'Event type not handled'], 200);
            }

        } catch (\Exception $e) {
            Log::error('Bank webhook error: ' . $e->getMessage(), [
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle SMS service webhooks.
     */
    public function smsService(Request $request)
    {
        try {
            Log::info('SMS Service Webhook Received', [
                'headers' => $request->headers->all(),
                'payload' => $request->all()
            ]);

            $payload = $request->all();
            $status = $payload['status'] ?? null;
            $messageId = $payload['message_id'] ?? null;
            $phoneNumber = $payload['phone_number'] ?? null;

            // Update SMS delivery status in database if needed
            // This would require an SMS log table to track message delivery

            return response()->json(['message' => 'SMS status updated'], 200);

        } catch (\Exception $e) {
            Log::error('SMS webhook error: ' . $e->getMessage(), [
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Handle credit bureau webhooks.
     */
    public function creditBureau(Request $request)
    {
        try {
            Log::info('Credit Bureau Webhook Received', [
                'headers' => $request->headers->all(),
                'payload' => $request->all()
            ]);

            $payload = $request->all();
            $clientId = $payload['client_id'] ?? null;
            $creditScore = $payload['credit_score'] ?? null;
            $reportData = $payload['report_data'] ?? null;

            if ($clientId && $creditScore) {
                // Update client credit information
                $client = Client::find($clientId);
                if ($client) {
                    $client->update([
                        'credit_score' => $creditScore,
                        'credit_report_data' => json_encode($reportData),
                        'credit_report_updated_at' => now()
                    ]);

                    Log::info('Credit score updated for client: ' . $clientId);
                }
            }

            return response()->json(['message' => 'Credit report processed'], 200);

        } catch (\Exception $e) {
            Log::error('Credit bureau webhook error: ' . $e->getMessage(), [
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Verify webhook signature.
     */
    private function verifyWebhookSignature(Request $request)
    {
        // Implementation depends on the payment provider
        // Example for a generic HMAC verification:
        
        $signature = $request->header('X-Webhook-Signature');
        $payload = $request->getContent();
        $secret = config('services.webhook.secret');
        
        if (!$signature || !$secret) {
            return false;
        }
        
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle successful payment.
     */
    private function handleSuccessfulPayment($payload)
    {
        DB::beginTransaction();

        try {
            $amount = $payload['amount'] ?? 0;
            $reference = $payload['reference'] ?? $payload['metadata']['reference'] ?? null;
            $transactionId = $payload['transaction_id'] ?? $payload['id'] ?? null;

            // Find the loan by reference
            $loan = $this->findLoanByReference($reference);
            
            if (!$loan) {
                Log::warning('Loan not found for reference: ' . $reference);
                return response()->json(['message' => 'Loan not found'], 404);
            }

            // Create repayment record
            $repayment = Repayment::create([
                'loan_id' => $loan->id,
                'amount' => $amount / 100, // Convert from cents if needed
                'payment_method' => 'online',
                'reference_number' => $transactionId,
                'payment_date' => now(),
                'notes' => 'Payment via webhook',
                'webhook_data' => json_encode($payload),
            ]);

            // Update loan balance
            $loan->outstanding_balance -= $repayment->amount;
            
            if ($loan->outstanding_balance <= 0) {
                $loan->status = 'completed';
                $loan->completed_at = now();
            } else {
                $loan->status = 'active';
            }
            
            $loan->save();

            // Update loan schedule
            $this->updateLoanSchedule($loan, $repayment->amount);

            DB::commit();

            Log::info('Payment processed successfully', [
                'loan_id' => $loan->id,
                'amount' => $repayment->amount,
                'transaction_id' => $transactionId
            ]);

            return response()->json(['message' => 'Payment processed successfully'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Handle failed payment.
     */
    private function handleFailedPayment($payload)
    {
        Log::warning('Payment failed', $payload);
        
        // You might want to notify the client or update payment attempt records
        
        return response()->json(['message' => 'Payment failure recorded'], 200);
    }

    /**
     * Handle pending payment.
     */
    private function handlePendingPayment($payload)
    {
        Log::info('Payment pending', $payload);
        
        // You might want to create a pending payment record
        
        return response()->json(['message' => 'Payment pending status recorded'], 200);
    }

    /**
     * Handle refund.
     */
    private function handleRefund($payload)
    {
        // Implementation for handling refunds
        Log::info('Refund processed', $payload);
        
        return response()->json(['message' => 'Refund processed'], 200);
    }

    /**
     * Process mobile money payment.
     */
    private function processMobileMoneyPayment($data)
    {
        DB::beginTransaction();

        try {
            // Find loan by reference or phone number
            $loan = $this->findLoanByReference($data['reference']);
            
            if (!$loan) {
                // Try to find by phone number
                $client = Client::where('phone', $data['phone_number'])->first();
                if ($client) {
                    $loan = $client->loans()->whereIn('status', ['disbursed', 'active'])->first();
                }
            }

            if (!$loan) {
                Log::warning('No active loan found for mobile money payment', $data);
                return response()->json(['message' => 'No active loan found'], 404);
            }

            // Create repayment
            $repayment = Repayment::create([
                'loan_id' => $loan->id,
                'amount' => $data['amount'],
                'payment_method' => 'mobile_money',
                'reference_number' => $data['transaction_id'],
                'payment_date' => now(),
                'notes' => 'Mobile money payment from ' . $data['phone_number'],
                'webhook_data' => json_encode($data['raw_data']),
            ]);

            // Update loan
            $loan->outstanding_balance -= $repayment->amount;
            
            if ($loan->outstanding_balance <= 0) {
                $loan->status = 'completed';
                $loan->completed_at = now();
            } else {
                $loan->status = 'active';
            }
            
            $loan->save();

            DB::commit();

            Log::info('Mobile money payment processed', [
                'loan_id' => $loan->id,
                'amount' => $repayment->amount,
                'phone' => $data['phone_number']
            ]);

            return response()->json(['message' => 'Mobile money payment processed'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Handle bank transfer.
     */
    private function handleBankTransfer($payload)
    {
        // Implementation for bank transfer processing
        Log::info('Bank transfer processed', $payload);
        
        return response()->json(['message' => 'Bank transfer processed'], 200);
    }

    /**
     * Handle account credit.
     */
    private function handleAccountCredit($payload)
    {
        // Implementation for account credit processing
        Log::info('Account credited', $payload);
        
        return response()->json(['message' => 'Account credit processed'], 200);
    }

    /**
     * Handle account debit.
     */
    private function handleAccountDebit($payload)
    {
        // Implementation for account debit processing
        Log::info('Account debited', $payload);
        
        return response()->json(['message' => 'Account debit processed'], 200);
    }

    /**
     * Find loan by reference.
     */
    private function findLoanByReference($reference)
    {
        if (!$reference) {
            return null;
        }

        return Loan::where('loan_number', $reference)
                   ->orWhere('id', $reference)
                   ->whereIn('status', ['disbursed', 'active'])
                   ->first();
    }

    /**
     * Update loan schedule based on payment.
     */
    private function updateLoanSchedule($loan, $amount)
    {
        $remainingAmount = $amount;
        
        $pendingSchedules = $loan->schedules()
                                 ->where('status', 'pending')
                                 ->orderBy('due_date')
                                 ->get();

        foreach ($pendingSchedules as $schedule) {
            if ($remainingAmount <= 0) {
                break;
            }

            $scheduleBalance = $schedule->total_amount - $schedule->paid_amount;
            
            if ($remainingAmount >= $scheduleBalance) {
                // Fully pay this schedule
                $schedule->paid_amount = $schedule->total_amount;
                $schedule->status = 'paid';
                $schedule->paid_date = now();
                $remainingAmount -= $scheduleBalance;
            } else {
                // Partially pay this schedule
                $schedule->paid_amount += $remainingAmount;
                $schedule->status = 'partial';
                $remainingAmount = 0;
            }
            
            $schedule->save();
        }
    }
}