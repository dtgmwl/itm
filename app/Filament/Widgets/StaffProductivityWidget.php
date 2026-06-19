<?php

namespace App\Filament\Widgets;

use App\Enums\TaskStatus;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class StaffProductivityWidget extends BaseWidget
{
    protected static ?string $heading = 'Staff Productivity Overview';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->where('is_active', true)
                    ->whereHas('roles', fn($q) => $q->where('name', 'staff'))
                    ->withCount([
                        'tasks',
                        'tasks as tasks_completed_count' => fn($q) => $q->where('status', TaskStatus::Completed->value),
                        'tasks as tasks_overdue_count' => fn($q) => $q
                            ->whereNotIn('status', [TaskStatus::Completed->value, TaskStatus::Cancelled->value])
                            ->whereNotNull('due_date')
                            ->where('due_date', '<', now()),
                    ])
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('department.name')
                    ->badge(),

                Tables\Columns\TextColumn::make('tasks_count')
                    ->label('Total Tasks')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tasks_completed_count')
                    ->label('Completed')
                    ->color('success'),

                Tables\Columns\TextColumn::make('tasks_overdue_count')
                    ->label('Overdue')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('completion_rate')
                    ->label('Rate %')
                    ->state(function ($record) {
                        $total = $record->tasks_count;
                        $completed = $record->tasks_completed_count;
                        return $total > 0 ? round(($completed / $total) * 100, 1) : 0;
                    })
                    ->badge()
                    ->color(fn ($state) => $state >= 80 ? 'success' : ($state >= 50 ? 'warning' : 'danger'))
                    ->formatStateUsing(fn ($state) => $state . '%'),
            ])
            ->defaultSort('name');
    }

    public static function canView(): bool
    {
        return auth()->user()->isHeadDepartment();
    }
}
