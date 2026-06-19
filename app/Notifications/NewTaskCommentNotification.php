<?php

namespace App\Notifications;

use App\Models\Task;
use App\Models\TaskComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class NewTaskCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task,
        public TaskComment $comment
    ) {}

    public function via(object $notifiable): array
    {
        return ['database']; // Prefer database for frequent chat updates
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Pesan Baru dari ' . $this->comment->user->name)
            ->body("Di tugas: {$this->task->title}")
            ->icon('heroicon-o-chat-bubble-left-ellipsis')
            ->actions([
                \Filament\Actions\Action::make('view')
                    ->label('Lihat Pesan')
                    ->url(url("/admin/tasks/{$this->task->id}?relation=1#comment-{$this->comment->id}")),
            ])
            ->info()
            ->getDatabaseMessage();
    }
}
