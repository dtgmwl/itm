<?php

namespace App\Filament\Pages;

use App\Models\Task;
use App\Models\User;
use BackedEnum;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanKegiatan extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static string|null|\UnitEnum $navigationGroup = 'Task Management';
    protected static int|null $navigationSort = 15;

    protected string $view = 'filament.pages.laporan-kegiatan';

    public int $bulan;
    public int $tahun;
    public ?int $userId = null;

    public function mount(): void
    {
        $this->bulan = (int) now()->month;
        $this->tahun = (int) now()->year;
        $this->userId = auth()->id();
    }

    public function canViewAll(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'head_department']);
    }

    public function form(Schema $form): Schema
    {
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = Carbon::create()->month($m)->format('F');
        }
        $years = [];
        for ($y = now()->year; $y >= now()->year - 3; $y--) {
            $years[$y] = (string) $y;
        }

        $schema = [
            Select::make('bulan')
                ->label('Bulan')
                ->default($this->bulan)
                ->options($months)
                ->live()
                ->afterStateUpdated(fn($state) => $this->bulan = (int) $state),
            Select::make('tahun')
                ->label('Tahun')
                ->default($this->tahun)
                ->options($years)
                ->live()
                ->afterStateUpdated(fn($state) => $this->tahun = (int) $state),
        ];

        if ($this->canViewAll()) {
            $schema[] = Select::make('userId')
                ->label('User')
                ->default($this->userId)
                ->options(fn() => User::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->live()
                ->afterStateUpdated(fn($state) => $this->userId = $state ? (int) $state : null);
        }

        return $form->schema($schema)->columns($this->canViewAll() ? 3 : 2);
    }

    public function getDailyReport(): array
    {
        $user = User::find($this->userId);
        if (!$user) {
            return [];
        }

        $start = Carbon::create($this->tahun, $this->bulan, 1);
        $end = $start->copy()->endOfMonth();
        $daysInMonth = $start->daysInMonth;

        $tasks = Task::where('status', 'completed')
            ->whereBetween('completed_at', [$start, $end])
            ->where(function ($q) use ($user) {
                $q->whereHas('assignees', fn($q) => $q->where('user_id', $user->id))
                  ->orWhere('assigned_to', $user->id);
            })
            ->orderBy('completed_at')
            ->get()
            ->groupBy(fn($t) => $t->completed_at->format('Y-m-d'));

        $rows = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = $start->copy()->day($day);
            $key = $date->format('Y-m-d');
            $dayTasks = $tasks[$key] ?? collect();
            $isWeekend = $date->isSaturday() || $date->isSunday();

            $rows[] = [
                'date' => $date->format('d/m/Y'),
                'day_name' => $date->isoFormat('dddd'),
                'tasks' => $isWeekend ? 'Hari Libur' : $dayTasks->map(fn($t) => $t->title)->implode("\n"),
                'is_weekend' => $isWeekend,
            ];
        }

        return $rows;
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn() => $this->getDailyReport())
            ->columns([
                TextColumn::make('index')
                    ->label('No')
                    ->rowIndex(),
                TextColumn::make('date')
                    ->label('Tanggal'),
                TextColumn::make('day_name')
                    ->label('Hari'),
                TextColumn::make('tasks')
                    ->label('Tugas Selesai')
                    ->html()
                    ->wrap()
                    ->formatStateUsing(fn(string $state): string => nl2br(e($state))),
            ])
            ->paginated(false);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action('exportExcel'),
            Action::make('exportPdf')
                ->label('Export PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action('exportPdf'),
        ];
    }

    public function exportExcel(): StreamedResponse
    {
        $rows = $this->getDailyReport();
        $fileName = "laporan-kegiatan-{$this->bulan}-{$this->tahun}.xlsx";

        $headerStyle = (new \OpenSpout\Common\Entity\Style\Style())
            ->setFontName('Inter')
            ->setFontBold()
            ->setFontSize(10)
            ->setBackgroundColor('E5E7EB');

        $bodyStyle = (new \OpenSpout\Common\Entity\Style\Style())
            ->setFontName('Inter')
            ->setFontSize(10);

        return response()->streamDownload(function () use ($rows, $headerStyle, $bodyStyle) {
            $writer = new Writer();
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues(['No', 'Tanggal', 'Hari', 'Tugas Selesai'], $headerStyle));

            foreach ($rows as $i => $row) {
                $writer->addRow(Row::fromValues([
                    $i + 1,
                    $row['date'],
                    $row['day_name'],
                    $row['tasks'],
                ], $bodyStyle));
            }

            $writer->close();
        }, $fileName);
    }

    private function registerDompdfFont(\Dompdf\FontMetrics $fontMetrics, string $family, string $style, string $weight, string $ttfPath): void
    {
        $fontDir = $fontMetrics->getOptions()->getFontDir();
        $fontname = mb_strtolower($family, 'UTF-8');
        $families = $fontMetrics->getFontFamilies();

        $styleString = $fontMetrics->getType("{$weight} {$style}");
        $localFile = $fontname . '_' . $styleString . '_' . md5($ttfPath);
        $localFilePath = $fontDir . '/' . $localFile;

        if (isset($families[$fontname][$styleString])) {
            return;
        }

        if (!file_exists("{$localFilePath}.ufm")) {
            $font = \FontLib\Font::load($ttfPath);
            $font->parse();
            $font->saveAdobeFontMetrics("{$localFilePath}.ufm");
            $font->close();

            if (!file_exists("{$localFilePath}.ttf")) {
                copy($ttfPath, "{$localFilePath}.ttf");
            }
        }

        $entry = $this->normalizeFontEntry($families[$fontname] ?? [], $fontDir);
        $entry[$styleString] = $localFile;
        $fontMetrics->setFontFamily($fontname, $entry);
    }

    private function normalizeFontEntry(array $entry, string $fontDir): array
    {
        return array_map(function (string $path) use ($fontDir) {
            return str_starts_with($path, $fontDir . '/')
                ? substr($path, strlen($fontDir) + 1)
                : $path;
        }, $entry);
    }

    public function exportPdf(): StreamedResponse
    {
        $rows = $this->getDailyReport();
        $month = $this->bulan;
        $year = $this->tahun;
        $user = User::find($this->userId);

        $pdf = Pdf::loadView('filament.exports.laporan-kegiatan-pdf', compact('rows', 'month', 'year', 'user'));
        $dompdf = $pdf->getDomPDF();
        $fontMetrics = $dompdf->getFontMetrics();

        if (file_exists(storage_path('fonts/Inter-Regular.ttf'))) {
            $this->registerDompdfFont(
                $fontMetrics, 'Inter', 'normal', 'normal',
                storage_path('fonts/Inter-Regular.ttf'),
            );
        }
        if (file_exists(storage_path('fonts/Inter-Bold.ttf'))) {
            $this->registerDompdfFont(
                $fontMetrics, 'Inter', 'normal', 'bold',
                storage_path('fonts/Inter-Bold.ttf'),
            );
        }

        return response()->streamDownload(fn() => print($pdf->output()), "laporan-kegiatan-{$month}-{$year}.pdf");
    }
}
