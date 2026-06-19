<?php
namespace App\Models;

use App\Enums\ActionType;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskLog extends Model
{
    // Sengaja di-disable biar ga ada celah edit audit trail
    public const UPDATED_AT = null; 

    protected $fillable = [
        'task_id', 'user_id', 'action_type',
        'old_status', 'new_status',
        'old_assigned_to', 'new_assigned_to',
        'note', 'metadata', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'action_type'    => ActionType::class,
        'old_status'     => TaskStatus::class,
        'new_status'     => TaskStatus::class,
        'metadata'       => 'array',
        'created_at'     => 'datetime',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function oldAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'old_assigned_to');
    }

    public function newAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'new_assigned_to');
    }
}
