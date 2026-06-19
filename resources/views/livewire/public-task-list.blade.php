<?php

use App\Models\Task;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

new class extends Component implements HasActions, HasSchemas, HasTable {
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function getStats(): array
    {
        $tasks = Task::where(function ($q) {
            $q->where('title', 'like', '%[REQUEST]%')
              ->orWhere('title', 'like', '%[GANGGUAN]%');
        })->get();

        $bySub = [];
        $byTag = ['REQUEST' => 0, 'GANGGUAN' => 0];

        foreach ($tasks as $t) {
            preg_match('/\[(REQUEST|GANGGUAN)\]/', $t->title, $mTag);
            $tag = $mTag[1] ?? 'REQUEST';
            $byTag[$tag]++;

            preg_match('/\[(?:REQUEST|GANGGUAN)\]\s+(.*?)\s*-\s/', $t->title, $m);
            $sub = $m[1] ?? $t->title;
            if (!isset($bySub[$sub])) {
                $bySub[$sub] = ['label' => $sub, 'total' => 0, 'request' => 0, 'gangguan' => 0];
            }
            $bySub[$sub]['total']++;
            $bySub[$sub][strtolower($tag)]++;
        }

        uasort($bySub, fn($a, $b) => $b['total'] <=> $a['total']);

        return [
            'total' => $tasks->count(),
            'byTag' => $byTag,
            'bySub' => array_values($bySub),
            'labels' => array_map(fn($s) => $s['label'], $bySub),
            'requests' => array_map(fn($s) => $s['request'], $bySub),
            'gangguans' => array_map(fn($s) => $s['gangguan'], $bySub),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Task::with(['logs', 'assignees'])->where(function ($q) {
                $q->where('title', 'like', '%[REQUEST]%')
                  ->orWhere('title', 'like', '%[GANGGUAN]%');
            }))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->prefix('#')
                    ->extraAttributes(['class' => 'font-mono text-xs']),

                TextColumn::make('title')
                    ->label('Permohonan / Keluhan')
                    ->limit(30)
                    ->tooltip(fn ($record): string => $this->maskTitle($record->title))
                    ->formatStateUsing(fn (string $state): string => $this->maskTitle($state))
                    ->searchable(),

                TextColumn::make('latest_note')
                    ->label('Progress')
                    ->limit(60)
                    ->tooltip(fn ($record): string => $record->logs->first()?->note ?? '')
                    ->getStateUsing(fn ($record): string => $record->logs->first()?->note ?? '-')
                    ->wrap(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->icon(fn ($state): string => $state->getIcon())
                    ->sortable(),

                TextColumn::make('assignee')
                    ->label('PIC')
                    ->badge()
                    ->color('info')
                    ->getStateUsing(function ($record): string {
                        $names = $record->assignees->pluck('name')->toArray();
                        if (empty($names) && $record->assignedTo) {
                            return $record->assignedTo->name;
                        }
                        return !empty($names) ? implode(', ', $names) : '-';
                    }),

                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->since()
                    ->sortable()
                    ->color('gray')
                    ->extraAttributes(['class' => 'text-xs']),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Belum ada pengajuan')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->searchPlaceholder('Cari layanan atau nama...')
            ->contentGrid([
                'md' => 1,
                'lg' => 1,
            ]);
    }

    private function maskTitle(string $title): string
    {
        // Menyamarkan nomor telepon dan bagian dari nama agar privasi terjaga di publik
        return preg_replace_callback('/(\d{4})\d+(\d{2})/', function ($matches) {
            return $matches[1] . 'xxxx' . $matches[2];
        }, $title);
    }
}; ?>

<div class="mt-4">
    <div class="flex items-center gap-3 mb-6">
        <div class="p-2 bg-indigo-50 rounded-lg text-indigo-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-slate-800 tracking-tight">Daftar Antrian & Progres</h2>
    </div>

    @php $stats = $this->getStats(); @endphp

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 text-center">
            <div class="text-3xl font-bold text-slate-800">{{ $stats['total'] }}</div>
            <div class="text-sm text-slate-500 mt-1">Total Pengajuan</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 text-center">
            <div class="text-3xl font-bold text-emerald-600">{{ $stats['byTag']['REQUEST'] }}</div>
            <div class="text-sm text-slate-500 mt-1">Permohonan</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 text-center">
            <div class="text-3xl font-bold text-rose-600">{{ $stats['byTag']['GANGGUAN'] }}</div>
            <div class="text-sm text-slate-500 mt-1">Gangguan / Komplain</div>
        </div>
    </div>



    <div class="filament-public-table">
        {{ $this->table }}
    </div>
</div>

<style>
    /* Styling agar tabel Filament terlihat lebih menyatu dengan halaman landing */
    .filament-public-table .fi-ta-ctn {
        @apply border-none shadow-none bg-transparent;
    }
    .filament-public-table .fi-ta-content {
        @apply rounded-2xl border border-slate-100 shadow-sm overflow-hidden;
    }
    .filament-public-table .fi-ta-header-cell-label {
        @apply text-xs uppercase tracking-wider font-semibold text-slate-500;
    }
</style>
