<?php

namespace App\Listeners;

use App\Enums\ActionType;
use App\Events\TaskAssigned;
use App\Events\TaskAttachmentUploaded;
use App\Events\TaskCommentAdded;
use App\Events\TaskCreated;
use App\Events\TaskStatusChanged;
use App\Models\TaskLog;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class LogTaskActivity
{
    public function handle(object $event): void
    {
        match(true) {
            $event instanceof TaskCreated            => $this->logCreated($event),
            $event instanceof TaskStatusChanged        => $this->logStatusChanged($event),
            $event instanceof TaskAssigned           => $this->logAssigned($event),
            $event instanceof TaskCommentAdded       => $this->logCommented($event),
            $event instanceof TaskAttachmentUploaded => $this->logAttachment($event),
            default => null,
        };
    }

    private function baseLog(object $event, ActionType $type, array $extra = []): void
    {
        $userId = match(true) {
            isset($event->actor) && $event->actor   => $event->actor->id,
            isset($event->creator) && $event->creator => $event->creator->id,
            isset($event->user) && $event->user    => $event->user->id,
            default                => auth()->id() ?? User::where('email', 'guest@it.local')->value('id'),
        };

        // Metadata handling: Let Laravel handle the array cast
        $metadata = $extra['metadata'] ?? null;
        unset($extra['metadata']);

        TaskLog::create(array_merge([
            'task_id'     => $event->task->id,
            'user_id'     => $userId,
            'action_type' => $type, // Laravel handles the Enum cast automatically
            'ip_address'  => \Illuminate\Support\Facades\Request::ip(),
            'user_agent'  => \Illuminate\Support\Facades\Request::userAgent(),
            'metadata'    => $metadata,
        ], $extra));
    }

    private function logCreated(TaskCreated $event): void
    {
        $this->baseLog($event, ActionType::Created, [
            'note'            => "Task created",
            'new_status'      => $event->task->status?->value ?? $event->task->status,
            'new_assigned_to' => $event->task->assigned_to,
        ]);
    }

    private function logStatusChanged(TaskStatusChanged $event): void
    {
        $this->baseLog($event, ActionType::StatusChanged, [
            'note'       => $event->note ?? 'Status diperbarui',
            'old_status' => $event->oldStatus?->value ?? $event->oldStatus,
            'new_status' => $event->newStatus?->value ?? $event->newStatus,
        ]);
    }

    private function logAssigned(TaskAssigned $event): void
    {
        $actionType = $event->oldAssigneeId !== null ? ActionType::Reassigned : ActionType::Assigned;

        $this->baseLog($event, $actionType, [
            'note'            => "Tugas diberikan kepada {$event->assignee->name}",
            'old_assigned_to' => $event->oldAssigneeId,
            'new_assigned_to' => $event->assignee->id,
        ]);
    }

    private function logCommented(TaskCommentAdded $event): void
    {
        $this->baseLog($event, ActionType::Commented, [
            'note'     => "Menambahkan komentar baru",
            'metadata' => ['comment_id' => $event->comment->id],
        ]);
    }

    private function logAttachment(TaskAttachmentUploaded $event): void
    {
        $this->baseLog($event, ActionType::AttachmentUploaded, [
            'note'     => "Mengunggah lampiran: {$event->attachment->original_name}",
            'metadata' => ['attachment_id' => $event->attachment->id],
        ]);
    }
}
