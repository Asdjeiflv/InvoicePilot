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
        // All authenticated users can create clients
        return true;
    }

    /**
     * Determine whether the user can update the client
     */
    public function update(User $user, Client $client): bool
    {
        // All authenticated users can update clients
        return true;
    }

    /**
     * Determine whether the user can delete the client
     */
    public function delete(User $user, Client $client): bool
    {
        // All authenticated users can delete clients
        return true;
    }

    /**
     * Determine whether the user can restore the client
     */
    public function restore(User $user, Client $client): bool
    {
        // All authenticated users can restore clients
        return true;
    }

    /**
     * Determine whether the user can permanently delete the client
     */
    public function forceDelete(User $user, Client $client): bool
    {
        // All authenticated users can force delete clients
        return true;
    }
}
