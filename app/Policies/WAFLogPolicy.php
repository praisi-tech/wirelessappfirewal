<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WAFLog;
use Illuminate\Auth\Access\HandlesAuthorization;

class WAFLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, WAFLog $log): bool
    {
        return $user->is_admin || $log->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false; // Logs are created automatically by system
    }

    public function update(User $user, WAFLog $log): bool
    {
        return false; // Logs should not be updated
    }

    public function delete(User $user, WAFLog $log): bool
    {
        return $user->is_admin;
    }

    public function cleanup(User $user): bool
    {
        return $user->is_admin;
    }

    public function viewStats(User $user): bool
    {
        return $user->is_admin;
    }
}