<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Enums\TaskStatus;
use App\Events\TaskAssigned;
use App\Filament\Resources\Tasks\TaskResource;
use App\Models\User;
use App\Services\TaskService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected array $oldAssigneeIds = [];

    protected function beforeSave(): void
    {
        $this->oldAssigneeIds = $this->record->assignees()->pluck('users.id')->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            TaskResource::getUpdateProgressAction(),
            ViewAction::make(),
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $user = auth()->user();

        $newAssigneeIds = $this->data['assignees'] ?? [];

        if (!empty($newAssigneeIds) && $record->status == TaskStatus::Open) {
            $data['status'] = TaskStatus::Assigned->value;
        }

        if (!empty($newAssigneeIds) && $user && $user->id !== 1) {
            $data['assigned_by'] = $user->id;
        }

        if (isset($data['status'])) {
            $newStatus = ($data['status'] instanceof TaskStatus)
                ? $data['status']
                : TaskStatus::tryFrom($data['status']);

            if ($newStatus !== null && $record->status !== $newStatus) {
                $service = app(TaskService::class);
                $service->updateStatus($record, $newStatus, $user, 'Status updated via panel');
                unset($data['status']);
            }
        }

        $record->update($data);

        $newlyAssigned = array_unique(array_diff($newAssigneeIds, $this->oldAssigneeIds));
        $removed = array_unique(array_diff($this->oldAssigneeIds, $newAssigneeIds));

        foreach ($newlyAssigned as $id) {
            $staff = User::find($id);
            if ($staff && $user) {
                $oldId = count($newlyAssigned) === 1 && count($removed) === 1
                    ? reset($removed)
                    : null;
                \Illuminate\Support\Facades\Log::info("DISPATCH TaskAssigned from EditTask::handleRecordUpdate for user {$id} task {$record->id}, oldId: " . ($oldId ?? 'null'));
                event(new TaskAssigned($record->fresh(), $user, $staff, $oldId));
            }
        }

        return $record;
    }
}