<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TaskPriority: string implements HasLabel, HasColor
{
    case Low      = 'low';
    case Medium   = 'medium';
    case High     = 'high';
    case Critical = 'critical';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string|array|null
    {
        return match($this) {
            self::Low      => 'gray',
            self::Medium   => 'info',
            self::High     => 'warning',
            self::Critical => 'danger',
        };
    }
}
