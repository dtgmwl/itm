<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain', 'text/csv',
    ];

    private const MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024; // 10MB

    /**
     * Upload a file and link it to a task.
     */
    public function upload(UploadedFile $file, Task $task, User $uploader): TaskAttachment
    {
        $this->validateFile($file);

        // Use UUID-based path to prevent enumeration attacks
        $path = $file->storeAs(
            path: "tasks/{$task->id}/attachments",
            name: Str::uuid() . '.' . $file->getClientOriginalExtension(),
            options: ['disk' => 'private']
        );

        $attachment = TaskAttachment::create([
            'task_id'       => $task->id,
            'uploaded_by'   => $uploader->id,
            'original_name' => $file->getClientOriginalName(),
            'file_path'     => $path,
            'file_size'     => $file->getSize(),
            'mime_type'     => $file->getMimeType(),
            'disk'          => 'private',
        ]);

        event(new \App\Events\TaskAttachmentUploaded($task, $uploader, $attachment));

        return $attachment;
    }

    /**
     * Generate a temporary signed URL for secure download.
     */
    public function getTemporaryUrl(TaskAttachment $attachment, int $minutes = 15): string
    {
        return Storage::disk($attachment->disk)
            ->temporaryUrl($attachment->file_path, now()->addMinutes($minutes));
    }

    private function validateFile(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_FILE_SIZE_BYTES) {
            throw new \InvalidArgumentException('File size exceeds 10MB limit.');
        }

        if (!in_array($file->getMimeType(), self::ALLOWED_MIME_TYPES)) {
            throw new \InvalidArgumentException("File type [{$file->getMimeType()}] is not allowed.");
        }
    }
}
