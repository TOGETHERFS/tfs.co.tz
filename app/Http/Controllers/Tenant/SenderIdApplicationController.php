<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\SenderId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SenderIdApplicationController extends Controller
{
    /**
     * Display a listing of tenant's sender ID applications.
     */
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $query = SenderId::where('tenant_id', $tenantId);

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sender_id', 'like', "%{$search}%")
                  ->orWhere('business_name', 'like', "%{$search}%");
            });
        }

        $senderIds = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Get summary statistics
        $stats = [
            'total_applications' => SenderId::where('tenant_id', $tenantId)->count(),
            'pending_applications' => SenderId::where('tenant_id', $tenantId)->pending()->count(),
            'approved_applications' => SenderId::where('tenant_id', $tenantId)->approved()->count(),
            'rejected_applications' => SenderId::where('tenant_id', $tenantId)->rejected()->count(),
            'active_sender_ids' => SenderId::where('tenant_id', $tenantId)->active()->count()
        ];

        return view('tenant.sender-ids.index', compact('senderIds', 'stats'));
    }

    /**
     * Show the form for creating a new sender ID application.
     */
    public function create()
    {
        return view('tenant.sender-ids.create');
    }

    /**
     * Store a newly created sender ID application.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sender_id' => [
                'required',
                'string',
                'max:11',
                'alpha_num',
                'unique:sender_ids,sender_id',
                function ($attribute, $value, $fail) {
                    if (!SenderId::isValidSenderId($value)) {
                        $fail('The sender ID must be 3-11 alphanumeric characters.');
                    }
                }
            ],
            'business_name' => 'required|string|max:255',
            'business_description' => 'required|string|min:50|max:1000',
            'business_registration' => 'nullable|string|max:255',
            'use_case' => 'required|string|min:50|max:1000',
            'documents' => 'required|array|min:1',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ], [
            'sender_id.unique' => 'This sender ID is already taken. Please choose a different one.',
            'business_description.min' => 'Business description must be at least 50 characters.',
            'use_case.min' => 'Use case description must be at least 50 characters.',
            'documents.required' => 'At least one supporting document is required.',
            'documents.*.max' => 'Each document must not exceed 5MB.',
        ]);

        // Handle document uploads
        $documents = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $index => $file) {
                $path = $file->store('sender-id-documents', 'public');
                $documents[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                    'uploaded_at' => now()->toISOString()
                ];
            }
        }

        $validated['documents'] = $documents;
        $validated['tenant_id'] = Auth::user()->tenant_id;
        $validated['status'] = SenderId::STATUS_PENDING;
        $validated['is_active'] = false;

        $senderId = SenderId::create($validated);

        return redirect()->route('tenant.sender-ids.show', $senderId)
            ->with('success', 'Sender ID application submitted successfully. We will review your application and get back to you within 2-3 business days.');
    }

    /**
     * Display the specified sender ID application.
     */
    public function show(SenderId $senderId)
    {
        // Ensure the sender ID belongs to the current tenant
        if ($senderId->tenant_id !== Auth::user()->tenant_id) {
            abort(403, 'Unauthorized access to sender ID application.');
        }

        $senderId->load(['approver']);
        
        // Get usage statistics if approved
        $stats = [];
        if ($senderId->isApproved()) {
            $stats = [
                'total_sms_sent' => $senderId->smsLogs()->count(),
                'total_campaigns' => $senderId->smsCampaigns()->count(),
                'last_used' => $senderId->smsLogs()->latest()->first()?->created_at,
                'this_month_usage' => $senderId->smsLogs()
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'delivery_rate' => $this->calculateDeliveryRate($senderId)
            ];
        }

        return view('tenant.sender-ids.show', compact('senderId', 'stats'));
    }

    /**
     * Show the form for editing the specified sender ID.
     */
    public function edit(SenderId $senderId)
    {
        // Ensure the sender ID belongs to the current tenant
        if ($senderId->tenant_id !== Auth::user()->tenant_id) {
            abort(403, 'Unauthorized access to sender ID application.');
        }

        // Only allow editing if status is pending or rejected
        if (!in_array($senderId->status, [SenderId::STATUS_PENDING, SenderId::STATUS_REJECTED])) {
            return redirect()->route('tenant.sender-ids.show', $senderId)
                ->with('error', 'You can only edit pending or rejected applications.');
        }

        return view('tenant.sender-ids.edit', compact('senderId'));
    }

    /**
     * Update the specified sender ID application.
     */
    public function update(Request $request, SenderId $senderId)
    {
        // Ensure the sender ID belongs to the current tenant
        if ($senderId->tenant_id !== Auth::user()->tenant_id) {
            abort(403, 'Unauthorized access to sender ID application.');
        }

        // Only allow updating if status is pending or rejected
        if (!in_array($senderId->status, [SenderId::STATUS_PENDING, SenderId::STATUS_REJECTED])) {
            return redirect()->route('tenant.sender-ids.show', $senderId)
                ->with('error', 'You can only edit pending or rejected applications.');
        }

        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'business_description' => 'required|string|min:50|max:1000',
            'business_registration' => 'nullable|string|max:255',
            'use_case' => 'required|string|min:50|max:1000',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
            'remove_documents' => 'nullable|array',
            'remove_documents.*' => 'integer'
        ], [
            'business_description.min' => 'Business description must be at least 50 characters.',
            'use_case.min' => 'Use case description must be at least 50 characters.',
            'documents.*.max' => 'Each document must not exceed 5MB.',
        ]);

        // Handle document removal
        $existingDocuments = $senderId->documents ?? [];
        if ($request->filled('remove_documents')) {
            foreach ($request->remove_documents as $index) {
                if (isset($existingDocuments[$index])) {
                    // Delete file from storage
                    Storage::disk('public')->delete($existingDocuments[$index]['path']);
                    unset($existingDocuments[$index]);
                }
            }
            $existingDocuments = array_values($existingDocuments); // Re-index array
        }

        // Handle new document uploads
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('sender-id-documents', 'public');
                $existingDocuments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                    'uploaded_at' => now()->toISOString()
                ];
            }
        }

        $validated['documents'] = $existingDocuments;

        // If this was a rejected application, reset status to pending
        if ($senderId->status === SenderId::STATUS_REJECTED) {
            $validated['status'] = SenderId::STATUS_PENDING;
            $validated['rejection_reason'] = null;
            $validated['rejected_at'] = null;
        }

        $senderId->update($validated);

        return redirect()->route('tenant.sender-ids.show', $senderId)
            ->with('success', 'Sender ID application updated successfully.');
    }

    /**
     * Remove the specified sender ID application.
     */
    public function destroy(SenderId $senderId)
    {
        // Ensure the sender ID belongs to the current tenant
        if ($senderId->tenant_id !== Auth::user()->tenant_id) {
            abort(403, 'Unauthorized access to sender ID application.');
        }

        // Only allow deletion if status is pending or rejected
        if (!in_array($senderId->status, [SenderId::STATUS_PENDING, SenderId::STATUS_REJECTED])) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete pending or rejected applications.'
            ]);
        }

        // Delete associated documents
        if ($senderId->documents) {
            foreach ($senderId->documents as $document) {
                Storage::disk('public')->delete($document['path']);
            }
        }

        $senderId->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sender ID application deleted successfully.'
        ]);
    }

    /**
     * Download a document from the sender ID application.
     */
    public function downloadDocument(SenderId $senderId, int $documentIndex)
    {
        // Ensure the sender ID belongs to the current tenant
        if ($senderId->tenant_id !== Auth::user()->tenant_id) {
            abort(403, 'Unauthorized access to sender ID application.');
        }

        $documents = $senderId->documents ?? [];
        if (!isset($documents[$documentIndex])) {
            abort(404, 'Document not found.');
        }

        $document = $documents[$documentIndex];
        $filePath = storage_path('app/public/' . $document['path']);

        if (!file_exists($filePath)) {
            abort(404, 'File not found.');
        }

        return response()->download($filePath, $document['name']);
    }

    /**
     * Get sender ID application guidelines.
     */
    public function guidelines()
    {
        return view('tenant.sender-ids.guidelines');
    }

    /**
     * Calculate delivery rate for a sender ID.
     */
    private function calculateDeliveryRate(SenderId $senderId): float
    {
        $totalSms = $senderId->smsLogs()->count();
        if ($totalSms === 0) {
            return 0;
        }

        $deliveredSms = $senderId->smsLogs()
            ->where('status', 'delivered')
            ->count();

        return round(($deliveredSms / $totalSms) * 100, 2);
    }
}