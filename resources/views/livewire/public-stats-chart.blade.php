<?php

use App\Models\Task;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

new class extends Component implements HasActions, HasSchemas, HasTable {
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function getChartData(): array
    {
        $tasks = Task::where(function ($q) {
            $q->where('title', 'like', '%[REQUEST]%')
              ->orWhere('title', 'like', '%[GANGGUAN]%');
        })->get();

        $data = [];
        foreach ($tasks as $t) {
            preg_match('/\[(REQUEST|GANGGUAN)\]\s+(.*?)\s*-\s/', $t->title, $m);
            $sub = $m[2] ?? $t->title;
            $tag = $m[1] ?? 'REQUEST';
            $data[$sub] ??= ['label' => $sub, 'request' => 0, 'gangguan' => 0, 'count' => 0];
            $data[$sub][strtolower($tag)]++;
            $data[$sub]['count']++;
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

        $items = [];
        foreach ($requestSubs as $sub) {
            $items[] = [
                'label' => $sub,
                'count' => $data[$sub]['count'] ?? 0,
                'request' => $data[$sub]['request'] ?? 0,
                'gangguan' => $data[$sub]['gangguan'] ?? 0,
                'type' => 'request',
            ];
        }
        foreach ($gangguanSubs as $sub) {
            $items[] = [
                'label' => $sub,
                'count' => $data[$sub]['count'] ?? 0,
                'request' => $data[$sub]['request'] ?? 0,
                'gangguan' => $data[$sub]['gangguan'] ?? 0,
                'type' => 'gangguan',
            ];
        }

        $maxCount = max(array_map(fn($i) => $i['count'], $items)) ?: 1;

        return [
            'items' => $items,
            'maxCount' => $maxCount,
            'totalRequest' => array_sum(array_map(fn($i) => $i['type'] === 'request' ? $i['count'] : 0, $items)),
            'totalGangguan' => array_sum(array_map(fn($i) => $i['type'] === 'gangguan' ? $i['count'] : 0, $items)),
        ];
    }

    public function table(Table $table): Table
    {
        $chart = $this->getChartData();

        return $table
            ->records(fn() => collect($chart['items'])->map(fn($i) => $i))
            ->columns([
                TextColumn::make('type')
                    ->label('')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => $state === 'request' ? 'R' : 'G')
                    ->color(fn(string $state): string => $state === 'request' ? 'success' : 'danger')
                    ->grow(false),
                TextColumn::make('label')
                    ->label('Subkategori')
                    ->wrap(),
                TextColumn::make('count')
                    ->label('Jumlah')
                    ->numeric()
                    ->sortable(),
            ])
            ->paginated(false)
            ->searchable(false)
            ->filters([])
            ->bulkActions([])
            ->heading('Permohonan vs Gangguan');
    }
}; ?>

{{ $this->table }}
