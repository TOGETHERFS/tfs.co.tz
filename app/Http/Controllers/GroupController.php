<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Branch;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GroupController extends Controller
{
    /**
     * Display a listing of groups.
     */
    public function index(Request $request)
    {
        $query = Group::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $groups = $query->withCount('clients')->latest()->paginate(15);

        return view('groups.index', compact('groups'));
    }

    /**
     * Show the form for creating a new group.
     */
    public function create()
    {
        $tenantId = session('tenant_id');
        $branches = Branch::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $loanOfficers = User::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        return view('groups.create', compact('branches', 'loanOfficers'));
    }

    /**
     * Store a newly created group.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:120',
                Rule::unique('groups')->where(fn ($q) => $q->where('tenant_id', session('tenant_id')))
            ],
            'branch_name' => ['required', 'string', 'max:150'],
            'loan_officer' => ['required', 'string', 'max:150'],
            'meeting_area' => ['required', 'string', 'max:150'],
            'bank_account' => ['nullable', 'string', 'max:120'],
            'region' => ['required', 'string', 'max:120'],
            'ward' => ['required', 'string', 'max:120'],
            'village' => ['required', 'string', 'max:120'],
            'box_number' => ['nullable', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        if (!session()->has('tenant_id')) {
            return back()->withErrors(['tenant' => 'Tenant context not resolved.'])->withInput();
        }

        $group = Group::create([
            'tenant_id' => session('tenant_id'),
            'name' => $validated['name'],
            'branch_name' => $validated['branch_name'],
            'loan_officer' => $validated['loan_officer'],
            'meeting_area' => $validated['meeting_area'],
            'bank_account' => $validated['bank_account'] ?? null,
            'region' => $validated['region'],
            'ward' => $validated['ward'],
            'village' => $validated['village'],
            'box_number' => $validated['box_number'] ?? null,
            'phone' => $validated['phone'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'active',
        ]);

        return redirect()->route('groups.show', $group)->with('success', 'Group created successfully.');
    }

    /**
     * Display a group and manage clients.
     */
    public function show(Group $group)
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;
        abort_if($group->tenant_id !== (int)$tenantId, 403);
        $group->load('clients');
        return view('groups.show', compact('group'));
    }

    /**
     * Attach clients to a group.
     */
    public function attachClients(Request $request, Group $group)
    {
        $tenantId = session('tenant_id') ?? auth()->user()->tenant_id;
        abort_if($group->tenant_id !== (int)$tenantId, 403);

        $validated = $request->validate([
            'client_ids' => ['nullable', 'array'],
            'client_ids.*' => ['integer', 'exists:clients,id'],
        ]);

        // Only allow clients from the same tenant
        $ids = collect($validated['client_ids'] ?? []);
        $validClientIds = $ids->isNotEmpty()
            ? Client::where('tenant_id', $tenantId)->whereIn('id', $ids)->pluck('id')->all()
            : [];

        // sync() replaces the entire set — checked = assigned, unchecked = removed
        $group->clients()->sync($validClientIds);

        return redirect()->route('groups.show', $group)->with('success', 'Clients assigned to group.');
    }
}