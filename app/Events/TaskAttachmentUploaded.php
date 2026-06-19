<?php

namespace App\Events;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskAttachmentUploaded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Task           $task,
        public readonly User           $actor,
        public readonly TaskAttachment $attachment,
    ) {}
}
