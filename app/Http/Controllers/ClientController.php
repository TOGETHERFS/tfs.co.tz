<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Group;
use App\Models\LoanProduct;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ClientsImport;
use App\Models\Loan;
use App\Models\LoanSchedule;
use App\Models\Repayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    /**
     * Display a listing of the clients.
     */
    public function index(Request $request)
    {
        $query = Client::with('loans');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('id_number', 'like', "%{$search}%");
            });
        }

        // Status filter (exclude blacklisted by default)
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        } else {
            $query->where('status', '<>', 'blacklisted');
        }

        $clients = $query->latest()->paginate(15);

        return view('clients.index', compact('clients'));
    }

    /**
     * Show the form for creating a new client.
     */
    public function create()
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;
        
        // Validate tenant context
        if (!$tenantId) {
            return redirect()->route('login')
                ->withErrors(['session' => 'Session expired. Please log in again.']);
        }
        
        $products = LoanProduct::active()->get([
            'id',
            'name',
            'description',
            'min_amount',
            'max_amount',
            'interest_rate',
            'min_term',
            'max_term',
            'processing_fee as processing_fee_rate',
        ]);

        // Get branches for the tenant
        $branches = Branch::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Get loan officers (users with loan_officer role via column OR roles relationship)
        $loanOfficerRoles = ['loan_officer', 'officer', 'manager', 'admin', 'credit_officer', 'gm'];
        $loanOfficers = User::where('tenant_id', $tenantId)
            ->where(function ($query) use ($loanOfficerRoles) {
                // Check legacy role column (case-insensitive)
                $query->whereRaw('LOWER(role) IN (' . implode(',', array_fill(0, count($loanOfficerRoles), '?')) . ')', $loanOfficerRoles)
                    // OR check roles relationship via pivot table
                    ->orWhereHas('roles', function ($q) use ($loanOfficerRoles) {
                        $q->whereIn('slug', $loanOfficerRoles);
                    });
            })
            ->orderBy('name')
            ->get();

        // Check for missing essential data and auto-repair if needed
        if ($branches->isEmpty() || $products->isEmpty()) {
            $tenant = \App\Models\Tenant::find($tenantId);
            if ($tenant) {
                \App\Services\TenantOnboardingService::repairTenant($tenant);
                // Reload data after repair
                $branches = Branch::where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get();
                $products = LoanProduct::active()->get([
                    'id', 'name', 'description', 'min_amount', 'max_amount',
                    'interest_rate', 'min_term', 'max_term',
                    'processing_fee as processing_fee_rate',
                ]);
            }
        }

        $groups = Group::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('clients.create', compact('products', 'branches', 'loanOfficers', 'groups'));
    }

    /**
     * Store a newly created client in storage.
     */
    public function store(Request $request)
    {
        // Debug logging - capture all request details
        logger()->info('Client store - START', [
            'session_tenant_id' => session('tenant_id'),
            'auth_user_id' => auth()->id(),
            'auth_user_tenant_id' => optional(auth()->user())->tenant_id,
            'request_data' => $request->except(['photo', 'password']),
            'expects_json' => $request->expectsJson(),
            'content_type' => $request->header('Content-Type'),
            'accept' => $request->header('Accept'),
        ]);

        // Convert date_of_birth from DD/MM/YYYY to YYYY-MM-DD if needed
        if ($request->date_of_birth && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $request->date_of_birth)) {
            [$d, $m, $y] = explode('/', $request->date_of_birth);
            $request->merge(['date_of_birth' => "{$y}-{$m}-{$d}"]);
        }

        // Resolve tenant early - needed for validation rules
        $tenantId = session('tenant_id') ?? optional(auth()->user())->tenant_id;
        
        if (!$tenantId) {
            logger()->error('Client store - No tenant context');
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tenant context not resolved. Please log in again.',
                    'errors' => ['tenant' => ['Session expired. Please log in again.']]
                ], 422);
            }
            return back()->withErrors(['tenant' => 'Session expired. Please log in again.'])->withInput();
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => [
                'required',
                'string',
                'regex:/^255[0-9]{9}$/',
                Rule::unique('clients')->where('tenant_id', session('tenant_id'))
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('clients')->where('tenant_id', session('tenant_id'))
            ],
            'address' => 'nullable|string|max:500',
            'region' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'ward' => 'nullable|string|max:100',
            'street' => 'nullable|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'id_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('clients')->where('tenant_id', session('tenant_id'))
            ],
            'status' => 'required|in:active,inactive,blacklisted',
            'branch_name' => 'nullable|string|max:255',
            'loan_officer' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'marital_status' => 'nullable|string|in:single,married,divorced,widowed',
            'occupation' => 'nullable|string|max:255',
            'monthly_income' => 'nullable|numeric|min:0',
            'employer' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|in:employed,self_employed,business_owner,farmer,unemployed,retired',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relationship' => 'nullable|string|in:spouse,parent,child,sibling,friend,director,other',
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')->where('tenant_id', session('tenant_id'))
            ],
            'loan_officer_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('tenant_id', session('tenant_id'))
            ],
            'initial_product_id' => 'nullable|exists:loan_products,id',
            'group_id' => 'nullable|exists:groups,id',
            'action' => 'nullable|string|in:save,save_and_new',
        ]);

        // Resolve tenant and set it explicitly to avoid NOT NULL violations
        $tenantId = session('tenant_id') ?? optional(auth()->user())->tenant_id;
        if (!$tenantId) {
            // If tenant cannot be resolved, return appropriate error response
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tenant context not resolved. Please log in again or select a tenant.',
                ], 422);
            }
            return back()->withErrors(['tenant' => 'Tenant context not resolved. Please log in again or select a tenant.'])->withInput();
        }
        $validated['tenant_id'] = $tenantId;

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('clients/photos', 'public');
            $validated['photo_path'] = $path;
        }

        $groupId = $validated['group_id'] ?? null;
        unset($validated['group_id']);

        try {
            $client = Client::create($validated);
            // Auto-assign to group if selected
            if ($groupId) {
                $group = Group::where('tenant_id', $tenantId)->find($groupId);
                if ($group) {
                    $group->clients()->syncWithoutDetaching([$client->id]);
                }
            }
        } catch (\Exception $e) {
            logger()->error('Client creation failed', [
                'error' => $e->getMessage(),
                'validated_data' => $validated,
            ]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Failed to create borrower: ' . $e->getMessage(),
                    'errors' => ['general' => [$e->getMessage()]]
                ], 500);
            }
            
            return back()->withErrors(['general' => 'Failed to create borrower: ' . $e->getMessage()])->withInput();
        }

        // If the request expects JSON (AJAX/fetch), return JSON with redirect URL
        if ($request->expectsJson()) {
            return response()->json([
                'redirect' => route('clients.show', $client),
                'client_id' => $client->id,
            ]);
        }

        // Check which action was requested
        $action = $request->input('action', 'save');
        
        if ($action === 'save_and_new') {
            return redirect()->route('clients.create')
                            ->with('success', 'Borrower created successfully. You can now create another borrower.');
        }

        return redirect()->route('clients.show', $client)
                        ->with('success', 'Borrower created successfully.');
    }

    /**
     * Display the specified client.
     */
    public function show(Client $client)
    {
        $client->load(['loans.product', 'loans.schedules', 'repayments']);
        
        return view('clients.show', compact('client'));
    }

    /**
     * Show the form for editing the specified client.
     */
    public function edit(Client $client)
    {
        return view('clients.edit', compact('client'));
    }

    /**
     * Update the specified client in storage.
     */
    public function update(Request $request, Client $client)
    {
        // Convert date_of_birth from DD/MM/YYYY to YYYY-MM-DD if needed
        if ($request->date_of_birth && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $request->date_of_birth)) {
            [$d, $m, $y] = explode('/', $request->date_of_birth);
            $request->merge(['date_of_birth' => "{$y}-{$m}-{$d}"]);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => [
                'required',
                'string',
                'regex:/^255[0-9]{9}$/',
                Rule::unique('clients')->where('tenant_id', session('tenant_id'))->ignore($client->id)
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('clients')->where('tenant_id', session('tenant_id'))->ignore($client->id)
            ],
            'address' => 'nullable|string|max:500',
            'region' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'ward' => 'nullable|string|max:100',
            'street' => 'nullable|string|max:255',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'id_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('clients')->where('tenant_id', session('tenant_id'))->ignore($client->id)
            ],
            'status' => 'required|in:active,inactive,blacklisted',
        ]);

        $client->update($validated);

        return redirect()->route('clients.show', $client)
                        ->with('success', 'Borrower updated successfully.');
    }

    /**
     * Remove the specified client from storage.
     */
    public function destroy(Request $request, Client $client)
    {
        // Only superadmin can force delete with related records
        $forceDelete = $request->has('force') && auth()->user()->role === 'superadmin';
        
        if (!$forceDelete && $client->hasActiveLoans()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete borrower with active loans. Use force delete as superadmin.'
                ], 422);
            }
            return redirect()->route('clients.show', $client)
                           ->with('error', 'Cannot delete borrower with active loans.');
        }

        DB::beginTransaction();
        try {
            // Delete all related records
            foreach ($client->loans as $loan) {
                $loan->schedules()->delete();
                $loan->documents()->delete();
                $loan->repayments()->delete();
                
                // Delete workflow related records if they exist
                if (method_exists($loan, 'workflowState') && $loan->workflowState) {
                    $loan->workflowState()->delete();
                }
                if (method_exists($loan, 'workflowLogs')) {
                    $loan->workflowLogs()->delete();
                }
                if (method_exists($loan, 'approvals')) {
                    $loan->approvals()->delete();
                }
                
                $loan->delete();
            }
            
            $client->delete();
            
            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Borrower and all related records deleted successfully.'
                ]);
            }

            return redirect()->route('clients.index')
                            ->with('success', 'Borrower and all related records deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Client deletion failed', ['error' => $e->getMessage(), 'client_id' => $client->id]);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete borrower: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Failed to delete borrower.');
        }
    }

    /**
     * Get client statistics.
     */
    public function statistics()
    {
        $stats = [
            'total_clients' => Client::count(),
            'active_clients' => Client::where('status', 'active')->count(),
            'clients_with_loans' => Client::whereHas('activeLoans')->count(),
            'new_clients_this_month' => Client::whereBetween('created_at', [
                                             now()->startOfMonth(),
                                             now()->endOfMonth()
                                         ])->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Show the import clients form.
     */
    public function importForm()
    {
        return view('clients.import');
    }

    /**
     * Process the uploaded Excel/CSV and import clients.
     */
    public function importProcess(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv,text/plain,application/csv|max:10240',
        ], [
            'file.required' => 'Please select a file to import.',
            'file.file' => 'The uploaded file is invalid.',
            'file.mimetypes' => 'The file must be an Excel (.xlsx, .xls) or CSV file.',
            'file.max' => 'The file size must not exceed 10MB.',
        ]);

        $tenantId = session('tenant_id') ?? optional(auth()->user())->tenant_id;
        if (!$tenantId) {
            return back()->withErrors(['tenant' => 'Tenant context not resolved. Please log in again or select a tenant.']);
        }

        try {
            $import = new ClientsImport($tenantId);
            Excel::import($import, $request->file('file'));

            $message = sprintf(
                'Import complete: %d created, %d updated%s',
                $import->created,
                $import->updated,
                count($import->errors) ? ", " . count($import->errors) . " rows skipped" : ''
            );

            return redirect()->route('clients.index')
                ->with('success', $message)
                ->with('import_errors', $import->errors);
        } catch (\Exception $e) {
            logger()->error('Client import failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['import' => 'Import failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Download a CSV template for borrower imports.
     */
    public function downloadTemplate()
    {
        $headers = [
            'first_name',
            'last_name',
            'phone',
            'email',
            'id_number',
            'date_of_birth',
            'gender',
            'address',
            'region',
            'district',
            'ward',
            'street',
            'status',
            'branch_name',
            'loan_officer',
        ];

        $exampleRow = [
            'John',                    // first_name
            'Doe',                     // last_name
            '255712345678',            // phone
            'john.doe@example.com',    // email
            '12345678901234567890',    // id_number
            '1990-01-15',              // date_of_birth
            'male',                    // gender
            '123 Main Street',         // address
            'Dar es Salaam',           // region
            'Ilala',                   // district
            'Kariakoo',                // ward
            'Msimbazi Street',         // street
            'active',                  // status
            'Main Branch',             // branch_name
            'Jane Smith',              // loan_officer
        ];

        $callback = function() use ($headers, $exampleRow) {
            $file = fopen('php://output', 'w');
            // Add BOM for Excel UTF-8 compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $headers);
            fputcsv($file, $exampleRow);
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="borrower_import_template.csv"',
        ]);
    }

    /**
     * Purge all borrowers and related records for the current tenant.
     */
    public function purge(Request $request)
    {
        $tenantId = session('tenant_id') ?? optional(auth()->user())->tenant_id;
        if (!$tenantId) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tenant context not resolved. Please log in again.'], 422);
            }
            return back()->withErrors(['tenant' => 'Tenant context not resolved. Please log in again.']);
        }

        DB::transaction(function () use ($tenantId) {
            // Delete in dependency order to satisfy foreign key constraints
            Repayment::where('tenant_id', $tenantId)->delete();
            LoanSchedule::where('tenant_id', $tenantId)->delete();
            Loan::where('tenant_id', $tenantId)->delete();
            Client::where('tenant_id', $tenantId)->delete();
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'All borrowers deleted successfully.']);
        }

        return redirect()->route('clients.index')->with('success', 'All borrowers deleted successfully.');
    }

    /**
     * JSON data endpoint for borrowers list (session-authenticated, tenant-scoped).
     */
    public function data(Request $request)
    {
        $tenantId = session('tenant_id') ?? optional(auth()->user())->tenant_id;
        $query = Client::withoutGlobalScope('tenant')->with(['groups'])->withCount('loans');
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        } else {
            // Prevent cross-tenant leakage when no tenant context is available
            $query->whereRaw('1 = 0');
        }

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('id_number', 'like', "%{$search}%")
                  ->orWhere('client_id', 'like', "%{$search}%");
            });
        }

        // Status filter (exclude blacklisted by default)
        $status = $request->get('status');
        if ($status === 'all' || empty($status)) {
            $query->where('status', '<>', 'blacklisted');
        } else {
            $query->where('status', $status);
        }

        // Sort
        $sort = $request->get('sort', 'created_at_desc');
        switch ($sort) {
            case 'created_at_asc':
                $query->orderBy('created_at', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('first_name', 'asc')->orderBy('last_name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('first_name', 'desc')->orderBy('last_name', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = (int) $request->get('per_page', 15);
        $clients = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $clients,
        ]);
    }

    // Add session-auth borrower search endpoint
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = $request->get('query');

        // Respect current session tenant if present
        $tenantId = session('tenant_id') ?? optional(auth()->user())->tenant_id;

        $clientsQuery = Client::withoutGlobalScope('tenant');
        if ($tenantId) {
            $clientsQuery->where('tenant_id', $tenantId);
        } else {
            // No tenant context; return empty result to avoid leakage
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $clients = $clientsQuery->where(function ($q) use ($query) {
            $q->where('first_name', 'like', "%{$query}%")
              ->orWhere('last_name', 'like', "%{$query}%")
              ->orWhere('email', 'like', "%{$query}%")
              ->orWhere('phone', 'like', "%{$query}%")
              ->orWhere('id_number', 'like', "%{$query}%");
        })
        ->limit(10)
        ->get(['id', 'first_name', 'last_name', 'email', 'phone', 'id_number']);

        return response()->json([
            'success' => true,
            'data' => $clients,
        ]);
    }

    // Add session-auth borrower JSON show endpoint
    public function json(Client $client)
    {
        // Ensure client belongs to current tenant if tenant scoped
        $tenantId = session('tenant_id');
        if ($tenantId && $client->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'Client not found in current tenant.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $client,
        ]);
    }
    public function toggleStatus(Client $client)
    {
        $tenantId = session('tenant_id') ?? optional(auth()->user())->tenant_id;
        if ($tenantId && $client->tenant_id !== $tenantId) {
            return response()->json([
                'message' => 'Borrower not found in current tenant.'
            ], 404);
        }

        $newStatus = $client->status === 'active' ? 'inactive' : 'active';
        $client->update(['status' => $newStatus]);

        return response()->json(['success' => true, 'status' => $newStatus]);
    }
}