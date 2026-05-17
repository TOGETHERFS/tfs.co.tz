<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\Group;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please login to access your profile.');
        }

        $user->load('groups');
        $groups = Group::where('tenant_id', $user->tenant_id)
            ->orderBy('name')
            ->get();

        return view('profile.edit', [
            'user' => $user,
            'groups' => $groups,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
        ]);

        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return redirect()->route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'old_password' => ['required'],
            'new_password' => ['required', Password::defaults()],
        ]);

        $user = $request->user();

        // Ensure the provided email matches the authenticated user's email
        if (strtolower($validated['email']) !== strtolower($user->email)) {
            return back()->withErrors(['email' => 'Email does not match your account.']);
        }

        // Verify the old password
        if (!Hash::check($validated['old_password'], $user->password)) {
            return back()->withErrors(['old_password' => 'Old password is incorrect.']);
        }

        // Update to the new password
        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return back()->with('status', 'password-updated');
    }

    /**
     * Update the user's group memberships.
     */
    public function updateGroups(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'groups' => ['nullable', 'array'],
            'groups.*' => ['integer', 'exists:groups,id'],
        ]);

        $requestedGroupIds = collect($request->input('groups', []))->map(fn($id) => (int) $id)->all();

        // Only allow groups from the same tenant
        $allowedGroupIds = Group::where('tenant_id', $user->tenant_id)
            ->whereIn('id', $requestedGroupIds)
            ->pluck('id')
            ->all();

        $user->groups()->sync($allowedGroupIds);

        return redirect()->route('profile.edit')->with('status', 'groups-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        // Use global session helper to avoid request-bound issues
        session()->invalidate();
        session()->regenerateToken();

        return redirect('/');
    }
}