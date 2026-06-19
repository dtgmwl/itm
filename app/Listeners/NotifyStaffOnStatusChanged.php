<?php

namespace App\Listeners;

use App\Events\TaskStatusChanged;
use App\Enums\TaskStatus;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Support\Facades\Log;

class NotifyStaffOnStatusChanged
{
    public function handle(TaskStatusChanged $event): void
    {
        // Jika status berubah menjadi Assigned (misal dari Open), 
        // maka semua assignee yang ada saat ini perlu dapet notifikasi.
        if ($event->newStatus === TaskStatus::Assigned) {
            $task = $event->task;
            
            // Kita load assignees kalau belum
            $task->loadMissing('assignees');

            foreach ($task->assignees as $assignee) {
                Log::info("Status changed to Assigned. Notifying {$assignee->name} for task #{$task->id}");
                $assignee->notify(new TaskAssignedNotification($task));
            }
        }
    }
}
