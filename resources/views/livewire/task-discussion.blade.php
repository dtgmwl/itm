<?php

use App\Models\TaskComment;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\RateLimiter;

use function Livewire\Volt\{
    state,
    mount,
    computed
};

state([
    'taskId',
    'newComment' => '',
    'editingCommentId' => null,
    'editedComment' => '',
]);

mount(function ($taskId) {
    $this->taskId = $taskId;
});

$comments = computed(function () {
    return TaskComment::query()
        ->where('task_id', $this->taskId)
        ->with('user')
        ->oldest()
        ->get();
});

$sendComment = function () {
    $this->validate([
        'newComment' => ['required', 'string', 'max:65535'],
    ]);

    $userKey = 'task-comment:user:' . auth()->id();

    if (RateLimiter::tooManyAttempts($userKey, 10)) {
        $seconds = RateLimiter::availableIn($userKey);
        Notification::make()
            ->title('Terlalu banyak komentar')
            ->body("Coba lagi {$seconds} detik lagi.")
            ->danger()
            ->send();
        return;
    }

    $comment = TaskComment::create([
        'task_id' => $this->taskId,
        'user_id' => auth()->id(),
        'comment' => trim($this->newComment),
    ]);

    $task = \App\Models\Task::with(['assignees', 'assignedBy', 'assignedTo', 'department'])->find($this->taskId);

    $commenterIds = TaskComment::where('task_id', $this->taskId)
        ->pluck('user_id');

    $hods = User::role('head_department')
        ->when($task->department_id, fn ($q) => $q->where('department_id', $task->department_id))
        ->get();

    $usersToNotify = collect([$task->assignedBy, $task->assignedTo])
        ->merge($task->assignees)
        ->merge(User::whereIn('id', $commenterIds)->get())
        ->merge($hods)
        ->filter()
        ->unique('id')
        ->reject(fn ($user) => $user->id === auth()->id())
        ->filter(fn ($user) => $user->can('view', $task));

    \Illuminate\Support\Facades\Notification::send($usersToNotify, app(\App\Notifications\NewTaskCommentNotification::class, ['task' => $task, 'comment' => $comment]));

    RateLimiter::hit($userKey, 60);

    $this->reset('newComment');
    $this->dispatch('comment-sent');

    Notification::make()
        ->title('Pesan terkirim')
        ->success()
        ->send();
};

$startEdit = function ($id, $content) {
    $this->editingCommentId = $id;
    $this->editedComment = $content;
};

$cancelEdit = function () {
    $this->reset(['editingCommentId', 'editedComment']);
};

$saveEdit = function () {
    $this->validate([
        'editedComment' => ['required', 'string', 'max:65535'],
    ]);

    $comment = TaskComment::findOrFail($this->editingCommentId);

    if ((int) auth()->id() !== (int) $comment->user_id) {
        return;
    }

    $comment->update([
        'comment' => trim($this->editedComment),
        'edited_at' => now(),
    ]);

    $this->cancelEdit();

    Notification::make()
        ->title('Komentar diperbarui')
        ->success()
        ->send();
};

$deleteComment = function ($id) {
    $comment = TaskComment::findOrFail($id);

    if ((int) auth()->id() !== (int) $comment->user_id) {
        return;
    }

    $comment->delete();

    Notification::make()
        ->title('Komentar dihapus')
        ->success()
        ->send();
};

?>

<div class="flex flex-col gap-4">
    <div x-on:comment-sent.window="$nextTick(() => $el.scrollTop = $el.scrollHeight)" class="max-h-[400px] space-y-3 overflow-y-auto pr-1">
        @if($this->comments->isEmpty())
            <div class="flex flex-col items-center justify-center py-10 text-center">
                <x-heroicon-m-chat-bubble-left-right class="mb-3 h-12 w-12 text-gray-300 dark:text-gray-600" />
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Belum ada diskusi. Mulai percakapan pertama!
                </p>
            </div>
        @else
            @foreach($this->comments as $comment)
                @php
                    $isOwner = (int) auth()->id() === (int) $comment->user_id;
                @endphp

                <div id="comment-{{ $comment->id }}" wire:key="comment-{{ $comment->id }}" class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                        <div class="flex items-center gap-2 min-w-0">
                            <div class="min-w-0">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ explode(' ', $comment->user->name)[0] }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $comment->created_at->diffForHumans() }}
                                    @if($comment->edited_at)
                                        &middot; <span class="italic">diedit</span>
                                    @endif
                                </span>
                            </div>
                        </div>

                        @if($isOwner && $editingCommentId !== $comment->id)
                            <div class="flex shrink-0 gap-1">
                                <button
                                    type="button"
                                    wire:click='startEdit(@json($comment->id), @json($comment->comment))'
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-amber-500 dark:hover:bg-gray-800"
                                    title="Edit"
                                >
                                    <x-heroicon-m-pencil-square class="h-4 w-4" />
                                </button>
                                <button
                                    type="button"
                                    wire:click="deleteComment({{ $comment->id }})"
                                    wire:confirm="Hapus komentar ini?"
                                    class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-100 hover:text-danger-500 dark:hover:bg-gray-800"
                                    title="Hapus"
                                >
                                    <x-heroicon-m-trash class="h-4 w-4" />
                                </button>
                            </div>
                        @endif
                    </div>

                    <div class="px-4 py-3">
                        @if($editingCommentId === $comment->id)
                            <div class="space-y-3">
                                <textarea
                                    wire:model.live="editedComment"
                                    rows="3"
                                    class="fi-input block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm transition duration-75 placeholder:text-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-primary-500"
                                ></textarea>

                                @error('editedComment')
                                    <p class="text-sm text-danger-500">{{ $message }}</p>
                                @enderror

                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        wire:click="cancelEdit"
                                        class="fi-btn fi-btn-size-sm rounded-lg bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="saveEdit"
                                        class="fi-btn fi-btn-size-sm rounded-lg bg-amber-500 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600"
                                    >
                                        Simpan
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="whitespace-pre-wrap text-sm leading-relaxed text-gray-800 dark:text-gray-200">
                                {!! nl2br(e($comment->comment)) !!}
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <div class="border-t border-gray-200 pt-4 dark:border-gray-800">
        <form wire:submit.prevent="sendComment" class="space-y-3">
            <textarea
                wire:model.live="newComment"
                rows="3"
                placeholder="Ketik tanggapan, koordinasi, atau kendala detail di sini..."
                class="fi-input block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm transition duration-75 placeholder:text-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-primary-500"
            ></textarea>

            @error('newComment')
                <p class="text-sm text-danger-500">{{ $message }}</p>
            @enderror

            <div class="flex justify-end">
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="sendComment"
                    class="fi-btn fi-btn-size-md inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <x-heroicon-m-paper-airplane class="h-4 w-4" wire:loading.remove wire:target="sendComment" />
                    <svg wire:loading wire:target="sendComment" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Kirim Pesan
                </button>
            </div>
        </form>
    </div>
</div>
