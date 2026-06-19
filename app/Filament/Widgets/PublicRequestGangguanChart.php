<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Widgets\ChartWidget;

class PublicRequestGangguanChart extends ChartWidget
{
    protected ?string $heading = 'Permohonan : Keluhan';
    protected ?string $maxHeight = '250px';
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $requestCount = Task::where('title', 'like', '%[REQUEST]%')->count();
        $gangguanCount = Task::where('title', 'like', '%[GANGGUAN]%')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah',
                    'data' => [$requestCount, $gangguanCount],
                    'backgroundColor' => ['#059669', '#e11d48'],
                    'borderColor' => 'transparent',
                ],
            ],
            'labels' => ['Permohonan', 'Keluhan'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
