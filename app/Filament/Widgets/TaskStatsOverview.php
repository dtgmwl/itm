<?php

namespace App\Filament\Widgets;

use App\Repositories\TaskReportRepository;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Filament\Resources\Tasks\TaskResource;

class TaskStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | array | null $columns = 5;

    protected function getStats(): array
    {
        $baseQuery = TaskResource::getEloquentQuery();
        $repository = new TaskReportRepository();
        $stats = $repository->getDashboardStats($baseQuery);

        return [
            Stat::make('Open', $stats['open'])
                ->description('Tasks open')
                ->descriptionIcon('heroicon-m-inbox')
                ->color('gray'),

            Stat::make('Assigned', $stats['assigned'])
                ->description('Tasks assigned')
                ->descriptionIcon('heroicon-m-user')
                ->color('info'),

            Stat::make('In Progress', $stats['in_progress'])
                ->description('Active tasks')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('warning'),

            Stat::make('Overdue', $stats['overdue'])
                ->description('Past due date')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($stats['overdue'] > 0 ? 'danger' : 'success'),

            Stat::make('Completion Rate', "{$stats['completion_rate']}%")
                ->description("{$stats['completed']} tasks completed")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color($stats['completion_rate'] >= 80 ? 'success' : ($stats['completion_rate'] >= 50 ? 'warning' : 'danger')),
        ];
    }
}
