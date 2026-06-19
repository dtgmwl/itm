---
name: filament-single-to-many-migration
description: Procedure for migrating Filament components from single-assignee (belongsTo) to multi-assignee (belongsToMany pivot) pattern
source: auto-skill
extracted_at: '2026-06-05T11:29:29.122Z'
---

## When to apply
When a Laravel/Filament project transitions from a single-user assignment model (e.g., `assigned_to` foreign key) to a multi-user model (e.g., `task_user` pivot table), all dependent components must be updated.

## Files to check and update

### 1. Model relationships
Ensure both relationships coexist during migration:
```php
// Legacy (keep for backward compatibility)
public function assignedTo(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }

// New (many-to-many via pivot)
public function assignees(): BelongsToMany { return $this->belongsToMany(User::class, 'task_user', 'task_id', 'user_id'); }
```

### 2. Widgets using withCount
Change from `assignedTasks` (HasMany) to `tasks` (BelongsToMany via User model):
```php
// BEFORE (single assignee - uses assigned_to column)
->withCount(['assignedTasks', 'assignedTasks as ...' => fn($q) => ...])

// AFTER (multi assignee - uses task_user pivot)
->withCount(['tasks', 'tasks as ...' => fn($q) => ...])
```

### 3. Calendar/Event widgets
Update `getEvents()` to filter by both relationships for staff users:
```php
// BEFORE
$query->where('assigned_to', $user->id);

// AFTER (check both old and new)
$query->where(function ($q) use ($user) {
    $q->where('assigned_to', $user->id)
      ->orWhereHas('assignees', fn($q2) => $q2->where('users.id', $user->id));
});
```

### 4. Calendar event rendering (toCalendarEvent)
Update to collect multiple assignee names:
```php
// BEFORE
$userName = $this->assignedTo?->name ?? 'Unassigned';

// AFTER (with fallback)
$assigneeNames = $this->assignees->pluck('name')->toArray();
if (empty($assigneeNames) && $this->assignedTo) {
    $assigneeNames = [$this->assignedTo->name];
}
$displayName = empty($assigneeNames) ? 'Unassigned' : implode(', ', $assigneeNames);
```

### 5. Resource eager loading
Add new relationship to `getEloquentQuery()` with() call:
```php
// BEFORE
->with(['logs' => fn($q) => $q->latest()]);

// AFTER
->with(['logs' => fn($q) => $q->latest(), 'assignees', 'assignedTo']);
```

### 6. Table columns with searchable
Ensure columns can search both relationships:
```php
->searchable(query: function (Builder $query, string $search) {
    $query->whereHas('assignees', fn($q) => $q->where('name', 'like', "%{$search}%"))
          ->orWhereHas('assignedTo', fn($q) => $q->where('name', 'like', "%{$search}%"));
})
```

### 7. Service layer / DTOs
Update creation logic to sync pivot table:
```php
// Sync assignees after task creation
$task->assignees()->sync($dto->assignees);

// Send notifications to each assignee
foreach ($dto->assignees as $assigneeId) {
    event(new TaskAssigned($task, $actor, User::find($assigneeId)));
}
```

## Key insight
Always maintain backward compatibility by checking the legacy relationship as a fallback. Old records won't have pivot entries, so display logic should gracefully degrade.
