<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Services\TaskService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    protected function getRedirectUrl(): string
    {
        return \App\Filament\Pages\Calendar::getUrl();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if ($user->isStaff()) {
            $data['assigned_by'] = $user->id;
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $data['assigned_by'] ??= auth()->id();
        $data['assignees'] = $this->data['assignees'] ?? [];

        $service = app(TaskService::class);
        return $service->createTask(\App\DTOs\CreateTaskDTO::fromArray($data));
    }
}
