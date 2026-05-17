<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\SmsTopup;
use App\Services\SelcomSmsTopupService;
use App\Services\SmsWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SmsTopupController extends Controller
{
    protected SelcomSmsTopupService $selcomService;
    protected SmsWalletService $walletService;

    public function __construct(SelcomSmsTopupService $selcomService, SmsWalletService $walletService)
    {
        $this->selcomService = $selcomService;
        $this->walletService = $walletService;
    }

    /**
     * Display SMS credit packages and wallet info.
     */
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;
        
        // Get wallet information
        $wallet = $this->walletService->getWallet($tenantId);
        $walletStats = $this->walletService->getWalletStats($tenantId);
        
        // Get credit packages
        $packages = $this->selcomService->getCreditPackages();
        
        // Get recent topups
        $recentTopups = SmsTopup::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('tenant.sms.topup.index', compact(
            'wallet',
            'walletStats',
            'packages',
            'recentTopups'
        ));
    }

    /**
     * Show topup history.
     */
    public function history(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $query = SmsTopup::where('tenant_id', $tenantId);
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        $topups = $query->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('tenant.sms.topup.history', compact('topups'));
    }

    /**
     * Create a new topup payment.
     */
    public function create(Request $request)
    {
        $request->validate([
            'package_id' => 'required|string',
            'phone_number' => 'nullable|string|regex:/^[0-9+\-\s]+$/',
            'email' => 'nullable|email'
        ]);

        try {
            $tenantId = Auth::user()->tenant_id;
            $packages = $this->selcomService->getCreditPackages();
            
            // Find selected package
            $selectedPackage = collect($packages)->firstWhere('id', $request->package_id);
            
            if (!$selectedPackage) {
                return back()->withErrors(['package_id' => 'Invalid package selected']);
            }

            // Create payment request
            $result = $this->selcomService->createTopupPayment(
                $tenantId,
                $selectedPackage['units'],
                $selectedPackage['amount'],
                $selectedPackage['currency'],
                $request->phone_number,
                $request->email
            );

            if ($result['success']) {
                // Redirect to payment gateway
                if (isset($result['data']['payment_url'])) {
                    return redirect($result['data']['payment_url']);
                } else {
                    return redirect()->route('tenant.sms.topup.show', $result['data']['topup_id'])
                        ->with('success', 'Payment request created successfully');
                }
            } else {
                return back()->withErrors(['payment' => $result['message']]);
            }

        } catch (\Exception $e) {
            Log::error("Failed to create SMS topup payment", [
                'tenant_id' => Auth::user()->tenant_id,
                'package_id' => $request->package_id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['payment' => 'Failed to create payment request']);
        }
    }

    /**
     * Show topup details.
     */
    public function show(SmsTopup $topup)
    {
        // Ensure user can only view their own topups
        if ($topup->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        return view('tenant.sms.topup.show', compact('topup'));
    }

    /**
     * Check payment status.
     */
    public function checkStatus(SmsTopup $topup)
    {
        // Ensure user can only check their own topups
        if ($topup->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        try {
            $result = $this->selcomService->checkPaymentStatus($topup);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'status' => $topup->fresh()->status,
                    'data' => $result['data']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status'
            ]);
        }
    }

    /**
     * Cancel pending payment.
     */
    public function cancel(SmsTopup $topup)
    {
        // Ensure user can only cancel their own topups
        if ($topup->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        try {
            $result = $this->selcomService->cancelPayment($topup);
            
            if ($result['success']) {
                return redirect()->route('tenant.sms.topup.index')
                    ->with('success', 'Payment cancelled successfully');
            } else {
                return back()->withErrors(['cancel' => $result['message']]);
            }

        } catch (\Exception $e) {
            return back()->withErrors(['cancel' => 'Failed to cancel payment']);
        }
    }

    /**
     * Payment success callback.
     */
    public function success(SmsTopup $topup)
    {
        // Ensure user can only view their own topups
        if ($topup->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        // Check latest status
        $this->selcomService->checkPaymentStatus($topup);
        $topup = $topup->fresh();

        return view('tenant.sms.topup.success', compact('topup'));
    }

    /**
     * Payment cancel callback.
     */
    public function cancelled(SmsTopup $topup)
    {
        // Ensure user can only view their own topups
        if ($topup->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        return view('tenant.sms.topup.cancelled', compact('topup'));
    }

    /**
     * Get wallet balance via AJAX.
     */
    public function getBalance()
    {
        $tenantId = Auth::user()->tenant_id;
        $balance = $this->walletService->getBalance($tenantId);
        
        return response()->json([
            'success' => true,
            'balance' => $balance
        ]);
    }

    /**
     * Get wallet transaction history via AJAX.
     */
    public function getTransactions(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $limit = $request->get('limit', 20);
        
        $transactions = $this->walletService->getTransactionHistory($tenantId, $limit);
        
        return response()->json([
            'success' => true,
            'transactions' => $transactions
        ]);
    }

    /**
     * Get wallet statistics via AJAX.
     */
    public function getStats()
    {
        $tenantId = Auth::user()->tenant_id;
        $stats = $this->walletService->getWalletStats($tenantId);
        
        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Update wallet settings.
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'low_balance_threshold' => 'required|integer|min:0|max:1000',
            'auto_topup_enabled' => 'boolean',
            'auto_topup_amount' => 'required_if:auto_topup_enabled,true|integer|min:100|max:10000',
            'auto_topup_threshold' => 'required_if:auto_topup_enabled,true|integer|min:0|max:500'
        ]);

        try {
            $tenantId = Auth::user()->tenant_id;
            $wallet = $this->walletService->getWallet($tenantId);
            
            $wallet->update([
                'low_balance_threshold' => $request->low_balance_threshold,
                'auto_topup_enabled' => $request->boolean('auto_topup_enabled'),
                'auto_topup_amount' => $request->auto_topup_amount ?? $wallet->auto_topup_amount,
                'auto_topup_threshold' => $request->auto_topup_threshold ?? $wallet->auto_topup_threshold
            ]);

            return back()->with('success', 'Wallet settings updated successfully');

        } catch (\Exception $e) {
            Log::error("Failed to update wallet settings", [
                'tenant_id' => Auth::user()->tenant_id,
                'error' => $e->getMessage()
            ]);

            return back()->withErrors(['settings' => 'Failed to update wallet settings']);
        }
    }

    /**
     * Download topup receipt.
     */
    public function downloadReceipt(SmsTopup $topup)
    {
        // Ensure user can only download their own receipts
        if ($topup->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        if ($topup->status !== 'paid') {
            return back()->withErrors(['receipt' => 'Receipt not available for unpaid topups']);
        }

        // Generate PDF receipt
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('tenant.sms.topup.receipt', compact('topup'));
        
        return $pdf->download("sms-topup-receipt-{$topup->internal_ref}.pdf");
    }
}