<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\LoanDocument;
use App\Models\LoanProduct;
use App\Models\LoanSchedule;
use App\Models\Repayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Services\ApprovalPipelineService;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        // Stats for dashboard cards
        $activeIds = Loan::whereIn('status', ['disbursed', 'active'])->pluck('id');
        $totalActiveToPay = (float) Loan::whereIn('status', ['disbursed', 'active'])->sum('total_amount');
        $totalRepaid      = (float) Repayment::whereIn('loan_id', $activeIds)->sum('amount');

        $stats = [
            'total_loans'      => Loan::count(),
            'active_loans'     => Loan::whereIn('status', ['disbursed', 'active'])->count(),
            'pending_loans'    => Loan::where('status', 'pending')->count(),
            'total_outstanding'=> $totalActiveToPay - $totalRepaid,
            'total_disbursed'  => Loan::whereIn('status', ['disbursed', 'active', 'completed'])->sum('principal'),
            'completed_loans'  => Loan::where('status', 'completed')->count(),
            'overdue_loans'    => Loan::overdue()->count(),
        ];

        // Paginated loans list with filters
        $query = Loan::with(['client', 'product'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('loan_number', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($c) use ($search) {
                      $c->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        $sort = $request->get('sort', 'created_at_desc');
        match ($sort) {
            'created_at_asc'  => $query->oldest(),
            'amount_desc'     => $query->orderBy('principal', 'desc'),
            'amount_asc'      => $query->orderBy('principal', 'asc'),
            default           => $query->latest(),
        };

        $loans        = $query->paginate(15)->withQueryString();
        $loan_products = LoanProduct::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $recent_loans  = Loan::with(['client', 'product'])->latest()->take(5)->get();

        return view('loans.index', compact('recent_loans', 'stats', 'loans', 'loan_products'));
    }

    public function create()
    {
        // Provide initial loan products for SSR fallback in the create view
        $products = LoanProduct::query()
            ->where('is_active', true)
            ->get([
                'id',
                'name',
                'description',
                'min_amount',
                'max_amount',
                'interest_rate',
                'interest_type',
                'repayment_type',
                'min_term',
                'max_term',
                'processing_fee',
                'processing_fee_type',
            ]);

        $groups = \App\Models\Group::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('loans.create', compact('products', 'groups'));
    }

    public function show(Loan $loan)
    {
        // Render dedicated loan details page with eager-loaded relations for SSR
        $loan->load(['client', 'product', 'schedules', 'repayments', 'documents']);
        return view('loans.show', compact('loan'));
    }

    public function edit(Loan $loan)
    {
        $products = LoanProduct::query()
            ->where('is_active', true)
            ->get([
                'id',
                'name',
                'description',
                'min_amount',
                'max_amount',
                'interest_rate',
                'interest_type',
                'repayment_type',
                'min_term',
                'max_term',
                'processing_fee',
                'processing_fee_type',
            ]);

        $loan->load(['client', 'product']);

        $productsJson = $products->map(function ($p) {
            return [
                'id'                  => $p->id,
                'interest_rate'       => (float) $p->interest_rate,
                'interest_type'       => $p->interest_type,
                'repayment_type'      => $p->repayment_type ?? 'amortized',
                'processing_fee'      => (float) $p->processing_fee,
                'processing_fee_type' => $p->processing_fee_type ?? 'percentage',
            ];
        })->values()->toArray();

        return view('loans.edit', compact('loan', 'products', 'productsJson'));
    }

    public function update(Request $request, Loan $loan)
    {
        // Determine validation rules based on processing_fee_type
        $processingFeeType = $request->input('processing_fee_type', 'percentage');
        $processingFeeRule = $processingFeeType === 'percentage' 
            ? 'nullable|numeric|min:0|max:100' 
            : 'nullable|numeric|min:0';
        
        $validator = Validator::make($request->all(), [
            'product_id' => 'nullable|exists:loan_products,id',
            'principal' => 'required|numeric|min:1',
            'term' => 'required|integer|min:1',
            'repayment_schedule' => 'nullable|in:daily,weekly,biweekly,monthly',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'processing_fee_type' => 'nullable|in:percentage,fixed',
            'processing_fee_rate' => $processingFeeRule,
            'penalty_type' => 'nullable|in:none,percentage,fixed',
            'penalty_value' => 'nullable|numeric|min:0',
            'penalty_frequency' => 'nullable|numeric|min:1',
            'first_payment_date' => 'required|date',
            'disbursed_at' => 'nullable|date',
            'purpose' => 'nullable|string|max:500',
            'status' => 'nullable|string|in:pending,approved,rejected,disbursed,active,completed,defaulted',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Calculate processing fee from rate if provided
            $updateData = $request->only([
                'product_id',
                'principal',
                'term',
                'repayment_schedule',
                'interest_rate',
                'application_date',
                'first_payment_date',
                'disbursed_at',
                'purpose',
                'status',
                'notes',
            ]);

            // Update product reference and sync interest_type from product if product changed
            if ($request->filled('product_id') && $request->product_id != $loan->product_id) {
                $newProduct = LoanProduct::find($request->product_id);
                if ($newProduct) {
                    $updateData['interest_type'] = $newProduct->interest_type;
                }
            }

            // Update penalty fields if provided
            if ($request->has('penalty_type')) {
                $updateData['penalty_type']      = $request->input('penalty_type', 'none');
                $updateData['penalty_value']     = $request->input('penalty_value', 0);
                $updateData['penalty_frequency'] = $request->input('penalty_frequency') ?: null;
            }

            // Remove empty date fields to avoid invalid datetime format errors
            if (empty($updateData['disbursed_at'])) {
                unset($updateData['disbursed_at']);
            }
            
            // Calculate processing fee based on type
            if ($request->has('processing_fee_rate')) {
                $processingFeeType = $request->input('processing_fee_type', 'percentage');
                if ($processingFeeType === 'percentage') {
                    // Calculate from percentage
                    $updateData['processing_fee'] = round(($request->principal * $request->processing_fee_rate) / 100, 2);
                } else {
                    // Use fixed amount directly
                    $updateData['processing_fee'] = $request->processing_fee_rate;
                }
                $updateData['processing_fee_type'] = $processingFeeType;
            }
            
            $loan->update($updateData);

            // Recalculate if principal, term, repayment_schedule, interest_rate, product_id, or processing_fee_rate changed
            if ($request->has('principal') || $request->has('term') || $request->has('repayment_schedule') || $request->has('interest_rate') || $request->has('processing_fee_rate') || $request->has('product_id')) {
                $loan->refresh(); // pick up any product_id change just saved
                $rMonthly = $loan->interest_rate / 100;
                $product = $loan->product ?? LoanProduct::find($loan->product_id);
                $interestType = optional($product)->interest_type ?? 'flat';
                $repaymentType = optional($product)->repayment_type ?? 'amortized';
                $repaymentSchedule = $loan->repayment_schedule ?? 'monthly';

                if ($repaymentType === 'interest_only') {
                    // Interest-only: each installment = interest only, balloon principal on last
                    $monthlyPayment = round($loan->principal * $rMonthly, 2);
                    $totalAmount    = round(($monthlyPayment * $loan->term) + $loan->principal, 2);
                } elseif (in_array(strtolower((string) $interestType), ['reducing', 'reducing_balance', 'reducing-balance'], true)) {
                    $ratePerInstallment = $rMonthly;
                    if ($repaymentSchedule === 'daily') {
                        $ratePerInstallment = pow(1 + $rMonthly, 1/30) - 1;
                    } elseif ($repaymentSchedule === 'weekly') {
                        $ratePerInstallment = pow(1 + $rMonthly, 1/4) - 1;
                    } elseif ($repaymentSchedule === 'biweekly') {
                        $ratePerInstallment = pow(1 + $rMonthly, 1/2) - 1;
                    }
                    if ($ratePerInstallment > 0) {
                        $monthlyPayment = round($loan->principal * ($ratePerInstallment * pow(1 + $ratePerInstallment, $loan->term)) / (pow(1 + $ratePerInstallment, $loan->term) - 1), 2);
                    } else {
                        $monthlyPayment = round($loan->principal / $loan->term, 2);
                    }
                    $totalAmount = round($monthlyPayment * $loan->term, 2);
                } else {
                    // Flat interest
                    $termInMonths = $loan->term;
                    if ($repaymentSchedule === 'weekly')   { $termInMonths = $loan->term / 4; }
                    elseif ($repaymentSchedule === 'daily') { $termInMonths = $loan->term / 30; }
                    elseif ($repaymentSchedule === 'biweekly') { $termInMonths = $loan->term / 2; }
                    $totalInterest = round($loan->principal * $rMonthly * $termInMonths, 2);
                    $principalPerPayment = round($loan->principal / $loan->term, 2);
                    $interestPerInstallment = round($totalInterest / $loan->term, 2);
                    $monthlyPayment = $principalPerPayment + $interestPerInstallment;
                    $totalAmount = round($monthlyPayment * $loan->term, 2);
                }

                $loan->update([
                    'monthly_payment' => $monthlyPayment,
                    'total_amount'    => $totalAmount,
                ]);

                // Regenerate schedule
                $loan->schedules()->delete();
                $this->generateLoanSchedule($loan);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Loan updated successfully',
                'data' => $loan->fresh(['client', 'product'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Loan update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update loan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Loan $loan)
    {
        DB::beginTransaction();
        try {
            // Delete related records first
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
            if (method_exists($loan, 'workflowAssignments')) {
                $loan->workflowAssignments()->delete();
            }
            if (method_exists($loan, 'approvals')) {
                $loan->approvals()->delete();
            }

            $loan->delete();

            DB::commit();

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Loan deleted successfully'
                ]);
            }

            return redirect()->route('loans.index')->with('success', 'Loan deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Loan deletion failed', ['error' => $e->getMessage(), 'loan_id' => $loan->id]);
            
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete loan: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to delete loan');
        }
    }

    public function documents(Loan $loan)
    {
        $this->authorize('view', $loan);
        return response()->json([
            'documents' => $loan->documents()->latest()->get(),
        ]);
    }

    public function uploadDocument(Request $request, Loan $loan)
    {
        $this->authorize('update', $loan);

        // Check current attachment count
        $currentCount = $loan->documents()->count();
        $maxAttachments = LoanDocument::MAX_ATTACHMENTS;
        
        if ($currentCount >= $maxAttachments) {
            return response()->json([
                'message' => "Maximum of {$maxAttachments} attachments allowed per loan"
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'files' => ['required', 'array', 'min:1', 'max:' . ($maxAttachments - $currentCount)],
            'files.*' => ['file', 'mimes:pdf,jpeg,jpg,png', 'max:' . LoanDocument::MAX_FILE_SIZE],
            'attachment_types' => ['nullable', 'array'],
            'attachment_types.*' => ['in:loan_contract,spouse_consent,guarantor_form,collateral,other'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $uploaded = [];
        $attachmentTypes = $request->input('attachment_types', []);
        $tenantId = session('tenant_id') ?? $loan->tenant_id;
        
        foreach ($request->file('files', []) as $index => $file) {
            $docType = $attachmentTypes[$index] ?? 'other';
            $path = $file->store('loan-documents/'.$loan->id, 'public');
            $doc = $loan->documents()->create([
                'tenant_id' => $tenantId,
                'document_type' => $docType,
                'uploaded_by' => Auth::id(),
                'original_name' => $file->getClientOriginalName(),
                'file_name' => basename($path),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'path' => $path,
                'description' => $request->input('description'),
            ]);
            $uploaded[] = $doc;
        }

        return response()->json([
            'message' => 'Documents uploaded successfully',
            'documents' => $uploaded,
        ], 201);
    }

    /**
     * Download/view a loan document.
     */
    public function downloadDocument(Loan $loan, $documentId)
    {
        $this->authorize('view', $loan);

        // Load document without global scope to avoid tenant filtering issues
        $document = $loan->documents()->withoutGlobalScopes()->find($documentId);
        
        if (!$document) {
            abort(404, 'Document not found');
        }

        $storage = \Illuminate\Support\Facades\Storage::disk('public');
        
        if (!$storage->exists($document->path)) {
            abort(404, 'File not found');
        }

        return $storage->download($document->path, $document->original_name);
    }

    /**
     * View a loan document inline (for images/PDFs).
     */
    public function viewDocument(Loan $loan, $documentId)
    {
        $this->authorize('view', $loan);

        // Load document without global scope to avoid tenant filtering issues
        $document = $loan->documents()->withoutGlobalScopes()->find($documentId);
        
        if (!$document) {
            abort(404, 'Document not found');
        }

        $storage = \Illuminate\Support\Facades\Storage::disk('public');
        
        if (!$storage->exists($document->path)) {
            abort(404, 'File not found');
        }

        return response($storage->get($document->path))
            ->header('Content-Type', $document->mime_type)
            ->header('Content-Disposition', 'inline; filename="' . $document->original_name . '"');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'group_id' => 'nullable|exists:groups,id',
            'product_id' => 'required|exists:loan_products,id',
            'principal' => 'required|numeric|min:1',
            'term' => 'required|integer|min:1',
            'repayment_schedule' => 'nullable|in:daily,weekly,biweekly,monthly',
            'interest_rate' => 'required|numeric|min:0|max:100',
            'interest_type' => 'nullable|in:flat,reducing,reducing_balance',
            'processing_fee_type' => 'nullable|in:percentage,fixed',
            'processing_fee_rate' => 'nullable|numeric|min:0',
            'penalty_type' => 'nullable|in:none,percentage,fixed',
            'penalty_value' => 'nullable|numeric|min:0',
            'penalty_frequency' => 'nullable|numeric|min:1',
            'application_date' => 'nullable|date',
            'first_payment_date' => 'required|date|after_or_equal:yesterday',
            'purpose' => 'nullable|string|max:500',
            // Collateral validation
            'collateral_required' => 'nullable|boolean',
            'collateral.type' => 'required_if:collateral_required,true|nullable|string|in:land,building,vehicle,machinery,livestock,inventory,equipment,other',
            'collateral.value' => 'nullable|numeric|min:0',
            'collateral.buying_price' => 'nullable|numeric|min:0',
            'collateral.selling_price' => 'nullable|numeric|min:0',
            'collateral.description' => 'nullable|string|max:500',
            // Guarantor validation
            'guarantor_required' => 'nullable|boolean',
            'guarantor.type' => 'required_if:guarantor_required,true|nullable|string|in:spouse,sister,brother,parent,friend,colleague,other',
            'guarantor.name' => 'required_if:guarantor_required,true|nullable|string|max:255',
            'guarantor.phone' => 'required_if:guarantor_required,true|nullable|string|max:20',
            'guarantor.email' => 'nullable|email|max:255',
            'guarantor.street' => 'nullable|string|max:255',
            'guarantor.ward' => 'nullable|string|max:255',
            'guarantor.district' => 'nullable|string|max:255',
            'guarantor.region' => 'nullable|string|max:255',
            // LGA validation
            'lga.officer_name' => 'nullable|string|max:255',
            'lga.position' => 'nullable|string|max:255',
            'lga.phone' => 'nullable|string|max:20',
            'lga.street' => 'nullable|string|max:255',
            'lga.ward' => 'nullable|string|max:255',
            'lga.district' => 'nullable|string|max:255',
            'lga.region' => 'nullable|string|max:255',
            // Attachments validation - max 4 files, 500KB each
            'attachments' => 'nullable|array|max:'.LoanDocument::MAX_ATTACHMENTS,
            'attachments.*' => 'file|mimes:pdf,jpeg,jpg,png|max:'.LoanDocument::MAX_FILE_SIZE,
            'attachment_types' => 'nullable|array',
            'attachment_types.*' => 'in:loan_contract,spouse_consent,guarantor_form,collateral,other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = LoanProduct::find($request->product_id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Loan product not found. Please select a valid loan product and try again.'
            ], 404);
        }

        // Validate amount & term within product bounds
        if ($request->principal < $product->min_amount || $request->principal > $product->max_amount) {
            return response()->json([
                'success' => false,
                'message' => "Loan amount must be between {$product->min_amount} and {$product->max_amount}"
            ], 400);
        }
        
        // Convert term to months for validation based on repayment schedule
        $termInMonths = $request->term;
        $repaymentSchedule = $request->repayment_schedule ?? 'monthly';
        
        if ($repaymentSchedule === 'daily') {
            $termInMonths = $request->term / 30; // 30 days = 1 month
        } elseif ($repaymentSchedule === 'weekly') {
            $termInMonths = $request->term / 4; // 4 weeks = 1 month
        } elseif ($repaymentSchedule === 'biweekly') {
            $termInMonths = $request->term / 2; // 2 biweekly periods = 1 month
        }
        
        // Get appropriate unit name for error message
        $termUnit = 'months';
        if ($repaymentSchedule === 'daily') {
            $termUnit = 'days';
        } elseif ($repaymentSchedule === 'weekly') {
            $termUnit = 'weeks';
        } elseif ($repaymentSchedule === 'biweekly') {
            $termUnit = '14-day periods';
        }
        
        if ($termInMonths < $product->min_term || $termInMonths > $product->max_term) {
            // Convert product limits to the same unit as user input for clearer error message
            $minTermInUserUnit = $product->min_term;
            $maxTermInUserUnit = $product->max_term;
            
            if ($repaymentSchedule === 'daily') {
                $minTermInUserUnit = $product->min_term * 30;
                $maxTermInUserUnit = $product->max_term * 30;
            } elseif ($repaymentSchedule === 'weekly') {
                $minTermInUserUnit = $product->min_term * 4;
                $maxTermInUserUnit = $product->max_term * 4;
            } elseif ($repaymentSchedule === 'biweekly') {
                $minTermInUserUnit = $product->min_term * 2;
                $maxTermInUserUnit = $product->max_term * 2;
            }
            
            return response()->json([
                'success' => false,
                'message' => "Loan term must be between {$minTermInUserUnit} and {$maxTermInUserUnit} {$termUnit}"
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Use product's calculateProcessingFee method which handles both percentage and fixed amount
            $processingFee = $product->calculateProcessingFee($request->principal);
            $rMonthly = $request->interest_rate / 100; // Interest rate is ALWAYS monthly
            $interestType = $request->interest_type ?? $product->interest_type ?? 'flat';
            $repaymentSchedule = $request->repayment_schedule ?? 'monthly';
            $term = $request->term; // Number of installments

            // Convert term to months based on repayment schedule
            $termInMonths = $term;
            if ($repaymentSchedule === 'daily') {
                $termInMonths = $term / 30;
            } elseif ($repaymentSchedule === 'weekly') {
                $termInMonths = $term / 4;
            } elseif ($repaymentSchedule === 'biweekly') {
                $termInMonths = $term / 2; // 2 biweekly periods = 1 month
            }

            $repaymentType = $product->repayment_type ?? 'amortized';

            if ($repaymentType === 'interest_only') {
                // Interest-only: each installment = interest only, principal balloon on last
                $monthlyPayment = round($request->principal * $rMonthly, 2);
                $totalAmount    = round(($monthlyPayment * $term) + $request->principal, 2);
            } elseif (in_array(strtolower((string) $interestType), ['reducing', 'reducing_balance', 'reducing-balance'], true) && $rMonthly > 0) {
                // Reducing balance
                $ratePerInstallment = $rMonthly;
                if ($repaymentSchedule === 'daily') {
                    $ratePerInstallment = pow(1 + $rMonthly, 1/30) - 1;
                } elseif ($repaymentSchedule === 'weekly') {
                    $ratePerInstallment = pow(1 + $rMonthly, 1/4) - 1;
                } elseif ($repaymentSchedule === 'biweekly') {
                    $ratePerInstallment = pow(1 + $rMonthly, 1/2) - 1;
                }
                if ($ratePerInstallment > 0) {
                    $monthlyPayment = round($request->principal * ($ratePerInstallment * pow(1 + $ratePerInstallment, $term)) / (pow(1 + $ratePerInstallment, $term) - 1), 2);
                } else {
                    $monthlyPayment = round($request->principal / $term, 2);
                }
                $totalAmount = round($monthlyPayment * $term, 2);
            } else {
                // Flat interest
                $totalInterest  = round($request->principal * $rMonthly * $termInMonths, 2);
                $totalAmount    = round($request->principal + $totalInterest, 2);
                $monthlyPayment = round($totalAmount / $term, 2);
            }

            // Extract collateral, guarantor and LGA data if provided
            $collateral = $request->input('collateral');
            if (!is_array($collateral)) {
                $collateral = [];
            }
            // Convert empty strings to null for decimal fields to prevent MySQL errors
            foreach (['value', 'buying_price', 'selling_price'] as $field) {
                if (array_key_exists($field, $collateral) && $collateral[$field] === '') {
                    $collateral[$field] = null;
                }
            }
            $guarantor = $request->input('guarantor');
            if (!is_array($guarantor)) {
                $guarantor = [];
            }
            $lga = $request->input('lga');
            if (!is_array($lga)) {
                $lga = [];
            }
            
            // Determine the first approval stage based on tenant's actual staff roles
            $tenantId = session('tenant_id') ?? Auth::user()->tenant_id;
            $firstStage = ApprovalPipelineService::getFirstStage($tenantId);

            $loan = Loan::create([
                'loan_number' => $this->generateLoanNumber(),
                'application_date' => $request->application_date ?? now()->toDateString(),
                'tenant_id' => $tenantId,
                'client_id' => $request->client_id,
                'group_id' => $request->group_id ?: null,
                'product_id' => $request->product_id,
                'user_id' => Auth::id(),
                'principal' => $request->principal,
                'interest_rate' => $request->interest_rate,
                'interest_type' => $request->interest_type ?? $product->interest_type ?? 'flat',
                'term' => $request->term,
                'repayment_schedule' => $request->repayment_schedule,
                'processing_fee' => $processingFee,
                'penalty_type' => $request->input('penalty_type', 'none'),
                'penalty_value' => $request->input('penalty_value', 0),
                'penalty_frequency' => $request->input('penalty_frequency') ?: null,
                'total_amount' => $totalAmount,
                'monthly_payment' => $monthlyPayment,
                'first_payment_date' => $request->first_payment_date,
                'status' => $firstStage ? 'pending' : 'approved',
                'approval_stage' => $firstStage ?? 'approved',
                'approval_stage_status' => $firstStage ? 'pending' : 'approved',
                'collateral_type' => $collateral['type'] ?? null,
                'collateral_value' => $collateral['value'] ?? null,
                'collateral_buying_price' => $collateral['buying_price'] ?? null,
                'collateral_selling_price' => $collateral['selling_price'] ?? null,
                'collateral_description' => $collateral['description'] ?? null,
                'guarantor_required' => $request->boolean('guarantor_required'),
                'guarantor_type' => $guarantor['type'] ?? null,
                'guarantor_name' => $guarantor['name'] ?? null,
                'guarantor_phone' => $guarantor['phone'] ?? null,
                'guarantor_email' => $guarantor['email'] ?? null,
                'guarantor_street' => $guarantor['street'] ?? null,
                'guarantor_ward' => $guarantor['ward'] ?? null,
                'guarantor_district' => $guarantor['district'] ?? null,
                'guarantor_region' => $guarantor['region'] ?? null,
                'lga_officer_name' => $lga['officer_name'] ?? null,
                'lga_position' => $lga['position'] ?? null,
                'lga_phone' => $lga['phone'] ?? null,
                'lga_street' => $lga['street'] ?? null,
                'lga_ward' => $lga['ward'] ?? null,
                'lga_district' => $lga['district'] ?? null,
                'lga_region' => $lga['region'] ?? null,
            ]);

            // Auto-assign client to group pivot if loan has a group
            if ($loan->group_id && $loan->client_id) {
                \App\Models\Group::where('id', $loan->group_id)
                    ->first()
                    ?->clients()
                    ->syncWithoutDetaching([$loan->client_id]);
            }

            $this->generateLoanSchedule($loan);

            // Handle file attachments
            if ($request->hasFile('attachments')) {
                $attachmentTypes = $request->input('attachment_types', []);
                $tenantId = session('tenant_id') ?? $loan->tenant_id;
                
                foreach ($request->file('attachments') as $index => $file) {
                    $docType = $attachmentTypes[$index] ?? 'other';
                    $path = $file->store('loan-documents/' . $loan->id, 'public');
                    
                    $loan->documents()->create([
                        'tenant_id' => $tenantId,
                        'document_type' => $docType,
                        'uploaded_by' => Auth::id(),
                        'original_name' => $file->getClientOriginalName(),
                        'file_name' => basename($path),
                        'mime_type' => $file->getClientMimeType(),
                        'size' => $file->getSize(),
                        'path' => $path,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Loan application created successfully',
                'data' => $loan->load(['client', 'product', 'documents'])
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            $errorRef = (string) Str::uuid();
            $publicErrorMessage = 'Failed to create loan application';

            if ($e instanceof \Illuminate\Database\QueryException) {
                $sqlState = $e->errorInfo[0] ?? null;
                $driverCode = $e->errorInfo[1] ?? null;
                $driverMessage = $e->errorInfo[2] ?? '';

                // Integrity constraint violation (MySQL)
                if ($sqlState === '23000' && (int)$driverCode === 1062) {
                    $publicErrorMessage = 'Loan number conflict. Please try again.';
                } elseif ($sqlState === '42S22' && (int)$driverCode === 1054) {
                    $publicErrorMessage = 'Server database schema is outdated. Please run migrations.';
                } elseif ($sqlState === '42S02' && (int)$driverCode === 1146) {
                    $publicErrorMessage = 'Server database schema is incomplete. Please run migrations.';
                } elseif ((int)$driverCode === 1265 && is_string($driverMessage) && stripos($driverMessage, 'repayment_schedule') !== false) {
                    $publicErrorMessage = 'Repayment schedule is not supported by the server database. Please run migrations.';
                } elseif ((int)$driverCode === 1366 && is_string($driverMessage) && stripos($driverMessage, 'repayment_schedule') !== false) {
                    $publicErrorMessage = 'Repayment schedule is not supported by the server database. Please run migrations.';
                } elseif (is_string($driverMessage) && stripos($driverMessage, 'repayment_schedule') !== false && stripos($driverMessage, 'Data truncated') !== false) {
                    $publicErrorMessage = 'Repayment schedule is not supported by the server database. Please run migrations.';
                } elseif (is_string($driverMessage) && stripos($driverMessage, 'repayment_schedule') !== false && stripos($driverMessage, 'Incorrect') !== false) {
                    $publicErrorMessage = 'Invalid repayment schedule. Please select a valid schedule and try again.';
                } elseif ($sqlState === '23000' && (int)$driverCode === 1452) {
                    $publicErrorMessage = 'Invalid borrower or product selection. Please reselect and try again.';
                } elseif (is_string($driverMessage) && stripos($driverMessage, 'repayment_schedule') !== false) {
                    $publicErrorMessage = 'Invalid repayment schedule. Please select a valid schedule and try again.';
                }
            }

            // Add diagnostic logging for easier troubleshooting
            logger()->error('Loan creation failed (web)', [
                'ref' => $errorRef,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'tenant_id' => session('tenant_id'),
                'request' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $publicErrorMessage . ' (Ref: ' . $errorRef . ')',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        } 
    }

    /**
     * Disburse an approved loan.
     */
    public function disburse(Request $request, Loan $loan)
    {
        try {
            $workflowEngine = app(\App\Services\WorkflowEngine::class);
            $result = $workflowEngine->disburse(
                $loan,
                auth()->user(),
                $request->input('comment'),
                [
                    'method'    => $request->input('disbursement_method'),
                    'reference' => $request->input('disbursement_reference'),
                    'account'   => $request->input('disbursement_account'),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Loan disbursed successfully',
                'data'    => $loan->fresh(['client', 'product']),
            ]);
        } catch (\Throwable $e) {
            logger()->error('Loan disburse failed', [
                'loan_id' => $loan->id,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Approve a loan via workflow.
     */
    public function approve(Request $request, Loan $loan)
    {
        try {
            $workflowEngine = app(\App\Services\WorkflowEngine::class);
            $result = $workflowEngine->approve(
                $loan,
                auth()->user(),
                $request->input('comment')
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Loan approved successfully',
                'data'    => $loan->fresh(['client', 'product']),
            ]);
        } catch (\Throwable $e) {
            logger()->error('Loan approve failed', [
                'loan_id' => $loan->id,
                'error'   => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reject a loan via workflow.
     */
    public function reject(Request $request, Loan $loan)
    {
        try {
            $workflowEngine = app(\App\Services\WorkflowEngine::class);
            $result = $workflowEngine->reject(
                $loan,
                auth()->user(),
                $request->input('comment', 'Rejected')
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Loan rejected successfully',
                'data'    => $loan->fresh(['client', 'product']),
            ]);
        } catch (\Throwable $e) {
            logger()->error('Loan reject failed', [
                'loan_id' => $loan->id,
                'error'   => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Return loan repayment schedule.
     */
    public function schedule(Loan $loan)
    {
        $loan->load('schedules');
        return response()->json([
            'success'  => true,
            'schedule' => $loan->schedules,
        ]);
    }

    private function generateLoanNumber()
    {
        $prefix = 'LN';
        $year = date('Y');
        $month = date('m');

        $lastLoan = Loan::withoutGlobalScopes()
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = 1;
        if ($lastLoan && is_string($lastLoan->loan_number) && preg_match('/(\d{4})$/', $lastLoan->loan_number, $m)) {
            $sequence = ((int) $m[1]) + 1;
        }

        return $prefix . $year . $month . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    private function generateLoanSchedule($loan)
    {
        $schedules = [];
        $paymentDate = Carbon::parse($loan->first_payment_date);
        $tenantId = session('tenant_id') ?? $loan->tenant_id;
        $repaymentSchedule = $loan->repayment_schedule ?? 'monthly';

        $rMonthly = $loan->interest_rate / 100;
        $product = $loan->product ?? LoanProduct::find($loan->product_id);
        $interestType = optional($product)->interest_type ?? 'flat';
        $repaymentType = optional($product)->repayment_type ?? 'amortized';
        $principalBalance = $loan->principal;

        // ---------------------------------------------------------------
        // INTEREST-ONLY schedule: pay interest each period, full principal
        // on the very last installment (balloon payment).
        // ---------------------------------------------------------------
        if ($repaymentType === 'interest_only') {
            $interestPerInstallment = round($loan->principal * $rMonthly, 2);

            for ($i = 1; $i <= $loan->term; $i++) {
                $isLast = ($i === $loan->term);
                $principalAmount = $isLast ? round($loan->principal, 2) : 0.00;
                $interestAmount  = $interestPerInstallment;
                $totalPayment    = round($principalAmount + $interestAmount, 2);
                $balanceAfter    = $isLast ? 0.00 : round($loan->principal, 2);

                $schedules[] = [
                    'tenant_id'          => $tenantId,
                    'loan_id'            => $loan->id,
                    'installment_number' => $i,
                    'due_date'           => $paymentDate->copy(),
                    'principal_amount'   => $principalAmount,
                    'interest_amount'    => $interestAmount,
                    'total_amount'       => $totalPayment,
                    'paid_amount'        => 0,
                    'balance'            => $balanceAfter,
                    'status'             => 'pending',
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];

                switch ($repaymentSchedule) {
                    case 'daily':    $paymentDate->addDay();     break;
                    case 'weekly':   $paymentDate->addWeek();    break;
                    case 'biweekly': $paymentDate->addDays(14); break;
                    default:         $paymentDate->addMonth();   break;
                }
            }

            LoanSchedule::insert($schedules);
            return;
        }

        // ---------------------------------------------------------------
        // STANDARD (amortized) schedule — flat or reducing balance
        // ---------------------------------------------------------------
        $interestPerInstallment = 0;
        if ($interestType === 'flat') {
            $termInMonths = $loan->term;
            if ($repaymentSchedule === 'weekly') {
                $termInMonths = $loan->term / 4;
            } elseif ($repaymentSchedule === 'daily') {
                $termInMonths = $loan->term / 30;
            } elseif ($repaymentSchedule === 'biweekly') {
                $termInMonths = $loan->term / 2;
            }

            $totalInterest = round($loan->principal * $rMonthly * $termInMonths, 2);
            $interestPerInstallment = round($totalInterest / $loan->term, 2);
        }

        for ($i = 1; $i <= $loan->term; $i++) {
            if (in_array(strtolower((string) $interestType), ['reducing', 'reducing_balance', 'reducing-balance'], true)) {
                $ratePerInstallment = $rMonthly;
                if ($repaymentSchedule === 'daily') {
                    $ratePerInstallment = pow(1 + $rMonthly, 1/30) - 1;
                } else if ($repaymentSchedule === 'weekly') {
                    $ratePerInstallment = pow(1 + $rMonthly, 1/4) - 1;
                } else if ($repaymentSchedule === 'biweekly') {
                    $ratePerInstallment = pow(1 + $rMonthly, 1/2) - 1;
                }

                $interestAmount  = round($principalBalance * $ratePerInstallment, 2);
                $principalAmount = round($loan->monthly_payment - $interestAmount, 2);
                $totalPayment    = round($principalAmount + $interestAmount, 2);

                if ($i === $loan->term) {
                    $principalAmount = round($principalBalance, 2);
                    $totalPayment    = round($principalAmount + $interestAmount, 2);
                }

                if ($principalAmount > $principalBalance) {
                    $principalAmount = round($principalBalance, 2);
                    $totalPayment    = round($principalAmount + $interestAmount, 2);
                    $interestAmount  = round($totalPayment - $principalAmount, 2);
                }
            } else {
                $interestAmount  = $interestPerInstallment;
                $principalAmount = round($loan->principal / $loan->term, 2);
                $totalPayment    = round($principalAmount + $interestAmount, 2);
            }

            $principalBalance = max(0, round($principalBalance - $principalAmount, 2));

            $schedules[] = [
                'tenant_id'          => $tenantId,
                'loan_id'            => $loan->id,
                'installment_number' => $i,
                'due_date'           => $paymentDate->copy(),
                'principal_amount'   => $principalAmount,
                'interest_amount'    => $interestAmount,
                'total_amount'       => $totalPayment,
                'paid_amount'        => 0,
                'balance'            => $principalBalance,
                'status'             => 'pending',
                'created_at'         => now(),
                'updated_at'         => now(),
            ];

            switch ($repaymentSchedule) {
                case 'daily':    $paymentDate->addDay();     break;
                case 'weekly':   $paymentDate->addWeek();    break;
                case 'biweekly': $paymentDate->addDays(14); break;
                default:         $paymentDate->addMonth();   break;
            }
        }

        LoanSchedule::insert($schedules);
    }

    /**
     * Show the import loans form.
     */
    public function importForm()
    {
        return view('loans.import');
    }

    /**
     * Process the uploaded Excel/CSV and import loans.
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
            $import = new \App\Imports\LoansImport($tenantId, auth()->id());
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('file'));

            $summary = $import->getSummary();
            
            // Log import results for debugging
            logger()->info('Loan import completed', [
                'tenant_id' => $tenantId,
                'user_id' => auth()->id(),
                'created' => $summary['created'],
                'updated' => $summary['updated'],
                'errors' => $summary['errors'],
            ]);

            $message = sprintf(
                'Import complete: %d created, %d updated%s',
                $summary['created'],
                $summary['updated'],
                count($summary['errors']) ? ", " . count($summary['errors']) . " rows skipped" : ''
            );

            return redirect()->route('loans.index')
                ->with('success', $message)
                ->with('import_errors', $summary['errors']);
        } catch (\Exception $e) {
            logger()->error('Loan import failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withErrors(['import' => 'Import failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Download an Excel template for loan imports.
     */
    public function downloadTemplate()
    {
        $headers = [
            'client_id_number',
            'client_phone',
            'product_name',
            'product_id',
            'principal',
            'term',
            'first_payment_date',
            'loan_number',
            'status',
            'notes',
        ];

        $exampleRow = [
            '12345678',           // client_id_number
            '0712345678',         // client_phone
            'Personal Loan',      // product_name
            '',                   // product_id (optional if name provided)
            '100000',             // principal
            '12',                 // term (months)
            date('Y-m-d', strtotime('+1 month')), // first_payment_date
            '',                   // loan_number (optional, auto-generated)
            'pending',            // status
            'Imported loan',      // notes
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
            'Content-Disposition' => 'attachment; filename="loan_import_template.csv"',
        ]);
    }
}