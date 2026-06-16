<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSprintRequest;
use App\Http\Requests\UpdateSprintRequest;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SprintController extends Controller
{
    public function index(Workspace $workspace, Project $project): JsonResponse
    {
        Gate::authorize('view', $project);

        return response()->json([
            'sprints' => $project->sprints()
                ->where('is_backlog', false)
                ->withCount('tasks')
                ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'planned' THEN 1 WHEN 'completed' THEN 2 ELSE 3 END")
                ->orderByDesc('start_date')
                ->get(['id', 'name', 'goal', 'status', 'start_date', 'end_date', 'completed_at']),
        ]);
    }

    public function show(Workspace $workspace, Project $project, Sprint $sprint): Response
    {
        Gate::authorize('view', $project);

        $this->ensureSprintBelongsToProject($sprint, $project);

        $sprint->loadCount('tasks');

        $sprint->load(['tasks' => function ($query) {
            $query->orderBy('status')
                ->orderBy('position')
                ->with(['priority:id,name,key,level,color', 'taskType:id,name,key,color', 'assignees:id,name,avatar', 'boardColumn:id,name,status_key,color']);
        }]);

        $completedCount = $sprint->tasks->filter(fn ($task) => $task->completed_at !== null)->count();

        $sprint->setRelation('tasks', $sprint->tasks->map(fn ($task) => [
            'id' => $task->id,
            'task_number' => $task->task_number,
            'code' => $task->code,
            'title' => $task->title,
            'status' => $task->status,
            'due_date' => $task->due_date,
            'completed_at' => $task->completed_at,
            'priority' => $task->priority,
            'task_type' => $task->taskType,
            'assignees' => $task->assignees,
            'board_column' => $task->boardColumn,
        ]));

        $availableTasks = $project->tasks()
            ->whereDoesntHave('sprints', fn ($q) => $q->where('sprint_id', $sprint->id))
            ->orderBy('code')
            ->get(['id', 'code', 'title']);

        return Inertia::render('projects/sprints/show', [
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
            'sprint' => array_merge(
                $sprint->only('id', 'name', 'goal', 'status', 'start_date', 'end_date', 'committed_points', 'completed_at', 'tasks_count'),
                ['completed_tasks_count' => $completedCount],
            ),
            'sprintTasks' => $sprint->tasks,
            'availableTasks' => $availableTasks,
        ]);
    }

    public function addTask(Request $request, Workspace $workspace, Project $project, Sprint $sprint): RedirectResponse
    {
        Gate::authorize('update', $project);

        $this->ensureSprintBelongsToProject($sprint, $project);

        $validated = $request->validate([
            'task_id' => ['required', 'integer', 'exists:tasks,id'],
        ]);

        $sprint->tasks()->syncWithoutDetaching([$validated['task_id']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Task added to sprint.']);

        return back();
    }

    public function removeTask(Request $request, Workspace $workspace, Project $project, Sprint $sprint): RedirectResponse
    {
        Gate::authorize('update', $project);

        $this->ensureSprintBelongsToProject($sprint, $project);

        $validated = $request->validate([
            'task_id' => ['required', 'integer', 'exists:tasks,id'],
        ]);

        $sprint->tasks()->detach($validated['task_id']);

        Inertia::flash('toast', ['type' => 'info', 'message' => 'Task removed from sprint.']);

        return back();
    }

    public function store(StoreSprintRequest $request, Workspace $workspace, Project $project): RedirectResponse
    {
        $validated = $request->validated();

        $project->sprints()->create([
            'name' => $validated['name'],
            'goal' => $validated['goal'] ?? null,
            'status' => $validated['status'] ?? 'planned',
            'is_backlog' => $validated['is_backlog'] ?? false,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'completed_at' => ($validated['status'] ?? null) === 'completed' ? now() : null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sprint created.']);

        return back();
    }

    public function update(UpdateSprintRequest $request, Workspace $workspace, Project $project, Sprint $sprint): RedirectResponse
    {
        $this->ensureSprintBelongsToProject($sprint, $project);

        $validated = $request->validated();
        $validated['completed_at'] = $validated['status'] === 'completed'
            ? ($sprint->completed_at ?? now())
            : null;

        $sprint->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Sprint updated.']);

        return back();
    }

    public function destroy(Workspace $workspace, Project $project, Sprint $sprint): RedirectResponse
    {
        Gate::authorize('update', $project);

        $this->ensureSprintBelongsToProject($sprint, $project);
        $sprint->tasks()->detach();
        $sprint->delete();

        Inertia::flash('toast', ['type' => 'info', 'message' => 'Sprint deleted.']);

        return back();
    }

    private function ensureSprintBelongsToProject(Sprint $sprint, Project $project): void
    {
        abort_unless((int) $sprint->project_id === (int) $project->id, 404);
    }
}
