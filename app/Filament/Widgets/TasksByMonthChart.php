<?php

namespace App\Filament\Widgets;

use App\Repositories\TaskReportRepository;
use Filament\Widgets\ChartWidget;
/* use Filament\Widgets\Concerns\InteractsWithPageTable; */
use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Filament\Resources\Tasks\TaskResource;


class TasksByMonthChart extends ChartWidget
{
    /* use InteractsWithPageTable; */
    protected ?string $heading = 'Monthly Task Activity';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected ?string $maxHeight = '250px';

    protected function getTablePage(): string
    {
        return ListTasks::class;
    }

    protected function getData(): array
    {
        $baseQuery = TaskResource::getEloquentQuery();
        $repository = new TaskReportRepository();
        $data = $repository->getMonthlyStats($baseQuery, now()->year);

        return [
            'datasets' => [
                [
                    'label' => 'Created',
                    'data' => $data->pluck('total_created')->toArray(),
                    'borderColor' => 'rgb(99, 102, 241)',
                    'backgroundColor' => 'rgba(99, 102, 241, 0.5)',
                    'fill' => true,
                ],
                [
                    'label' => 'Completed',
                    'data' => $data->pluck('total_completed')->toArray(),
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                    'fill' => true,
                ],
            ],
            'labels' => $data->pluck('month')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
