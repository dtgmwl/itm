<?php

use App\Events\TaskCommentAdded;
use App\Models\TaskComment;
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

    // ponytail: satu jalur notifikasi via event; penerima dihitung di listener
    TaskCommentAdded::dispatch($task, auth()->user(), $comment);

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

<div class="flex flex-col gap-3">
    <div x-on:comment-sent.window="$nextTick(() => $el.scrollTop = $el.scrollHeight)" class="max-h-[380px] min-h-[180px] space-y-2 overflow-y-auto pr-1">
        @if($this->comments->isEmpty())
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                    <x-heroicon-o-chat-bubble-left-right class="h-6 w-6 text-gray-400 dark:text-gray-500" />
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Belum ada diskusi. Mulai percakapan pertama!
                </p>
            </div>
        @else
            @foreach($this->comments as $comment)
                @php
                    $isOwner = (int) auth()->id() === (int) $comment->user_id;
                    $nameParts = explode(' ', trim($comment->user->name));
                    $initials = strtoupper(mb_substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? mb_substr($nameParts[1], 0, 1) : ''));
                @endphp

                <div id="comment-{{ $comment->id }}" wire:key="comment-{{ $comment->id }}" class="flex gap-2">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold uppercase text-gray-500 dark:bg-gray-700 dark:text-gray-300" title="{{ $comment->user->name }}">
                        {{ $initials }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="truncate text-xs font-semibold text-gray-900 dark:text-white">
                                {{ $nameParts[0] }}
                            </span>
                            <span class="shrink-0 text-xs text-gray-400">
                                {{ $comment->created_at->diffForHumans() }}
                                @if($comment->edited_at)
                                    &middot; <span class="italic">diedit</span>
                                @endif
                            </span>

                            @if($isOwner && $editingCommentId !== $comment->id)
                                <span class="ml-auto flex shrink-0 gap-1">
                                    <button
                                        type="button"
                                        wire:click='startEdit(@json($comment->id), @json($comment->comment))'
                                        class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-200 hover:text-amber-500 dark:hover:bg-gray-600"
                                        title="Edit"
                                    >
                                        <x-heroicon-m-pencil-square class="h-4 w-4" />
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="deleteComment({{ $comment->id }})"
                                        wire:confirm="Hapus komentar ini?"
                                        class="flex h-7 w-7 items-center justify-center rounded-lg text-gray-400 transition hover:bg-gray-200 hover:text-danger-500 dark:hover:bg-gray-600"
                                        title="Hapus"
                                    >
                                        <x-heroicon-m-trash class="h-4 w-4" />
                                    </button>
                                </span>
                            @endif
                        </div>

                        <div class="mt-1 rounded-2xl rounded-tl-md bg-gray-100 px-3 py-2 text-sm text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                            @if($editingCommentId === $comment->id)
                                <div class="space-y-2">
                                    <textarea
                                        wire:model.live="editedComment"
                                        rows="2"
                                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm transition duration-75 placeholder:text-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-primary-500"
                                    ></textarea>

                                    @error('editedComment')
                                        <p class="text-sm text-danger-500">{{ $message }}</p>
                                    @enderror

                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            wire:click="cancelEdit"
                                            class="rounded-lg bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                                        >
                                            Batal
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="saveEdit"
                                            class="rounded-lg bg-amber-500 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600"
                                        >
                                            Simpan
                                        </button>
                                    </div>
                                </div>
                            @else
                                <div class="whitespace-pre-wrap break-words leading-relaxed">
                                    {!! nl2br(e($comment->comment)) !!}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <div class="border-t border-gray-200 pt-3 dark:border-gray-700">
        <form wire:submit.prevent="sendComment" class="space-y-2">
            <div class="flex items-end gap-2">
                <textarea
                    wire:model.live="newComment"
                    rows="2"
                    placeholder="Ketik tanggapan, koordinasi, atau kendala detail di sini..."
                    class="block w-full flex-1 rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm transition duration-75 placeholder:text-gray-400 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500 dark:focus:border-primary-500"
                ></textarea>

                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="sendComment"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <x-heroicon-m-paper-airplane class="h-4 w-4" wire:loading.remove wire:target="sendComment" />
                    <svg wire:loading wire:target="sendComment" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Kirim
                </button>
            </div>

            @error('newComment')
                <p class="text-sm text-danger-500">{{ $message }}</p>
            @enderror
        </form>
    </div>
</div>
