<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    /**
     * Get all clients.
     */
    public function index(Request $request)
    {
        $query = Client::with(['groups']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('id_number', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
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
            'data' => $clients
        ]);
    }

    /**
     * Get a specific client.
     */
    public function show($id)
    {
        $client = Client::with(['loans', 'repayments'])->find($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Borrower not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $client
        ]);
    }

    /**
     * Create a new client.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'required|string|unique:clients,phone',
            'id_number' => 'required|string|unique:clients,id_number',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'required|string|max:100',
            'occupation' => 'nullable|string|max:255',
            'monthly_income' => 'nullable|numeric|min:0',
            'employer' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relationship' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $client = Client::create($request->all());

        // Log the activity
        activity()
            ->causedBy($request->user())
            ->performedOn($client)
            ->log('Borrower created via API');

        return response()->json([
            'success' => true,
            'message' => 'Borrower created successfully',
            'data' => $client
        ], 201);
    }

    /**
     * Update a client.
     */
    public function update(Request $request, $id)
    {
        $client = Client::find($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Borrower not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email,' . $client->id,
            'phone' => 'required|string|unique:clients,phone,' . $client->id,
            'id_number' => 'required|string|unique:clients,id_number,' . $client->id,
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'address' => 'required|string|max:500',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'required|string|max:100',
            'occupation' => 'nullable|string|max:255',
            'monthly_income' => 'nullable|numeric|min:0',
            'employer' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relationship' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $client->update($request->all());

        // Log the activity
        activity()
            ->causedBy($request->user())
            ->performedOn($client)
            ->log('Borrower updated via API');

        return response()->json([
            'success' => true,
            'message' => 'Borrower updated successfully',
            'data' => $client
        ]);
    }

    /**
     * Delete a client.
     */
    public function destroy(Request $request, $id)
    {
        $client = Client::find($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Borrower not found'
            ], 404);
        }

        // Check if client has active loans
        if ($client->loans()->whereIn('status', ['pending', 'approved', 'disbursed', 'active'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete borrower with active loans'
            ], 400);
        }

        // Log the activity before deletion
        activity()
            ->causedBy($request->user())
            ->performedOn($client)
            ->log('Borrower deleted via API');

        $client->delete();

        return response()->json([
            'success' => true,
            'message' => 'Borrower deleted successfully'
        ]);
    }

    /**
     * Get client statistics.
     */
    public function statistics()
    {
        $stats = [
            'total_clients' => Client::count(),
            'active_clients' => Client::where('status', 'active')->count(),
            'inactive_clients' => Client::where('status', 'inactive')->count(),
            'clients_with_loans' => Client::whereHas('loans')->count(),
            'new_clients_this_month' => Client::whereMonth('created_at', now()->month)
                                             ->whereYear('created_at', now()->year)
                                             ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Search clients.
     */
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
        $clients = Client::where(function ($q) use ($query) {
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
            'data' => $clients
        ]);
    }
}