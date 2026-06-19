<?php
namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Guava\Calendar\Contracts\Eventable;
use Guava\Calendar\ValueObjects\CalendarEvent;

class Task extends Model implements Eventable
{
    use SoftDeletes;

    protected $fillable = [
        'title', 'description', 'priority', 'status', 'task_source',
        'assigned_by', 'assigned_to', 'department_id',
        'start_date', 'due_date', 'completed_at', 'cancelled_at',
    ];

    public function toCalendarEvent(): CalendarEvent
    {
        // Support multiple assignees from pivot table, fallback to legacy assignedTo
        $assigneeNames = $this->assignees->pluck('name')->toArray();
        if (empty($assigneeNames) && $this->assignedTo) {
            $assigneeNames = [$this->assignedTo->name];
        }

        $displayName = empty($assigneeNames)
            ? 'Unassigned'
            : implode(', ', array_map(function ($name) {
                return str_contains($name, ' ') ? explode(' ', $name)[0] : $name;
            }, $assigneeNames));

        $color = match($this->status) {
            TaskStatus::Open       => '#9ca3af', // Gray-400
            TaskStatus::Assigned   => '#3b82f6', // Blue-500 (info)
            TaskStatus::InProgress => '#f59e0b', // Amber-500 (warning)
            TaskStatus::Pending    => '#f97316', // Orange-500
            TaskStatus::Completed  => '#10b981', // Emerald-500 (success)
            TaskStatus::Cancelled  => '#ef4444', // Red-500 (danger)
            default                => '#3b82f6',
        };

        // Completed tasks show on their completion date instead
        if ($this->status === TaskStatus::Completed && $this->completed_at) {
            $actualStartDate = $this->completed_at;
            $actualDueDate = $this->completed_at;
        } else {
            $actualStartDate = $this->start_date;
            $actualDueDate = $this->due_date;

            // If start_date is not set, but due_date is, then effectively start on due_date (single day event)
            if (!$actualStartDate && $actualDueDate) {
                $actualStartDate = $actualDueDate;
            }
            // If due_date is not set, but start_date is, then effectively end on start_date (single day event)
            elseif (!$actualDueDate && $actualStartDate) {
                $actualDueDate = $actualStartDate;
            }
            // If neither is set, fall back to created_at for both (single day on creation date)
            elseif (!$actualStartDate && !$actualDueDate) {
                $actualStartDate = $this->created_at;
                $actualDueDate = $this->created_at;
            }
        }

        return CalendarEvent::make($this)
            ->title("{$displayName} | {$this->title}")
            ->start($actualStartDate)
            ->end($actualDueDate)
            ->allDay(true)
            ->backgroundColor($color)
            ->textColor('#ffffff')
            ->styles([
                'font-weight' => 'bold',
            ]);
    }

    protected $casts = [
        'status'       => TaskStatus::class,
        'priority'     => TaskPriority::class,
        'task_source'  => \App\Enums\TaskSource::class,
        'start_date'   => 'date',
        'due_date'     => 'date',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TaskLog::class)->latest();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class)->latest();
    }

    // Relasi baru (Many-to-Many)
    public function assignees()
    {
        return $this->belongsToMany(User::class, 'task_user', 'task_id', 'user_id');
    }

    // Scopes buat query gampang
    public function scopeOverdue($query)
    {
        return $query
            ->whereNotIn('status', [TaskStatus::Completed->value, TaskStatus::Cancelled->value])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString());
    }

    public function isOverdue(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && !in_array($this->status, [TaskStatus::Completed, TaskStatus::Cancelled]);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [TaskStatus::Completed, TaskStatus::Cancelled]);
    }
}
