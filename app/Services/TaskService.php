<?php
namespace App\Services;

use App\DTOs\CreateTaskDTO;
use App\Enums\TaskStatus;
use App\Events\TaskCreated;
use App\Events\TaskStatusChanged;
use App\Events\TaskCompleted;
use App\Events\TaskCancelled;
use App\Exceptions\InvalidTaskTransitionException;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TaskService
{
    /**
     * Create a new task using a DTO and trigger the creation event.
     */

    public function createTask(CreateTaskDTO $dto): Task
    {
        return DB::transaction(function () use ($dto) {
            $status = !empty($dto->assignees)
                ? TaskStatus::Assigned
                : TaskStatus::Open;

            $task = Task::create([
                'title'         => $dto->title,
                'description'   => $dto->description,
                'priority'      => $dto->priority,
                'status'        => $status,
                'task_source'   => $dto->taskSource,
                'assigned_by'   => $dto->assignedBy,
                'department_id' => $dto->departmentId,
                'start_date'    => $dto->startDate,
                'due_date'      => $dto->dueDate,
            ]);

            // Trigger event for audit log and notifications
            $actor = auth()->user() ?? User::where('email', 'guest@it.local')->first();
            event(new TaskCreated($task, $actor));

            if (!empty($dto->assignees)) {
                $task->assignees()->sync($dto->assignees);

                foreach (array_unique($dto->assignees) as $assigneeId) {
                    $staff = User::find($assigneeId);
                    if ($staff) {
                        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
                        $caller = $trace[1]['function'] ?? '?';
                        $callerClass = $trace[1]['class'] ?? '?';
                        \Illuminate\Support\Facades\Log::info("DISPATCH TaskAssigned from {$callerClass}::{$caller} for user {$assigneeId} task {$task->id}");
                        event(new \App\Events\TaskAssigned($task, auth()->user(), $staff));
                    }
                }
            }

            return $task;
        });
    }

    /**
     * Update task status safely and log the transition.
     */
    public function updateStatus(Task $task, $status, User $actor, ?string $note = null): Task
    {
        return DB::transaction(function () use ($task, $status, $actor, $note) {
            $oldStatus = $task->status;

            $newStatus = $status instanceof TaskStatus
                ? $status
                : TaskStatus::tryFrom($status);

            if (!$newStatus) {
                throw new \InvalidArgumentException("Status tidak dikenali oleh sistem.");
            }

            // Enforce state machine logic
            if ($oldStatus !== $newStatus && !$oldStatus->canTransitionTo($newStatus)) {
                throw new InvalidTaskTransitionException(
                    "Pelanggaran SOP: Status [{$oldStatus->getLabel()}] tidak bisa langsung diubah menjadi [{$newStatus->getLabel()}]!"
                );
            }

            $task->update([
                'status'       => $newStatus->value,
                'completed_at' => $newStatus === TaskStatus::Completed ? now() : $task->completed_at,
                'cancelled_at' => $newStatus === TaskStatus::Cancelled ? now() : $task->cancelled_at,
            ]);

            event(new TaskStatusChanged($task, $oldStatus, $newStatus, $actor, $note));

            if ($newStatus === TaskStatus::Completed) {
                event(new TaskCompleted($task, $actor));
            }

            if ($newStatus === TaskStatus::Cancelled) {
                event(new TaskCancelled($task, $actor));
            }

            return $task->fresh();
        });
    }
}
