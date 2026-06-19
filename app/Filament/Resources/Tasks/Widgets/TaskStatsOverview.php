<?php

namespace App\Filament\Resources\Tasks\Widgets;

use App\Enums\TaskStatus;
use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TaskStatsOverview extends StatsOverviewWidget
{
    // Bikin widget ini muncul paling atas
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Pending Tasks', Task::whereIn('status', [TaskStatus::Open->value, TaskStatus::Pending->value])->count())
                ->description('Needs attention')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('warning'),

            Stat::make('Completed This Month', Task::where('status', TaskStatus::Completed->value)->whereMonth('completed_at', now()->month)->count())
                ->description('Good progress!')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('success'),

            Stat::make('Overdue Tasks', Task::overdue()->count())
                ->description('Past due date')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}
