<?php
namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TaskStatus: string implements HasLabel, HasColor, HasIcon
{
    case Open       = 'open';
    case Assigned   = 'assigned';
    case InProgress = 'in_progress';
    case Pending    = 'pending';
    case Completed  = 'completed';
    case Cancelled  = 'cancelled';

    public function getLabel(): string
    {
        return match($this) {
            self::Open       => 'Open',
            self::Assigned   => 'Assigned',
            self::InProgress => 'In Progress',
            self::Pending    => 'Pending',
            self::Completed  => 'Completed',
            self::Cancelled  => 'Cancelled',
        };
    }

    public function getColor(): string|array|null
    {
        return match($this) {
            self::Open       => 'gray',
            self::Assigned   => 'info',
            self::InProgress => 'warning',
            self::Pending    => 'orange',
            self::Completed  => 'success',
            self::Cancelled  => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match($this) {
            self::Open       => 'heroicon-o-inbox',
            self::Assigned   => 'heroicon-o-user',
            self::InProgress => 'heroicon-o-arrow-path',
            self::Pending    => 'heroicon-o-clock',
            self::Completed  => 'heroicon-o-check-circle',
            self::Cancelled  => 'heroicon-o-x-circle',
        };
    }

    // Mencegah perubahan status yang ga masuk akal (Domain validation)
    public function allowedTransitions(): array
    {
        return match($this) {
            self::Open       => [self::Assigned, self::Cancelled],
            self::Assigned   => [self::InProgress, self::Pending, self::Cancelled],
            self::InProgress => [self::Pending, self::Completed, self::Cancelled],
            self::Pending    => [self::InProgress, self::Cancelled],
            self::Completed  => [],
            self::Cancelled  => [],
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions());
    }
}
