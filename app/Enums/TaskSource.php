<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TaskSource: string implements HasLabel, HasColor, HasIcon
{
    case Routine = 'routine';
    case HodInstruction = 'hod_instruction';
    case ExternalInstruction = 'external_instruction';
    case SelfInitiative = 'self_initiative';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Routine => 'Tugas Rutin',
            self::HodInstruction => 'KABID',
            self::ExternalInstruction => 'Lain / Eksternal',
            self::SelfInitiative => 'Inisiatif Sendiri',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Routine => 'info',
            self::HodInstruction => 'warning',
            self::ExternalInstruction => 'danger',
            self::SelfInitiative => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Routine => 'heroicon-m-calendar-days',
            self::HodInstruction => 'heroicon-m-user',
            self::ExternalInstruction => 'heroicon-m-briefcase',
            self::SelfInitiative => 'heroicon-m-sparkles',
        };
    }
}
