<?php

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Spatie\Honeypot\Http\Livewire\Concerns\HoneypotData;
use Spatie\Honeypot\Http\Livewire\Concerns\UsesSpamProtection;

new class extends Component implements HasForms {
    use InteractsWithForms;
    use UsesSpamProtection;

    public ?array $data = [];
    public HoneypotData $extraFields;

    public function mount(): void
    {
        if (Auth::check()) {
            redirect()->intended(filament()->getUrl());
        }

        $this->extraFields = new HoneypotData();
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('email')
                    ->label('Email Address')
                    ->email()
                    ->required()
                    ->autocomplete()
                    ->placeholder('admin@example.com'),

                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required()
                    ->revealable()
                    ->autocomplete('current-password'),

                Checkbox::make('remember')
                    ->label('Ingat Saya'),
            ])
            ->statePath('data');
    }

    public function authenticate()
    {
        $this->protectAgainstSpam();

        $data = $this->form->getState();

        $ipKey = 'admin-login:ip:' . request()->ip();
        $emailKey = 'admin-login:email:' . strtolower($data['email']);

        if (RateLimiter::tooManyAttempts($ipKey, 5)) {
            $seconds = RateLimiter::availableIn($ipKey);
            throw ValidationException::withMessages([
                'data.email' => 'Terlalu banyak percobaan login. Coba lagi ' . $seconds . ' detik lagi.',
            ]);
        }

        if (RateLimiter::tooManyAttempts($emailKey, 5)) {
            throw ValidationException::withMessages([
                'data.email' => 'Akun ini diblokir sementara karena terlalu banyak percobaan gagal.',
            ]);
        }

        if (! Auth::attempt([
            'email' => $data['email'],
            'password' => $data['password'],
        ], $data['remember'] ?? false)) {
            RateLimiter::hit($ipKey, 60);
            RateLimiter::hit($emailKey, 60);
            throw ValidationException::withMessages([
                'data.email' => __('filament-panels::pages/auth/login.messages.failed'),
            ]);
        }

        RateLimiter::clear($ipKey);
        RateLimiter::clear($emailKey);

        session()->regenerate();

        return redirect()->intended(filament()->getUrl());
    }
};

?>

<div class="w-full">
    <form wire:submit="authenticate" class="space-y-6">
        <x-honeypot livewire-model="extraFields" />
        {{ $this->form }}

        <x-filament::button type="submit" size="lg" color="amber" class="w-full shadow-sm">
            Sign In to Dashboard
        </x-filament::button>
    </form>
</div>
