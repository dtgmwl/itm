<?php

namespace App\Listeners;

use App\Events\TaskCompleted;
use App\Models\User;
use App\Notifications\TaskCompletedNotification;
use Illuminate\Support\Facades\Log;

class NotifyHeadOnTaskComplete
{
    public function handle(TaskCompleted $event): void
    {
        $task = $event->task;
        
        // Cek siapa yang buat task, kalau bukan GUEST (ID 1), kabari dia.
        $recipient = $task->assignedBy;

        if (!$recipient || $recipient->id === 1) {
            // Cari HOD di departemen terkait jika pembuatnya GUEST atau tidak ada
            $recipient = User::role('head_department')
                ->where('department_id', $task->department_id)
                ->first();
        }

        if (!$recipient) {
            Log::warning("No HOD or valid reporter found for task #{$task->id} completion notification.");
            return;
        }

        Log::info("Notifying {$recipient->name} (ID: {$recipient->id}) on task #{$task->id} complete.");
        $recipient->notify(new TaskCompletedNotification($task));
    }
}
