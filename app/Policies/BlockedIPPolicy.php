<?php

namespace App\Policies;

use App\Models\User;
use App\Models\BlockedIP;
use Illuminate\Auth\Access\HandlesAuthorization;

class BlockedIPPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, BlockedIP $blockedIP): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, BlockedIP $blockedIP): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, BlockedIP $blockedIP): bool
    {
        return $user->is_admin;
    }
}