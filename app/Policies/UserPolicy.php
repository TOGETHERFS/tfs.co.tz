<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can view any users.
     */
    public function viewAny(User $authUser): bool
    {
        return $authUser->isAdmin() || $authUser->hasRole('manager');
    }

    /**
     * Determine if the user can view a specific user.
     */
    public function view(User $authUser, User $user): bool
    {
        // Must be same tenant
        if ($authUser->tenant_id !== $user->tenant_id) {
            return false;
        }

        return $authUser->isAdmin() || $authUser->hasRole('manager') || $authUser->id === $user->id;
    }

    /**
     * Determine if the user can update a specific user.
     * Admins can edit any user in their tenant, including themselves.
     */
    public function update(User $authUser, User $user): bool
    {
        // Must be same tenant
        if ($authUser->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Admins can edit anyone including themselves
        if ($authUser->isAdmin()) {
            return true;
        }

        // Managers can edit staff but not other managers/admins
        if ($authUser->hasRole('manager')) {
            return !$user->isAdmin() && !$user->hasRole('manager');
        }

        // Users can only edit their own profile (limited fields handled in controller)
        return $authUser->id === $user->id;
    }

    /**
     * Determine if the user can delete a specific user.
     */
    public function delete(User $authUser, User $user): bool
    {
        // Must be same tenant
        if ($authUser->tenant_id !== $user->tenant_id) {
            return false;
        }

        // Cannot delete yourself
        if ($authUser->id === $user->id) {
            return false;
        }

        // Only admins can delete users
        return $authUser->isAdmin();
    }
}
