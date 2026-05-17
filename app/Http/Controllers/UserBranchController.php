<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UserBranchController extends Controller
{
    /**
     * Display branch management page for users
     */
    public function index()
    {
        $tenantId = session('tenant_id');
        
        // Get branches for the current tenant
        $branches = Branch::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get();

        return view('user.branches.index', compact('branches'));
    }

    /**
     * Show form to create a new branch
     */
    public function create()
    {
        return view('user.branches.create');
    }

    /**
     * Store a newly created branch
     */
    public function store(Request $request)
    {
        $tenantId = session('tenant_id');
        
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('branches')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })
            ],
            'region' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
        ]);

        $branchData = [
            'name' => $request->name,
            'code' => $request->code,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
            'tenant_id' => $tenantId,
            'is_active' => true,
        ];

        // Only add region and district if columns exist (after migration)
        if (Schema::hasColumn('branches', 'region')) {
            $branchData['region'] = $request->region;
            $branchData['district'] = $request->district;
        }

        Branch::create($branchData);

        return redirect()->route('user.branches.index')
            ->with('success', 'Branch created successfully!');
    }

    /**
     * Update the specified branch
     */
    public function update(Request $request, Branch $branch)
    {
        $tenantId = session('tenant_id');
        
        // Ensure the branch belongs to the current tenant
        if ($branch->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized access to branch.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('branches')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                })->ignore($branch->id)
            ],
            'region' => 'required|string|max:100',
            'district' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_active' => 'boolean',
        ]);

        $branch->update([
            'name' => $request->name,
            'code' => $request->code,
            'region' => $request->region,
            'district' => $request->district,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('user.branches.index')
            ->with('success', 'Branch updated successfully!');
    }

    /**
     * Remove the specified branch
     */
    public function destroy(Branch $branch)
    {
        $tenantId = session('tenant_id');
        
        // Ensure the branch belongs to the current tenant
        if ($branch->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized access to branch.');
        }

        // Check if branch has users assigned
        if ($branch->users()->count() > 0) {
            return redirect()->route('user.branches.index')
                ->with('error', 'Cannot delete branch with assigned staff members. Please reassign staff first.');
        }

        $branch->delete();

        return redirect()->route('user.branches.index')
            ->with('success', 'Branch deleted successfully!');
    }
}