<?php

namespace App\Http\Controllers;

use App\Models\TaskAttachment;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function show(TaskAttachment $attachment)
    {
        if (!Storage::disk($attachment->disk)->exists($attachment->file_path)) {
            abort(404);
        }

        return Storage::disk($attachment->disk)->response($attachment->file_path);
    }
}
