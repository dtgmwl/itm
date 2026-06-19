<?php

namespace App\Listeners;

use App\Events\TaskAssigned;
use App\Notifications\TaskAssignedNotification;

class NotifyAssignedStaff
{
    protected static array $sent = [];

    public function handle(TaskAssigned $event): void
    {
        $key = $event->task->id . '_' . ($event->assignee?->id ?? $event->task->assigned_to);

        if (in_array($key, static::$sent)) {
            return;
        }
        static::$sent[] = $key;

        $assignee = $event->assignee ?? $event->task->assignedTo;

        if (!$assignee) return;

        $assignee->notify(new TaskAssignedNotification($event->task));
    }
}
