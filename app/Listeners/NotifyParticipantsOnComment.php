<?php

namespace App\Listeners;

use App\Events\TaskCommentAdded;
use App\Models\TaskComment;
use App\Models\User;
use App\Notifications\NewTaskCommentNotification;
use Illuminate\Support\Facades\Log;

class NotifyParticipantsOnComment
{
    public function handle(TaskCommentAdded $event): void
    {
        $task = $event->task;
        Log::info("Handling TaskCommentAdded for task {$task->id} by user {$event->actor->id}");

        $commenterIds = TaskComment::where('task_id', $task->id)
            ->pluck('user_id');

        $hods = User::role('head_department')
            ->when($task->department_id, fn ($q) => $q->where('department_id', $task->department_id))
            ->get();

        $participants = collect([$task->assignedBy, $task->assignedTo])
            ->merge($task->assignees)
            ->merge(User::whereIn('id', $commenterIds)->get())
            ->merge($hods)
            ->filter()
            ->unique('id')
            ->reject(fn ($user) => $user->id === $event->actor->id)
            ->filter(fn ($user) => $user->can('view', $task));

        foreach ($participants as $participant) {
            $participant->notify(new NewTaskCommentNotification($task, $event->comment));
        }
    }
}
