<?php

namespace App\Repositories;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class TaskReportRepository
{
    /**
     * Get monthly stats for the current year.
     */
    public function getMonthlyStats(Builder $query, int $year): Collection
    {
        // Using raw SQL for performance and PostgreSQL date functions
        return (clone $query)
            ->reorder()
            ->selectRaw("
                TO_CHAR(created_at, 'Mon') as month,
                EXTRACT(MONTH FROM created_at) as month_num,
                COUNT(*) as total_created,
                COUNT(CASE WHEN status = ? THEN 1 END) as total_completed
            ", [TaskStatus::Completed->value])
            ->whereYear('created_at', $year)
            ->groupByRaw("TO_CHAR(created_at, 'Mon'), EXTRACT(MONTH FROM created_at)")
            ->orderByRaw('EXTRACT(MONTH FROM created_at)')
            ->get();
    }

    public function getStaffProductivity(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->hasRole('staff')
            ->withCount([
                // Langsung hitung semua dari relasi pivot (tasks)
                'tasks as total',
                
                'tasks as completed' => fn($q) => $q->where('status', TaskStatus::Completed->value),
                
                'tasks as overdue' => fn($q) => $q->whereNotIn('status', [TaskStatus::Completed->value, TaskStatus::Cancelled->value])
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', now()),
            ])
            ->get()
            ->map(function ($user) {
                // Hitung rating langsung dari hasil query di atas
                $user->completion_rate = $user->total > 0
                    ? round(($user->completed / $user->total) * 100, 1)
                    : 0;

                return $user;
            });
    }
    public function getDashboardStats(Builder $query): array
    {
        $total = (clone $query)->count();
        $open = (clone $query)->where('status', TaskStatus::Open->value)->count();
        $assigned = (clone $query)->where('status', TaskStatus::Assigned->value)->count();
        $completed = (clone $query)->where('status', TaskStatus::Completed->value)->count();
        $inProgress = (clone $query)->where('status', TaskStatus::InProgress->value)->count();
        $overdue = (clone $query)->whereNotIn('status', [TaskStatus::Completed->value, TaskStatus::Cancelled->value])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->count();

        return [
            'total' => $total,
            'open' => $open,
            'assigned' => $assigned,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'overdue' => $overdue,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        ];
    }
}
