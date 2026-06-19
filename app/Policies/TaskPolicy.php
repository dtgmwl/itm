<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // All active, authenticated users can list tasks
    }

    public function view(User $user, Task $task): bool
    {
        // Admin & Head can view all; staff only sees their own
        return $user->hasRole('admin')
            || $user->isHeadDepartment()
            || $task->assigned_to === $user->id
            || $task->assignees()->where('users.id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'head_department', 'staff']);
    }

    public function update(User $user, Task $task): bool
    {
        if ($task->isTerminal()) return false;

        return $user->hasRole('admin') || $user->isHeadDepartment();
    }

    public function delete(User $user, Task $task): bool
    {
        return ($user->hasRole('admin') || $user->isHeadDepartment()) && !$task->isTerminal();
    }

    public function assign(User $user, Task $task): bool
    {
        return ($user->hasRole('admin') || $user->isHeadDepartment()) && !$task->isTerminal();
    }

    public function cancel(User $user, Task $task): bool
    {
        return ($user->hasRole('admin') || $user->isHeadDepartment()) && !$task->isTerminal();
    }

    public function updateStatus(User $user, Task $task): bool
    {
        if ($task->isTerminal()) return false;

        return $user->hasRole('admin')
            || $user->isHeadDepartment()
            || $task->assigned_to === $user->id
            || $task->assignees()->where('users.id', $user->id)->exists();
    }

    public function addComment(User $user, Task $task): bool
    {
        return $this->view($user, $task);
    }

    public function uploadAttachment(User $user, Task $task): bool
    {
        return $this->view($user, $task) && !$task->isTerminal();
    }
}
