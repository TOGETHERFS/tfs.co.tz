<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sms\SmsPackage;
use App\Models\SmsPurchase;
use Illuminate\Http\Request;

class SmsPackageController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super_admin');
    }

    /**
     * Display SMS packages and purchases overview.
     */
    public function index()
    {
        $packages = SmsPackage::ordered()->get();
        $purchases = SmsPurchase::with(['user', 'tenant'])
            ->orderByDesc('created_at')
            ->paginate(15);
        
        return view('admin.sms.packages.index', compact('packages', 'purchases'));
    }

    /**
     * Show form to create a new package.
     */
    public function create()
    {
        return view('admin.sms.packages.create');
    }

    /**
     * Store a new SMS package.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'sms_count' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        SmsPackage::create($validated);

        return redirect()->route('admin.sms.packages.index')
            ->with('status', 'SMS package created successfully.');
    }

    /**
     * Show form to edit a package.
     */
    public function edit(SmsPackage $package)
    {
        return view('admin.sms.packages.edit', compact('package'));
    }

    /**
     * Update an SMS package.
     */
    public function update(Request $request, SmsPackage $package)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'sms_count' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'sort_order' => 'nullable|integer',
            'is_active' => 'nullable',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $package->update($validated);

        return redirect()->route('admin.sms.packages.index')
            ->with('status', 'SMS package updated successfully.');
    }

    /**
     * Delete an SMS package.
     */
    public function destroy(SmsPackage $package)
    {
        $package->delete();

        return redirect()->route('admin.sms.packages.index')
            ->with('status', 'SMS package deleted.');
    }
}
