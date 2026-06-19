<?php

namespace App\Listeners;

use App\Events\TaskStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class BroadcastTaskUpdate implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TaskStatusChanged $event): void
    {
        // To be implemented when Reverb is installed
    }
}
