<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class TaskCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Task $task) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Tugas Selesai: {$this->task->title}")
            ->line("Tugas yang Anda berikan telah diselesaikan: {$this->task->title}")
            ->action('Lihat Tugas', url("/admin/tasks/{$this->task->id}"))
            ->line('Terima kasih telah menggunakan sistem kami!');
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Tugas Selesai: ' . $this->task->title)
            ->body('Staf telah menyelesaikan tugas ini.')
            ->icon('heroicon-o-check-circle')
            ->success()
            ->getDatabaseMessage();
    }
}
