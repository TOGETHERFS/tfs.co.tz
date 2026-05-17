<?php

namespace App\Http\Controllers;

use App\Models\StaffDocument;
use App\Models\DocumentRecipient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    // -------------------------------------------------------
    // List documents (sent to me + sent by me)
    // -------------------------------------------------------
    public function index()
    {
        $user     = Auth::user();
        $tenantId = session('tenant_id') ?? $user->tenant_id;
        $isAdmin  = in_array($user->role, ['admin', 'super_admin', 'manager']);

        // Documents I received
        $received = StaffDocument::whereHas('recipients', fn($q) => $q->where('recipient_id', $user->id))
            ->where('tenant_id', $tenantId)
            ->with(['uploader', 'recipients'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Documents I uploaded
        $sent = StaffDocument::where('uploaded_by', $user->id)
            ->where('tenant_id', $tenantId)
            ->with('recipientUsers')
            ->orderBy('created_at', 'desc')
            ->get();

        // Admin: all documents in the tenant
        $allDocuments = collect();
        if ($isAdmin) {
            $allDocuments = StaffDocument::where('tenant_id', $tenantId)
                ->with(['uploader', 'recipientUsers'])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return view('documents.index', compact('received', 'sent', 'allDocuments', 'isAdmin'));
    }

    // -------------------------------------------------------
    // Show upload form
    // -------------------------------------------------------
    public function create()
    {
        $user     = Auth::user();
        $tenantId = session('tenant_id') ?? $user->tenant_id;

        $staff = User::where('tenant_id', $tenantId)
            ->where('id', '!=', $user->id)
            ->get();

        return view('documents.create', compact('staff'));
    }

    // -------------------------------------------------------
    // Store uploaded document
    // -------------------------------------------------------
    public function store(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string|max:1000',
            'file'         => 'required|file|max:20480', // 20MB max
            'recipient_ids'=> 'required|array|min:1',
            'recipient_ids.*' => 'exists:users,id',
        ]);

        $user     = Auth::user();
        $tenantId = session('tenant_id') ?? $user->tenant_id;

        $file     = $request->file('file');
        $path     = $file->store('documents/' . $tenantId, 'private');

        $document = StaffDocument::create([
            'tenant_id'   => $tenantId,
            'uploaded_by' => $user->id,
            'title'       => $request->title,
            'description' => $request->description,
            'file_path'   => $path,
            'file_name'   => $file->getClientOriginalName(),
            'file_size'   => $this->formatSize($file->getSize()),
            'mime_type'   => $file->getMimeType(),
        ]);

        // Create recipient entries
        foreach ($request->recipient_ids as $recipientId) {
            DocumentRecipient::create([
                'document_id'  => $document->id,
                'recipient_id' => $recipientId,
            ]);
        }

        return redirect()->route('documents.index')
            ->with('success', __('messages.document_uploaded'));
    }

    // -------------------------------------------------------
    // Download document
    // -------------------------------------------------------
    public function download(StaffDocument $document)
    {
        $user     = Auth::user();
        $tenantId = session('tenant_id') ?? $user->tenant_id;

        abort_unless($document->tenant_id == $tenantId, 403);

        // Must be uploader or a recipient
        $isRecipient = DocumentRecipient::where('document_id', $document->id)
            ->where('recipient_id', $user->id)->exists();

        if (!$user->isAdmin() && $document->uploaded_by !== $user->id && !$isRecipient) {
            abort(403);
        }

        // Mark as read
        DocumentRecipient::where('document_id', $document->id)
            ->where('recipient_id', $user->id)
            ->update(['is_read' => true, 'read_at' => now()]);

        return Storage::disk('private')->download($document->file_path, $document->file_name);
    }

    // -------------------------------------------------------
    // View document details
    // -------------------------------------------------------
    public function show(StaffDocument $document)
    {
        $user     = Auth::user();
        $tenantId = session('tenant_id') ?? $user->tenant_id;

        abort_unless($document->tenant_id == $tenantId, 403);

        $isRecipient = DocumentRecipient::where('document_id', $document->id)
            ->where('recipient_id', $user->id)->exists();

        if (!$user->isAdmin() && $document->uploaded_by !== $user->id && !$isRecipient) {
            abort(403);
        }

        $document->load('uploader', 'recipientUsers');

        // Mark as read
        DocumentRecipient::where('document_id', $document->id)
            ->where('recipient_id', $user->id)
            ->update(['is_read' => true, 'read_at' => now()]);

        return view('documents.show', compact('document'));
    }

    // -------------------------------------------------------
    // Delete document (uploader or admin only)
    // -------------------------------------------------------
    public function destroy(StaffDocument $document)
    {
        $user     = Auth::user();
        $tenantId = session('tenant_id') ?? $user->tenant_id;

        abort_unless($document->tenant_id == $tenantId, 403);

        if (!$user->isAdmin() && $document->uploaded_by !== $user->id) {
            abort(403);
        }

        Storage::disk('private')->delete($document->file_path);
        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', __('messages.document_deleted'));
    }

    // -------------------------------------------------------
    // Helper: format bytes
    // -------------------------------------------------------
    private function formatSize(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
