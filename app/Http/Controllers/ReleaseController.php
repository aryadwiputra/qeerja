<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Release;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReleaseController extends Controller
{
    public function index(Workspace $workspace, Project $project): Response
    {
        Gate::authorize('view', $project);

        $releases = $project->releases()
            ->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($q) => $q->whereNotNull('completed_at')])
            ->with('creator:id,name')
            ->orderByRaw("CASE status WHEN 'draft' THEN 0 WHEN 'scheduled' THEN 1 WHEN 'released' THEN 2 ELSE 3 END")
            ->orderByDesc('release_date')
            ->get();

        return Inertia::render('projects/releases/index', [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
            ],
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'key' => $project->key,
                'slug' => $project->slug,
                'color' => $project->color,
            ],
            'releases' => $releases,
        ]);
    }

    public function indexJson(Workspace $workspace, Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        $releases = $project->releases()
            ->withCount(['tasks', 'tasks as completed_tasks_count' => fn ($q) => $q->whereNotNull('completed_at')])
            ->with('creator:id,name')
            ->orderByRaw("CASE status WHEN 'draft' THEN 0 WHEN 'scheduled' THEN 1 WHEN 'released' THEN 2 ELSE 3 END")
            ->orderByDesc('release_date')
            ->get();

        return response()->json(['releases' => $releases]);
    }

    public function show(Workspace $workspace, Project $project, Release $release): JsonResponse
    {
        Gate::authorize('view', $project);

        abort_unless((int) $release->project_id === (int) $project->id, 404);

        $release->loadCount(['tasks', 'tasks as completed_tasks_count' => fn ($q) => $q->whereNotNull('completed_at')]);
        $release->load('creator:id,name');

        $tasks = $release->tasks()
            ->with(['priority:id,name,key,level,color', 'taskType:id,name,key,color', 'assignees:id,name,avatar', 'boardColumn:id,name,status_key,color'])
            ->orderBy('position')
            ->get()
            ->map(fn ($task) => [
                'id' => $task->id,
                'task_number' => $task->task_number,
                'code' => $task->code,
                'title' => $task->title,
                'status' => $task->status,
                'completed_at' => $task->completed_at,
                'story_points' => $task->story_points,
                'priority' => $task->priority,
                'task_type' => $task->taskType,
                'assignees' => $task->assignees,
                'board_column' => $task->boardColumn,
            ]);

        $availableTasks = $project->tasks()
            ->whereNull('release_id')
            ->whereNull('archived_at')
            ->orderBy('code')
            ->get(['id', 'code', 'title']);

        return response()->json([
            'release' => [
                'id' => $release->id,
                'name' => $release->name,
                'description' => $release->description,
                'release_date' => $release->release_date?->format('Y-m-d'),
                'status' => $release->status,
                'created_at' => $release->created_at,
                'tasks_count' => $release->tasks_count,
                'completed_tasks_count' => $release->completed_tasks_count,
                'creator' => $release->creator ? [
                    'id' => $release->creator->id,
                    'name' => $release->creator->name,
                ] : null,
            ],
            'tasks' => $tasks,
            'availableTasks' => $availableTasks,
        ]);
    }

    public function store(Request $request, Workspace $workspace, Project $project): JsonResponse
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'release_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:draft,scheduled,released'],
        ]);

        $release = $project->releases()->create([
            'created_by' => $request->user()->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'release_date' => $validated['release_date'] ?? null,
            'status' => $validated['status'] ?? 'draft',
        ]);

        return response()->json([
            'id' => $release->id,
            'name' => $release->name,
            'description' => $release->description,
            'release_date' => $release->release_date?->format('Y-m-d'),
            'status' => $release->status,
        ], 201);
    }

    public function update(Request $request, Workspace $workspace, Project $project, Release $release): JsonResponse
    {
        Gate::authorize('update', $project);

        abort_unless((int) $release->project_id === (int) $project->id, 404);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'release_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'in:draft,scheduled,released'],
        ]);

        if (isset($validated['status']) && $validated['status'] === 'released' && $release->status !== 'released') {
            $validated['release_date'] = $release->release_date ?? now();
        }

        $release->update($validated);

        return response()->json([
            'id' => $release->id,
            'name' => $release->name,
            'description' => $release->description,
            'release_date' => $release->release_date?->format('Y-m-d'),
            'status' => $release->status,
        ]);
    }

    public function destroy(Workspace $workspace, Project $project, Release $release): JsonResponse
    {
        Gate::authorize('update', $project);

        abort_unless((int) $release->project_id === (int) $project->id, 404);

        $release->tasks()->update(['release_id' => null]);
        $release->delete();

        return response()->json(['ok' => true]);
    }

    public function addTask(Request $request, Workspace $workspace, Project $project, Release $release): JsonResponse
    {
        Gate::authorize('update', $project);

        abort_unless((int) $release->project_id === (int) $project->id, 404);

        $validated = $request->validate([
            'task_id' => ['required', 'integer', 'exists:tasks,id'],
        ]);

        $project->tasks()->where('id', $validated['task_id'])->update(['release_id' => $release->id]);

        return response()->json(['ok' => true]);
    }

    public function removeTask(Request $request, Workspace $workspace, Project $project, Release $release): JsonResponse
    {
        Gate::authorize('update', $project);

        abort_unless((int) $release->project_id === (int) $project->id, 404);

        $validated = $request->validate([
            'task_id' => ['required', 'integer', 'exists:tasks,id'],
        ]);

        $project->tasks()->where('id', $validated['task_id'])->where('release_id', $release->id)->update(['release_id' => null]);

        return response()->json(['ok' => true]);
    }
}
