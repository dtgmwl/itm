<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Task $task) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Tugas Baru: {$this->task->title}")
            ->line("Anda telah diberikan tugas baru: {$this->task->title}")
            ->action('Lihat Tugas', url("/admin/tasks/{$this->task->id}"))
            ->line('Terima kasih telah menggunakan sistem kami!');
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Tugas Baru: ' . $this->task->title)
            ->body('Anda ditugaskan pada pekerjaan ini.')
            ->icon('heroicon-o-clipboard-document-list')
            ->actions([
                \Filament\Actions\Action::make('view')
                    ->label('Lihat Tugas')
                    ->url(url("/admin/tasks/{$this->task->id}")),
            ])
            ->info()
            ->getDatabaseMessage();
    }
}
