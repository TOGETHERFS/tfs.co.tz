<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SenderId;
use App\Models\Sms\SmsSenderIdRequest;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SenderIdController extends Controller
{
    /**
     * Display a listing of sender ID applications.
     */
    public function index(Request $request)
    {
        // Query SmsSenderIdRequest (tenant applications)
        $query = SmsSenderIdRequest::with(['tenant', 'requestedBy', 'approvedBy']);

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sender_id', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhereHas('tenant', function ($tq) use ($search) {
                      $tq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Date filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $senderIds = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Get summary statistics
        $stats = [
            'total_applications' => SmsSenderIdRequest::count(),
            'pending_applications' => SmsSenderIdRequest::pending()->count(),
            'approved_applications' => SmsSenderIdRequest::approved()->count(),
            'rejected_applications' => SmsSenderIdRequest::where('status', 'rejected')->count(),
            'active_sender_ids' => SmsSenderIdRequest::where('status', 'live')->count()
        ];

        return view('admin.sender-ids.index', compact('senderIds', 'stats'));
    }

    /**
     * Display the specified sender ID application.
     */
    public function show($id)
    {
        $senderId = SmsSenderIdRequest::with(['tenant', 'requestedBy'])->findOrFail($id);
        
        // Get usage statistics
        $stats = [
            'total_sms_sent' => 0,
            'total_campaigns' => 0,
            'last_used' => null,
            'delivery_rate' => 0,
            'monthly_usage' => []
        ];

        return view('admin.sender-ids.show', compact('senderId', 'stats'));
    }

    /**
     * Show the form for creating a new sender ID application.
     */
    public function create()
    {
        $tenants = Tenant::orderBy('name')->get();
        return view('admin.sender-ids.create', compact('tenants'));
    }

    /**
     * Store a newly created sender ID application.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'sender_id' => 'required|string|max:11|alpha_num|unique:sender_ids,sender_id',
            'business_name' => 'required|string|max:255',
            'business_description' => 'required|string',
            'business_registration' => 'nullable|string|max:255',
            'use_case' => 'required|string',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
            'status' => 'nullable|in:pending,approved,rejected',
            'is_active' => 'boolean'
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
                    'type' => $file->getMimeType()
                ];
            }
        }
        $validated['documents'] = $documents;

        // Set default status if not provided
        if (!isset($validated['status'])) {
            $validated['status'] = 'pending';
        }

        $senderId = SenderId::create($validated);

        // If approved during creation, set approval details
        if ($validated['status'] === 'approved') {
            $senderId->approve(Auth::id());
        }

        return redirect()->route('admin.sender-ids.index')
            ->with('success', 'Sender ID application created successfully.');
    }

    /**
     * Show the form for editing the specified sender ID.
     */
    public function edit(SenderId $senderId)
    {
        $tenants = Tenant::orderBy('name')->get();
        return view('admin.sender-ids.edit', compact('senderId', 'tenants'));
    }

    /**
     * Update the specified sender ID.
     */
    public function update(Request $request, SenderId $senderId)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'business_description' => 'required|string',
            'business_registration' => 'nullable|string|max:255',
            'use_case' => 'required|string',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
            'is_active' => 'boolean'
        ]);

        // Handle new document uploads
        $existingDocuments = $senderId->documents ?? [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('sender-id-documents', 'public');
                $existingDocuments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType()
                ];
            }
        }
        $validated['documents'] = $existingDocuments;

        $senderId->update($validated);

        return redirect()->route('admin.sender-ids.show', $senderId)
            ->with('success', 'Sender ID updated successfully.');
    }

    /**
     * Approve a sender ID application.
     */
    public function approve(Request $request, $id)
    {
        $senderIdRequest = SmsSenderIdRequest::findOrFail($id);

        if ($senderIdRequest->status !== 'pending') {
            return redirect()->route('admin.sender-ids.index')
                ->with('error', 'Only pending applications can be approved.');
        }

        $senderIdRequest->approve(Auth::id());

        return redirect()->route('admin.sender-ids.index')
            ->with('success', 'Sender ID "' . $senderIdRequest->sender_id . '" approved successfully.');
    }

    /**
     * Reject a sender ID application.
     */
    public function reject(Request $request, $id)
    {
        $senderIdRequest = SmsSenderIdRequest::findOrFail($id);

        if ($senderIdRequest->status !== 'pending') {
            return redirect()->route('admin.sender-ids.index')
                ->with('error', 'Only pending applications can be rejected.');
        }

        $reason = $request->input('rejection_reason', 'Application rejected by admin');
        $senderIdRequest->reject(Auth::id(), $reason);

        return redirect()->route('admin.sender-ids.index')
            ->with('success', 'Sender ID "' . $senderIdRequest->sender_id . '" rejected.');
    }

    /**
     * Suspend a sender ID.
     */
    public function suspend(SenderId $senderId)
    {
        if (!$senderId->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Sender ID is already suspended.'
            ]);
        }

        $success = $senderId->suspend();

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Sender ID suspended successfully.',
                'is_active' => false
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to suspend sender ID.'
        ]);
    }

    /**
     * Activate a sender ID.
     */
    public function activate(SenderId $senderId)
    {
        if ($senderId->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only approved sender IDs can be activated.'
            ]);
        }

        if ($senderId->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Sender ID is already active.'
            ]);
        }

        $success = $senderId->activate();

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Sender ID activated successfully.',
                'is_active' => true
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to activate sender ID.'
        ]);
    }

    /**
     * Delete a document from sender ID application.
     */
    public function deleteDocument(Request $request, SenderId $senderId)
    {
        $validated = $request->validate([
            'document_index' => 'required|integer|min:0'
        ]);

        $documents = $senderId->documents ?? [];
        $index = $validated['document_index'];

        if (!isset($documents[$index])) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found.'
            ]);
        }

        // Delete file from storage
        if (isset($documents[$index]['path'])) {
            Storage::disk('public')->delete($documents[$index]['path']);
        }

        // Remove document from array
        unset($documents[$index]);
        $documents = array_values($documents); // Re-index array

        $senderId->update(['documents' => $documents]);

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully.'
        ]);
    }

    /**
     * Bulk operations on sender IDs.
     */
    public function bulkOperation(Request $request)
    {
        $validated = $request->validate([
            'operation' => 'required|in:approve,reject,suspend,activate',
            'sender_id_ids' => 'required|array',
            'sender_id_ids.*' => 'exists:sender_ids,id',
            'rejection_reason' => 'required_if:operation,reject|string|max:500'
        ]);

        $senderIds = SenderId::whereIn('id', $validated['sender_id_ids'])->get();
        $successCount = 0;
        $errors = [];

        foreach ($senderIds as $senderId) {
            try {
                switch ($validated['operation']) {
                    case 'approve':
                        if ($senderId->status === 'pending') {
                            $senderId->approve(Auth::id());
                            $successCount++;
                        } else {
                            $errors[] = "{$senderId->sender_id} is not pending";
                        }
                        break;

                    case 'reject':
                        if ($senderId->status === 'pending') {
                            $senderId->reject($validated['rejection_reason'], Auth::id());
                            $successCount++;
                        } else {
                            $errors[] = "{$senderId->sender_id} is not pending";
                        }
                        break;

                    case 'suspend':
                        if ($senderId->is_active) {
                            $senderId->suspend();
                            $successCount++;
                        } else {
                            $errors[] = "{$senderId->sender_id} is already suspended";
                        }
                        break;

                    case 'activate':
                        if ($senderId->status === 'approved' && !$senderId->is_active) {
                            $senderId->activate();
                            $successCount++;
                        } else {
                            $errors[] = "{$senderId->sender_id} cannot be activated";
                        }
                        break;
                }
            } catch (\Exception $e) {
                $errors[] = "Error with {$senderId->sender_id}: " . $e->getMessage();
            }
        }

        $message = "Operation completed. {$successCount} sender IDs processed successfully.";
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', $errors);
        }

        return redirect()->route('admin.sender-ids.index')
            ->with($successCount > 0 ? 'success' : 'error', $message);
    }

    /**
     * Calculate delivery rate for a sender ID.
     */
    protected function calculateDeliveryRate(SenderId $senderId): float
    {
        $totalSent = $senderId->smsLogs()->whereIn('status', ['sent', 'delivered', 'failed'])->count();
        $delivered = $senderId->smsLogs()->where('status', 'delivered')->count();

        return $totalSent > 0 ? round(($delivered / $totalSent) * 100, 2) : 0;
    }

    /**
     * Get monthly usage statistics for a sender ID.
     */
    protected function getMonthlyUsage(SenderId $senderId): array
    {
        $monthlyData = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $count = $senderId->smsLogs()
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            
            $monthlyData[] = [
                'month' => $date->format('M Y'),
                'count' => $count
            ];
        }

        return $monthlyData;
    }
}