<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsProvider;
use App\Services\SmsManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmsProviderController extends Controller
{
    protected SmsManager $smsManager;

    public function __construct(SmsManager $smsManager)
    {
        $this->smsManager = $smsManager;
    }

    /**
     * Display a listing of SMS providers.
     */
    public function index()
    {
        $providers = SmsProvider::orderBy('priority')->get();
        $liveSenderIds = [];
        
        // Get provider balances and sender IDs
        foreach ($providers as $provider) {
            if ($provider->is_active && $provider->isConfigured()) {
                try {
                    $balanceResult = $this->smsManager->getProviderBalance($provider->name);
                    if ($balanceResult['success']) {
                        $provider->live_balance = $balanceResult['balance'] ?? 0;
                    } else {
                        $provider->live_balance = 0;
                        Log::warning('Provider balance fetch failed', [
                            'provider' => $provider->name, 
                            'message' => $balanceResult['message'] ?? 'Unknown error'
                        ]);
                    }
                    
                    // Fetch live sender IDs from Beem Africa
                    if ($provider->name === 'beem_africa') {
                        $senderIdsResult = $this->smsManager->getProviderSenderIds($provider->name);
                        if ($senderIdsResult['success']) {
                            $liveSenderIds = $senderIdsResult['sender_ids'] ?? [];
                        } else {
                            Log::warning('Sender IDs fetch failed', [
                                'provider' => $provider->name,
                                'message' => $senderIdsResult['message'] ?? 'Unknown error'
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    $provider->live_balance = 0;
                    Log::error('Provider fetch error', ['provider' => $provider->name, 'error' => $e->getMessage()]);
                }
            } else {
                $provider->live_balance = 'N/A';
            }
        }

        return view('admin.sms-providers.index', compact('providers', 'liveSenderIds'));
    }

    /**
     * Show the form for creating a new SMS provider.
     */
    public function create()
    {
        return view('admin.sms-providers.create');
    }

    /**
     * Store a newly created SMS provider.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:sms_providers,name',
            'display_name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'is_primary' => 'boolean',
            'cost_per_sms' => 'required|numeric|min:0',
            'priority' => 'required|integer|min:1',
            'config' => 'required|array'
        ]);

        // Validate config based on provider type
        $this->validateProviderConfig($validated['name'], $validated['config']);

        $provider = SmsProvider::create($validated);

        // If set as primary, unset others
        if ($validated['is_primary'] ?? false) {
            $provider->setPrimary();
        }

        return redirect()->route('admin.sms-providers.index')
            ->with('success', 'SMS provider created successfully.');
    }

    /**
     * Display the specified SMS provider.
     */
    public function show(SmsProvider $smsProvider)
    {
        // Test connection
        $connectionTest = null;
        if ($smsProvider->is_active && $smsProvider->isConfigured()) {
            try {
                $connectionTest = $this->smsManager->testProvider($smsProvider->name);
            } catch (\Exception $e) {
                $connectionTest = [
                    'success' => false,
                    'message' => 'Connection test failed: ' . $e->getMessage()
                ];
            }
        }

        return view('admin.sms-providers.show', compact('smsProvider', 'connectionTest'));
    }

    /**
     * Show the form for editing the specified SMS provider.
     */
    public function edit(SmsProvider $smsProvider)
    {
        return view('admin.sms-providers.edit', compact('smsProvider'));
    }

    /**
     * Update the specified SMS provider.
     */
    public function update(Request $request, SmsProvider $smsProvider)
    {
        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'is_primary' => 'boolean',
            'cost_per_sms' => 'required|numeric|min:0',
            'priority' => 'required|integer|min:1',
            'config' => 'required|array'
        ]);

        // Validate config based on provider type
        $this->validateProviderConfig($smsProvider->name, $validated['config']);

        $smsProvider->update($validated);

        // If set as primary, unset others
        if ($validated['is_primary'] ?? false) {
            $smsProvider->setPrimary();
        }

        return redirect()->route('admin.sms-providers.index')
            ->with('success', 'SMS provider updated successfully.');
    }

    /**
     * Remove the specified SMS provider.
     */
    public function destroy(SmsProvider $smsProvider)
    {
        // Don't allow deletion if it's the only active provider
        $activeProviders = SmsProvider::where('is_active', true)->count();
        if ($smsProvider->is_active && $activeProviders <= 1) {
            return redirect()->route('admin.sms-providers.index')
                ->with('error', 'Cannot delete the only active SMS provider.');
        }

        $smsProvider->delete();

        return redirect()->route('admin.sms-providers.index')
            ->with('success', 'SMS provider deleted successfully.');
    }

    /**
     * Test provider connection.
     */
    public function testConnection(SmsProvider $smsProvider)
    {
        if (!$smsProvider->is_active || !$smsProvider->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Provider is not active or not configured properly.'
            ]);
        }

        try {
            $result = $this->smsManager->testProvider($smsProvider->name);
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('SMS provider connection test failed', [
                'provider' => $smsProvider->name,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get provider balance.
     */
    public function getBalance(SmsProvider $smsProvider)
    {
        if (!$smsProvider->is_active || !$smsProvider->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Provider is not active or not configured properly.'
            ]);
        }

        try {
            $result = $this->smsManager->getProviderBalance($smsProvider->name);
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('SMS provider balance check failed', [
                'provider' => $smsProvider->name,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Balance check failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Set provider as primary.
     */
    public function setPrimary(SmsProvider $smsProvider)
    {
        if (!$smsProvider->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot set inactive provider as primary.'
            ]);
        }

        $smsProvider->setPrimary();

        return response()->json([
            'success' => true,
            'message' => 'Provider set as primary successfully.'
        ]);
    }

    /**
     * Toggle provider status.
     */
    public function toggleStatus(SmsProvider $smsProvider)
    {
        // Don't allow deactivating if it's the only active provider
        if ($smsProvider->is_active) {
            $activeProviders = SmsProvider::where('is_active', true)->count();
            if ($activeProviders <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot deactivate the only active SMS provider.'
                ]);
            }
        }

        $smsProvider->update(['is_active' => !$smsProvider->is_active]);

        // If deactivated and was primary, set another as primary
        if (!$smsProvider->is_active && $smsProvider->is_primary) {
            $newPrimary = SmsProvider::where('is_active', true)->first();
            if ($newPrimary) {
                $newPrimary->setPrimary();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Provider status updated successfully.',
            'is_active' => $smsProvider->is_active
        ]);
    }

    /**
     * Validate provider configuration.
     */
    protected function validateProviderConfig(string $providerName, array $config): void
    {
        switch ($providerName) {
            case 'beem_africa':
                if (!isset($config['api_key']) || !isset($config['secret_key'])) {
                    throw new \InvalidArgumentException('Beem Africa requires api_key and secret_key.');
                }
                break;

            case 'route_africa':
                if (!isset($config['username']) || !isset($config['password'])) {
                    throw new \InvalidArgumentException('Route Africa requires username and password.');
                }
                break;

            default:
                throw new \InvalidArgumentException("Unknown provider: {$providerName}");
        }
    }

    /**
     * Sync sender IDs from Beem Africa API.
     */
    public function syncSenderIds()
    {
        try {
            $provider = SmsProvider::where('name', 'beem_africa')->where('is_active', true)->first();
            
            if (!$provider) {
                return redirect()->route('admin.sms-providers.index')
                    ->with('error', 'Beem Africa provider is not configured or not active.');
            }

            $result = $this->smsManager->getProviderSenderIds('beem_africa');
            
            if ($result['success']) {
                $senderIds = $result['sender_ids'] ?? [];
                
                if (empty($senderIds)) {
                    return redirect()->route('admin.sms-providers.index')
                        ->with('error', 'No sender IDs found in Beem Africa account. Please register sender IDs at https://portal.beem.africa/');
                }
                
                $synced = 0;
                foreach ($senderIds as $senderData) {
                    $senderId = $senderData['sender_id'] ?? $senderData['senderid'] ?? $senderData['name'] ?? null;
                    $status = $senderData['status'] ?? 'unknown';
                    
                    if ($senderId) {
                        \App\Models\Sms\SmsSenderId::updateOrCreate(
                            ['sender_id' => $senderId],
                            [
                                'provider_id' => $senderId,
                                'is_active' => in_array(strtolower($status), ['approved', 'active', '1', 'true', 'live']),
                                'provider_status' => $status,
                            ]
                        );
                        $synced++;
                    }
                }
                
                return redirect()->route('admin.sms-providers.index')
                    ->with('success', "Successfully synced {$synced} sender ID(s) from Beem Africa.");
            }

            return redirect()->route('admin.sms-providers.index')
                ->with('error', 'Failed to sync sender IDs: ' . ($result['message'] ?? 'Unknown error'));
        } catch (\Exception $e) {
            Log::error('Sync sender IDs error', ['error' => $e->getMessage()]);
            return redirect()->route('admin.sms-providers.index')
                ->with('error', 'Error syncing sender IDs: ' . $e->getMessage());
        }
    }
}