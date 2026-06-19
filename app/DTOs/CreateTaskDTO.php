<?php

namespace App\DTOs;

use App\Enums\TaskPriority;
use App\Enums\TaskSource;
use Carbon\Carbon;

final readonly class CreateTaskDTO
{
    public function __construct(
        public string       $title,
        public string       $description,
        public TaskPriority $priority,
        public int          $assignedBy,
        public ?int         $assignedTo,
        public ?int         $departmentId,
        public ?Carbon      $startDate,
        public ?Carbon      $dueDate,
        public array        $assignees = [],
        public ?TaskSource  $taskSource = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title:        $data['title'],
            description:  $data['description'] ?? '',
            priority:     $data['priority'] instanceof TaskPriority ? $data['priority'] : TaskPriority::from($data['priority'] ?? 'medium'),
            assignedBy:   $data['assigned_by'],
            assignedTo:   $data['assigned_to'] ?? null,
            departmentId: $data['department_id'] ?? null,
            startDate:    isset($data['start_date']) ? Carbon::parse($data['start_date']) : null,
            dueDate:      isset($data['due_date']) ? Carbon::parse($data['due_date']) : null,
            assignees:    $data['assignees'] ?? [],
            taskSource:   isset($data['task_source']) ? ($data['task_source'] instanceof TaskSource ? $data['task_source'] : TaskSource::tryFrom($data['task_source'])) : null,
        );
    }
}
