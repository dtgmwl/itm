<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Filament\Widgets\TasksByStatusChart;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            \Filament\Actions\Action::make('calendar')
                ->label('Calendar View')
                ->icon('heroicon-o-calendar')
                ->color('info')
                ->url(fn() => \App\Filament\Pages\Calendar::getUrl()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\TaskStatsOverview::class,
            \App\Filament\Widgets\TasksByStatusChart::class,
        ];
    }

}
