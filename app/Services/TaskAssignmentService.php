<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TaskAssignmentService
{
    /**
     * Get staff members eligible for assignment, optionally filtered by department.
     * Ordered by current active workload (least busy first).
     */
    public function getEligibleAssignees(?int $departmentId = null): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->hasRole('staff')
            ->when($departmentId, fn($q) => $q->where('department_id', $departmentId))
            ->withCount(['assignedTasks' => fn($q) => $q->whereNotIn('status', ['completed', 'cancelled'])])
            ->orderBy('assigned_tasks_count')
            ->get();
    }

    /**
     * Get a detailed workload breakdown for a specific user.
     */
    public function getStaffWorkload(User $user): array
    {
        $tasks = $user->assignedTasks()
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->get();

        return [
            'total'       => $tasks->count(),
            'overdue'     => $tasks->filter->isOverdue()->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'pending'     => $tasks->where('status', 'open')->count(),
        ];
    }
}
