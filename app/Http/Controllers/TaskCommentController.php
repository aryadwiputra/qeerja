<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\Workspace;
use App\Services\TaskActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TaskCommentController extends Controller
{
    public function store(StoreCommentRequest $request, Workspace $workspace, Project $project, Task $task, TaskActivityService $activity): RedirectResponse
    {
        $validated = $request->validated();

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        $activity->commented($task, $request->user(), $comment);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Comment added.']);

        return back();
    }

    public function update(UpdateCommentRequest $request, Workspace $workspace, Project $project, Task $task, TaskComment $comment): RedirectResponse
    {
        abort_unless((int) $comment->task_id === (int) $task->id, 404);

        $comment->update([
            'body' => $request->validated('body'),
            'edited_at' => now(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Comment updated.']);

        return back();
    }

    public function destroy(Workspace $workspace, Project $project, Task $task, TaskComment $comment): RedirectResponse
    {
        abort_unless((int) $comment->task_id === (int) $task->id, 404);

        Gate::authorize('delete', $comment);

        $comment->delete();

        Inertia::flash('toast', ['type' => 'info', 'message' => 'Comment deleted.']);

        return back();
    }
}
