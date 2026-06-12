<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\Workspace;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TaskAttachmentPreviewController extends Controller
{
    public function preview(Workspace $workspace, Project $project, Task $task, TaskAttachment $attachment): StreamedResponse
    {
        abort_unless((int) $attachment->task_id === (int) $task->id, 404);

        Gate::authorize('view', $task);

        if (! Storage::disk($attachment->disk)->exists($attachment->file_path)) {
            abort(404);
        }

        return Storage::disk($attachment->disk)->response($attachment->file_path, $attachment->file_name, [
            'Content-Type' => $attachment->mime_type ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$attachment->file_name.'"',
        ]);
    }

    public function download(Workspace $workspace, Project $project, Task $task, TaskAttachment $attachment): StreamedResponse
    {
        abort_unless((int) $attachment->task_id === (int) $task->id, 404);

        Gate::authorize('view', $task);

        if (! Storage::disk($attachment->disk)->exists($attachment->file_path)) {
            abort(404);
        }

        return Storage::disk($attachment->disk)->download($attachment->file_path, $attachment->file_name, [
            'Content-Type' => $attachment->mime_type ?? 'application/octet-stream',
        ]);
    }
}
