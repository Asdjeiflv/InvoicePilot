<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    /**
     * Determine whether the user can view any clients
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view clients
        return true;
    }

    /**
     * Determine whether the user can view the client
     */
    public function view(User $user, Client $client): bool
    {
        // All authenticated users can view clients
        return true;
    }

    /**
     * Determine whether the user can create clients
     */
    public function create(User $user): bool
    {
        // Admin, accounting, and sales can create clients
        return $user->hasAnyRole(['admin', 'accounting', 'sales']);
    }

    /**
     * Determine whether the user can update the client
     */
    public function update(User $user, Client $client): bool
    {
        // Admin, accounting, and sales can update clients
        return $user->hasAnyRole(['admin', 'accounting', 'sales']);
    }

    /**
     * Determine whether the user can delete the client
     */
    public function delete(User $user, Client $client): bool
    {
        // Only admin can delete clients
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the client
     */
    public function restore(User $user, Client $client): bool
    {
        // Only admin can restore clients
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the client
     */
    public function forceDelete(User $user, Client $client): bool
    {
        // Only admin can force delete clients
        return $user->isAdmin();
    }
}
