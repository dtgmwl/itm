<?php

namespace App\Filament\Widgets;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Filament\Resources\Tasks\TaskResource;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Support\Collection;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\Filament\Actions\CreateAction;
use Guava\Calendar\Filament\Actions\EditAction;
use Guava\Calendar\Filament\Actions\ViewAction;
use Filament\Schemas\Schema;
use Guava\Calendar\ValueObjects\DateClickInfo;
use Guava\Calendar\ValueObjects\EventClickInfo;
use Illuminate\Database\Eloquent\Model;

class TaskCalendarWidget extends CalendarWidget
{
    protected int | string | array $columnSpan = 'full';

    protected CalendarViewType $calendarView = CalendarViewType::DayGridMonth;

    protected bool $dateClickEnabled = true;

    public function onDateClick(DateClickInfo $info): void
    {
        if (auth()->user()->cannot('create', Task::class)) {
            \Filament\Notifications\Notification::make()
                ->title('Akses Ditolak')
            /* ->body('Hanya Admin atau Kepala Departemen yang dapat membuat tugas baru.') */
                ->body('Tidak dapat membuat task.')
                ->danger()
                ->send();
            return;
        }

        $this->redirect(TaskResource::getUrl('create', [
            'due_date' => $info->date->toDateString(),
        ]));
    }

    protected function onEventClick(EventClickInfo $info, Model $event, ?string $action = null): void
    {
        $this->redirect(TaskResource::getUrl('view', ['record' => $event]));
    }

    protected function getEvents(FetchInfo $info): Collection | array
    {
        $query = Task::query()->with('assignees');
        $user = auth()->user();

        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            // Admin can see everything
        } elseif (method_exists($user, 'isHeadDepartment') && $user->isHeadDepartment()) {
            $query->where(function ($q) use ($user) {
                $q->where('department_id', $user->department_id)
                  ->orWhere('status', TaskStatus::Open->value);
            });
        } else {
            // Staff: check both legacy assigned_to and new pivot table
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhereHas('assignees', fn($q2) => $q2->where('users.id', $user->id));
            });
        }

        return $query
            ->where(function ($query) use ($info) {
                $query->whereBetween('start_date', [$info->start, $info->end])
                    ->orWhereBetween('due_date', [$info->start, $info->end])
                    ->orWhere(function ($q) use ($info) {
                        $q->where('status', TaskStatus::Open->value)
                          ->whereBetween('created_at', [$info->start, $info->end]);
                    })
                    ->orWhere(function ($q) use ($info) {
                        $q->where('status', TaskStatus::Completed)
                          ->whereBetween('completed_at', [$info->start, $info->end]);
                    });
            })
            ->get()
            ->map(fn (Task $task) => $task->toCalendarEvent());
    }

    protected function headerActions(): array
    {
        return [
            CreateAction::make('createTask')
                ->model(Task::class)
                ->form(fn (Schema $schema) => TaskResource::form($schema))
                ->fillForm(fn (array $arguments) => [
                    'due_date' => $arguments['due_date'] ?? null,
                ]),
        ];
    }

    protected function eventActions(): array
    {
        return [
            ViewAction::make()
                ->url(fn (Task $record) => TaskResource::getUrl('view', ['record' => $record])),
            EditAction::make()
                ->form(fn (Schema $schema) => TaskResource::form($schema)),
        ];
    }

    protected bool $eventClickEnabled = true;
    protected ?string $defaultEventClickAction = 'view';
}
