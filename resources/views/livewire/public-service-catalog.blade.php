<?php

use App\DTOs\CreateTaskDTO;
use App\Enums\TaskPriority;
use App\Services\TaskService;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Component;
use Spatie\Honeypot\Http\Livewire\Concerns\HoneypotData;
use Spatie\Honeypot\Http\Livewire\Concerns\UsesSpamProtection;

new class extends Component implements HasForms {
    use InteractsWithForms;
    use UsesSpamProtection;

    public ?array $data = [];
    public HoneypotData $extraFields;
    public string $submitToken = '';

    public function mount(): void
    {
        $this->extraFields = new HoneypotData();
        $this->refreshToken();
        $this->form->fill();
    }

    public function refreshToken(): void
    {
        $this->submitToken = Str::random(32);
        session()->put('submit_token', $this->submitToken);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Radio::make('jenis_tiket')
                    ->label('Kategori Tiket')
                    ->id('jenis_tiket')
                    ->options([
                        'layanan' => 'Pengajuan Layanan IT',
                        'komplain' => 'Komplain / Lapor Gangguan',
                    ])
                    ->default('layanan')
                    ->inline()
                    ->required()
                    ->live(),

                Select::make('layanan')
                    ->label('Katalog Layanan')
                    ->id('layanan')
                    ->options([
                        'Infrastruktur & Jaringan' => [
                            'Fasilitasi Jaringan Intra Pemerintah' => 'Fasilitasi Jaringan Intra Pemerintah',
                            'Permohonan Pemasangan Wi-Fi Publik' => 'Permohonan Pemasangan Wi-Fi Publik',
                            'Permohonan Peningkatan Kualitas Jaringan Internet Perangkat Daerah' => 'Permohonan Peningkatan Kualitas Jaringan Internet Perangkat Daerah',
                        ],

                        'Hosting & Cloud' => [
                            'Permohonan Pembuatan Sub Domain dan Hosting Server' => 'Permohonan Pembuatan Sub Domain dan Hosting Server',
                            'Permohonan Pemanfaatan Data Cloud' => 'Permohonan Pemanfaatan Data Cloud',
                        ],

                        'Aplikasi & Email' => [
                            'Pendampingan Pembuatan/Pengelolaan Aplikasi Perangkat Daerah' => 'Pendampingan Pembuatan/Pengelolaan Aplikasi Perangkat Daerah',
                            'Permohonan Pembuatan Alamat Surat Elektronik Pegawai' => 'Permohonan Pembuatan Alamat Surat Elektronik Pegawai',
                        ],

                        'Multimedia & Publikasi' => [
                            'Permintaan Rekaman CCTV Publik' => 'Permintaan Rekaman CCTV Publik',
                            'Permohonan Penayangan Informasi Pada Videotron' => 'Permohonan Penayangan Informasi Pada Videotron',
                            'Permohonan Fasilitasi Video Conference' => 'Permohonan Fasilitasi Video Conference (Zoom Meetings)',
                        ],
                    ])
                    ->required(fn (Get $get) => $get('jenis_tiket') === 'layanan')
                    ->hidden(fn (Get $get) => $get('jenis_tiket') !== 'layanan')
                    ->searchable(),

                Select::make('komplain')
                    ->label('Jenis Gangguan')
                    ->id('komplain')
                    ->options([
                        'Infrastruktur & Jaringan' => [
                            'Penanganan Gangguan Wi-Fi Publik' => 'Penanganan Gangguan Wi-Fi Publik',
                            'Internet (Putus / Lambat)' => 'Internet (Putus / Lambat)',
                        ],
                        'Sistem & Hardware' => [
                            'Hardware' => 'Hardware Rusak',
                            'Sistem' => 'Aplikasi Eror',
                        ],
                    ])
                    ->required(fn (Get $get) => $get('jenis_tiket') === 'komplain')
                    ->hidden(fn (Get $get) => $get('jenis_tiket') !== 'komplain')
                    ->searchable(),

                TextInput::make('nama_pelapor')
                    ->label('Nama, Instansi & Kontak')
                    ->id('nama_pelapor')
                    ->placeholder('Contoh: Doni - Kecamatan Nusawungu - 0895XXXXXXX ')
                    ->required(),

                Textarea::make('deskripsi')
                    ->label('Detail Deskripsi')
                    ->id('deskripsi')
                    ->placeholder('Jelaskan secara rinci kebutuhan atau masalah IT Anda...')
                    ->rows(4)
                    ->required(),
            ])
            ->statePath('data');
    }

    public function submit()
    {
        $this->protectAgainstSpam();

        $sessionKey = 'service-catalog:session:' . session()->getId();
        $ipKey = 'service-catalog:ip:' . request()->ip();

        if (RateLimiter::tooManyAttempts($sessionKey, 10)) {
            $seconds = RateLimiter::availableIn($sessionKey);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data.jenis_tiket' => 'Terlalu banyak pengajuan. Coba lagi ' . $seconds . ' detik lagi.',
            ]);
        }

        if (RateLimiter::tooManyAttempts($ipKey, 100)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data.jenis_tiket' => 'Terlalu banyak pengajuan dari jaringan ini. Coba lagi nanti.',
            ]);
        }

        if (session()->pull('submit_token') !== $this->submitToken) {
            abort(422, 'Form expired, silakan reload.');
        }

        $data = $this->form->getState();
        $isKomplain = $data['jenis_tiket'] === 'komplain';
        $subKategori = $isKomplain ? $data['komplain'] : $data['layanan'];

        $dto = CreateTaskDTO::fromArray([
            'title' => sprintf('[%s] %s - %s',
                $isKomplain ? 'GANGGUAN' : 'REQUEST',
                $subKategori,
                $data['nama_pelapor']
            ),
            'description' => $data['deskripsi'],
            'priority' => $isKomplain ? TaskPriority::High : TaskPriority::Medium,
            'assigned_by' => \App\Models\User::where('email', 'guest@it.local')->value('id'),
            'task_source' => \App\Enums\TaskSource::ExternalInstruction,
            'assignees' => [],
        ]);

        app(TaskService::class)->createTask($dto);

        RateLimiter::hit($sessionKey, 3600);
        RateLimiter::hit($ipKey, 3600);
        $this->refreshToken();
        $this->form->fill();

        Notification::make()
            ->title('Tiket Berhasil Dikirim')
            ->body('Permintaan Anda telah kami terima dan akan segera diproses.')
            ->success()
            ->send();
    }
};

?>

<div>
    <form wire:submit="submit" class="space-y-6">
        <x-honeypot livewire-model="extraFields" />
        {{ $this->form }}

        <div class="flex justify-end">
            <button type="submit"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-xl shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                </svg>
                Kirim Tiket
            </button>
        </div>
    </form>
</div>
