<?php

namespace App\Providers;

use App\Events\TaskAssigned;
use App\Events\TaskAttachmentUploaded;
use App\Events\TaskCancelled;
use App\Events\TaskCommentAdded;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Events\TaskStatusChanged;
use App\Listeners\LogTaskActivity;
use App\Listeners\NotifyAssignedStaff;
use App\Listeners\NotifyHeadOnTaskComplete;
use App\Listeners\NotifyParticipantsOnComment;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Livewire\Volt\Volt;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            \Filament\Auth\Http\Responses\Contracts\LogoutResponse::class,
            \App\Http\Responses\LogoutResponse::class
        );
    }

    public function boot(): void
    {
        Event::listen([
            TaskCreated::class,
            TaskAssigned::class,
            TaskStatusChanged::class,
            TaskCommentAdded::class,
            TaskAttachmentUploaded::class,
        ], LogTaskActivity::class);

        Event::listen(TaskAssigned::class, NotifyAssignedStaff::class);
        Event::listen(TaskCompleted::class, NotifyHeadOnTaskComplete::class);
        Event::listen(TaskCommentAdded::class, NotifyParticipantsOnComment::class);

        Volt::mount([resource_path('views/livewire')]);

        if (app()->environment('production') || str_contains(config('app.url'), 'ngrok-free.dev')) {
            URL::forceScheme('https');
        }
    }
}
