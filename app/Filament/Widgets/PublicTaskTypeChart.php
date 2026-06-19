<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Widgets\ChartWidget;

class PublicTaskTypeChart extends ChartWidget
{
    protected ?string $heading = 'Statistik Pelayanan';
    protected ?string $maxHeight = '400px';
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $tasks = Task::where(function ($q) {
            $q->where('title', 'like', '%[REQUEST]%')
              ->orWhere('title', 'like', '%[GANGGUAN]%');
        })->get();

        $data = [];
        foreach ($tasks as $t) {
            preg_match('/\[(REQUEST|GANGGUAN)\]\s+(.*?)\s*-\s/', $t->title, $m);
            $sub = $m[2] ?? $t->title;
            $data[$sub] = ($data[$sub] ?? 0) + 1;
        }

        $requestSubs = [
            'Fasilitasi Jaringan Intra Pemerintah',
            'Permohonan Pemasangan Wi-Fi Publik',
            'Permohonan Peningkatan Kualitas Jaringan Internet Perangkat Daerah',
            'Permohonan Pembuatan Sub Domain dan Hosting Server',
            'Permohonan Pemanfaatan Data Cloud',
            'Pendampingan Pembuatan/Pengelolaan Aplikasi Perangkat Daerah',
            'Permohonan Pembuatan Alamat Surat Elektronik Pegawai',
            'Permintaan Rekaman CCTV Publik',
            'Permohonan Penayangan Informasi Pada Videotron',
            'Permohonan Fasilitasi Video Conference',
        ];

        $gangguanSubs = [
            'Penanganan Gangguan Wi-Fi Publik',
            'Internet',
            'Hardware',
            'Sistem',
        ];

        $labels = array_merge($requestSubs, $gangguanSubs);

        $requestValues = [];
        foreach ($requestSubs as $sub) {
            $requestValues[] = $data[$sub] ?? 0;
        }
        foreach ($gangguanSubs as $sub) {
            $requestValues[] = 0;
        }

        $gangguanValues = [];
        foreach ($requestSubs as $sub) {
            $gangguanValues[] = 0;
        }
        foreach ($gangguanSubs as $sub) {
            $gangguanValues[] = $data[$sub] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Permohonan',
                    'data' => $requestValues,
                    'backgroundColor' => '#059669',
                    'borderColor' => 'transparent',
                ],
                [
                    'label' => 'Keluhan',
                    'data' => $gangguanValues,
                    'backgroundColor' => '#e11d48',
                    'borderColor' => 'transparent',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => [
                    'ticks' => [
                        'maxRotation' => 45,
                        'minRotation' => 45,
                        'font' => [
                            'size' => 9,
                        ],
                    ],
                ],
                'y' => [
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
