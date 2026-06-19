<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepartmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('departments.manage') || $user->hasRole('admin');
    }

    public function view(User $user, Department $department): bool
    {
        return $user->hasPermissionTo('departments.manage') || $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('departments.manage') || $user->hasRole('admin');
    }

    public function update(User $user, Department $department): bool
    {
        return $user->hasPermissionTo('departments.manage') || $user->hasRole('admin');
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->hasPermissionTo('departments.manage') || $user->hasRole('admin');
    }
}
