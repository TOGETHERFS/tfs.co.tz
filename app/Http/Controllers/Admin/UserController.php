<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of all users (system-wide).
     */
    public function index(Request $request)
    {
        $this->authorizeAdmin();

        $query = User::with('tenant');

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('tenant_id')->orderByDesc('created_at')->get();

        // Group users by tenant
        $groupedUsers = $users->groupBy('tenant_id');

        return view('admin.users.index', compact('groupedUsers'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $this->authorizeAdmin();
        return response('Create user UI not implemented yet.', 501);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $this->authorizeAdmin();
        return response('Store user logic not implemented yet.', 501);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $this->authorizeAdmin();
        return response('Show user UI not implemented yet.', 501);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $this->authorizeAdmin();
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'role' => 'required|string|max:50',
            'password' => 'nullable|string|min:8',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->role = $validated['role'];
        
        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }
        
        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        $this->authorizeAdmin();

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    /**
     * Ban/Suspend a user.
     */
    public function ban(User $user)
    {
        $this->authorizeAdmin();

        // Prevent self-ban
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot ban your own account.');
        }

        $user->is_banned = true;
        $user->banned_at = now();
        $user->save();

        return back()->with('success', 'User has been banned.');
    }

    /**
     * Unban/Reactivate a user.
     */
    public function unban(User $user)
    {
        $this->authorizeAdmin();

        $user->is_banned = false;
        $user->banned_at = null;
        $user->save();

        return back()->with('success', 'User has been unbanned.');
    }

    private function authorizeAdmin(): void
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }
    }
}