<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\FixedAsset;
use App\Models\Accounting\FixedAssetCategory;
use App\Models\Accounting\AssetDepreciationSchedule;
use App\Models\Accounting\ChartOfAccount;
use App\Models\Branch;
use App\Services\Accounting\AutomatedAccountingService;
use Illuminate\Http\Request;

class FixedAssetController extends Controller
{
    protected AutomatedAccountingService $accountingService;

    public function __construct(AutomatedAccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function index(Request $request)
    {
        $status = $request->get('status');
        $categoryId = $request->get('category_id');

        $query = FixedAsset::with(['category', 'branch'])
            ->orderBy('purchase_date', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $assets = $query->paginate(25);
        $categories = FixedAssetCategory::active()->get();
        $statuses = FixedAsset::getStatuses();

        $totalValue = FixedAsset::where('status', 'active')->sum('current_value');
        $totalAssets = FixedAsset::where('status', 'active')->count();

        return view('accounting.fixed-assets.index', compact('assets', 'categories', 'statuses', 'status', 'categoryId', 'totalValue', 'totalAssets'));
    }

    public function create()
    {
        $categories = FixedAssetCategory::active()->get();
        $branches = Branch::all();
        $depreciationMethods = FixedAssetCategory::getDepreciationMethods();

        return view('accounting.fixed-assets.create', compact('categories', 'branches', 'depreciationMethods'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:fixed_asset_categories,id',
            'asset_name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'serial_number' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:150',
            'purchase_date' => 'required|date',
            'purchase_price' => 'required|numeric|min:0.01',
            'salvage_value' => 'nullable|numeric|min:0',
            'useful_life_years' => 'required|numeric|min:0.5',
            'depreciation_method' => 'required|in:straight_line,declining_balance,none',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $validated['asset_code'] = FixedAsset::generateAssetCode();
        $validated['created_by'] = auth()->id();
        $validated['current_value'] = $validated['purchase_price'];
        $validated['salvage_value'] = $validated['salvage_value'] ?? 0;

        $asset = FixedAsset::create($validated);

        try {
            $this->accountingService->recordAssetPurchase($asset);
        } catch (\Exception $e) {
            // Log error but don't fail the asset creation
        }

        return redirect()->route('accounting.fixed-assets.show', $asset)
            ->with('success', 'Fixed asset registered successfully.');
    }

    public function show(FixedAsset $fixedAsset)
    {
        $fixedAsset->load(['category', 'branch', 'createdBy', 'depreciationSchedules', 'purchaseJournal']);

        return view('accounting.fixed-assets.show', compact('fixedAsset'));
    }

    public function edit(FixedAsset $fixedAsset)
    {
        if ($fixedAsset->status !== 'active') {
            return redirect()->route('accounting.fixed-assets.show', $fixedAsset)
                ->with('error', 'Only active assets can be edited.');
        }

        $categories = FixedAssetCategory::active()->get();
        $branches = Branch::all();
        $depreciationMethods = FixedAssetCategory::getDepreciationMethods();

        return view('accounting.fixed-assets.edit', compact('fixedAsset', 'categories', 'branches', 'depreciationMethods'));
    }

    public function update(Request $request, FixedAsset $fixedAsset)
    {
        if ($fixedAsset->status !== 'active') {
            return redirect()->route('accounting.fixed-assets.show', $fixedAsset)
                ->with('error', 'Only active assets can be edited.');
        }

        $validated = $request->validate([
            'asset_name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'serial_number' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:150',
            'salvage_value' => 'nullable|numeric|min:0',
            'useful_life_years' => 'required|numeric|min:0.5',
            'depreciation_method' => 'required|in:straight_line,declining_balance,none',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $fixedAsset->update($validated);

        return redirect()->route('accounting.fixed-assets.show', $fixedAsset)
            ->with('success', 'Fixed asset updated successfully.');
    }

    public function depreciate(FixedAsset $fixedAsset)
    {
        if ($fixedAsset->status !== 'active') {
            return back()->with('error', 'Only active assets can be depreciated.');
        }

        if ($fixedAsset->depreciation_method === 'none') {
            return back()->with('error', 'This asset is not set for depreciation.');
        }

        $depreciation = $fixedAsset->calculateDepreciation();

        if ($depreciation <= 0) {
            return back()->with('info', 'No depreciation to record at this time.');
        }

        try {
            $this->accountingService->recordDepreciation($fixedAsset, $depreciation);

            AssetDepreciationSchedule::create([
                'tenant_id' => $fixedAsset->tenant_id,
                'asset_id' => $fixedAsset->id,
                'depreciation_date' => now(),
                'depreciation_amount' => $depreciation,
                'accumulated_depreciation' => $fixedAsset->accumulated_depreciation,
                'book_value' => $fixedAsset->current_value,
                'is_posted' => true,
            ]);

            return back()->with('success', 'Depreciation of ' . number_format($depreciation, 2) . ' recorded.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error recording depreciation: ' . $e->getMessage());
        }
    }

    public function runMonthlyDepreciation()
    {
        $assets = FixedAsset::where('status', 'active')
            ->where('depreciation_method', '!=', 'none')
            ->get();

        $processed = 0;
        $errors = 0;

        foreach ($assets as $asset) {
            $depreciation = $asset->calculateDepreciation();
            
            if ($depreciation <= 0) continue;

            try {
                $this->accountingService->recordDepreciation($asset, $depreciation);

                AssetDepreciationSchedule::create([
                    'tenant_id' => $asset->tenant_id,
                    'asset_id' => $asset->id,
                    'depreciation_date' => now(),
                    'depreciation_amount' => $depreciation,
                    'accumulated_depreciation' => $asset->accumulated_depreciation,
                    'book_value' => $asset->current_value,
                    'is_posted' => true,
                ]);

                $processed++;
            } catch (\Exception $e) {
                $errors++;
            }
        }

        return back()->with('success', "Monthly depreciation completed. Processed: {$processed}, Errors: {$errors}");
    }

    public function dispose(Request $request, FixedAsset $fixedAsset)
    {
        $request->validate([
            'disposal_date' => 'required|date',
            'disposal_amount' => 'nullable|numeric|min:0',
            'disposal_notes' => 'nullable|string',
        ]);

        $fixedAsset->update([
            'status' => $request->disposal_amount > 0 ? 'sold' : 'disposed',
            'disposal_date' => $request->disposal_date,
            'disposal_amount' => $request->disposal_amount ?? 0,
            'disposal_notes' => $request->disposal_notes,
        ]);

        return redirect()->route('accounting.fixed-assets.show', $fixedAsset)
            ->with('success', 'Asset disposal recorded.');
    }

    public function categories()
    {
        $categories = FixedAssetCategory::with(['assetAccount', 'depreciationAccount', 'accumulatedDepreciationAccount'])->get();
        $accounts = ChartOfAccount::active()->orderBy('account_code')->get();
        $depreciationMethods = FixedAssetCategory::getDepreciationMethods();

        return view('accounting.fixed-assets.categories', compact('categories', 'accounts', 'depreciationMethods'));
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:fixed_asset_categories,name,NULL,id,tenant_id,' . session('tenant_id'),
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'asset_account_id' => 'nullable|exists:chart_of_accounts,id',
            'depreciation_account_id' => 'nullable|exists:chart_of_accounts,id',
            'accumulated_depreciation_account_id' => 'nullable|exists:chart_of_accounts,id',
            'depreciation_method' => 'required|in:straight_line,declining_balance,none',
            'useful_life_years' => 'required|numeric|min:0.5',
            'salvage_value_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        FixedAssetCategory::create($validated);

        return redirect()->route('accounting.fixed-assets.categories')
            ->with('success', 'Asset category created.');
    }

    public function destroyCategory(FixedAssetCategory $category)
    {
        if ($category->assets()->exists()) {
            return back()->with('error', 'Cannot delete category with existing assets.');
        }

        $category->delete();

        return redirect()->route('accounting.fixed-assets.categories')
            ->with('success', 'Asset category deleted.');
    }
}
