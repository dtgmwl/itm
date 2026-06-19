<?php

namespace App\Filament\Widgets;
use App\Models\Task;
use App\Enums\TaskStatus;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;
/* use Filament\Widgets\Concerns\InteractsWithPageTable; */
use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Filament\Resources\Tasks\TaskResource;

class TasksByStatusChart extends ChartWidget
{
    /* use InteractsWithPageTable; */
    protected ?string $heading = 'Status Tugas';

    protected ?string $maxHeight = '250px';

    protected int | string | array $columnSpan = 'full';

    protected function getTablePage(): string
    {
        return ListTasks::class;
    }

    protected function getData(): array
    {
        $baseQuery = TaskResource::getEloquentQuery();
        $data = (clone $baseQuery)
            ->reorder()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // 2. Siapin label dan warna berdasarkan Enum TaskStatus
        $labels = [];
        $counts = [];
        $colors = [];

        foreach (TaskStatus::cases() as $status) {
            $labels[] = $status->getLabel();
            $counts[] = $data[$status->value] ?? 0;

            // Map warna Filament ke Kode Hex (Chart.js butuh hex/rgb)
            $colors[] = match ($status->getColor()) {
                'gray'    => '#94a3b8',
                'info'    => '#3b82f6',
                'warning' => '#f59e0b',
                'orange'  => '#ea580c',
                'success' => '#10b981',
                'danger'  => '#ef4444',
                default   => '#cbd5e1',
            };
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Tugas',
                    'data' => $counts,
                    'backgroundColor' => $colors,
                    'borderColor' => 'transparent',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
