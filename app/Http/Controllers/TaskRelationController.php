<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRelationRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TaskRelationController extends Controller
{
    public function store(StoreTaskRelationRequest $request, Workspace $workspace, Project $project, Task $task): RedirectResponse
    {
        Gate::authorize('update', $task);

        $validated = $request->validated();

        $task->relatedTasks()->create([
            'related_task_id' => $validated['related_task_id'],
            'relation_type' => $validated['relation_type'],
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Relation added.']);

        return back();
    }

    public function destroy(Workspace $workspace, Project $project, Task $task, int $relation): RedirectResponse
    {
        Gate::authorize('update', $task);

        $task->relatedTasks()->whereKey($relation)->delete();

        Inertia::flash('toast', ['type' => 'info', 'message' => 'Relation removed.']);

        return back();
    }
}
