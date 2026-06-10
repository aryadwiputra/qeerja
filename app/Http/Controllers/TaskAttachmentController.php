<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\Workspace;
use App\Services\TaskActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TaskAttachmentController extends Controller
{
    public function store(StoreAttachmentRequest $request, Workspace $workspace, Project $project, Task $task, TaskActivityService $activity): RedirectResponse
    {
        $file = $request->file('file');
        $path = $file->store("workspaces/{$workspace->id}/projects/{$project->id}/tasks/{$task->id}", 'public');

        $attachment = $task->attachments()->create([
            'uploaded_by' => $request->user()->id,
            'disk' => 'public',
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize() ?: 0,
        ]);

        $activity->attachmentAdded($task, $request->user(), $attachment);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Attachment uploaded.']);

        return back();
    }

    public function destroy(Workspace $workspace, Project $project, Task $task, TaskAttachment $attachment): RedirectResponse
    {
        abort_unless((int) $attachment->task_id === (int) $task->id, 404);

        Gate::authorize('update', $task);

        Storage::disk($attachment->disk)->delete($attachment->file_path);
        $attachment->delete();

        Inertia::flash('toast', ['type' => 'info', 'message' => 'Attachment deleted.']);

        return back();
    }
}
