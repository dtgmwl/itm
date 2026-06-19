<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\TaskCalendarWidget;
use BackedEnum;

class Calendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';
    protected static string|null|\UnitEnum $navigationGroup = 'Task Management';
    protected static int|null $navigationSort = 10;

    protected string $view = 'filament.pages.calendar';

    protected function getHeaderWidgets(): array
    {
        return [
            TaskCalendarWidget::class,
        ];
    }
}
