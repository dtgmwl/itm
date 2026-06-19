<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('users.manage') || $user->hasRole('admin');
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo('users.manage') || $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('users.manage') || $user->hasRole('admin');
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo('users.manage') || $user->hasRole('admin');
    }

    public function delete(User $user, User $model): bool
    {
        // Prevent self-deletion
        return ($user->hasPermissionTo('users.manage') || $user->hasRole('admin')) && $user->id !== $model->id;
    }
}
