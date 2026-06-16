<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ComponentController extends Controller
{
    public function index(Workspace $workspace, Project $project): Response
    {
        Gate::authorize('view', $project);

        $components = $project->components()
            ->withCount('tasks')
            ->with('lead:id,name,avatar')
            ->orderBy('name')
            ->get();

        return Inertia::render('projects/components/index', [
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
            ],
            'components' => $components->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'description' => $c->description,
                'tasks_count' => $c->tasks_count,
                'lead' => $c->lead,
            ]),
            'members' => $project->members()
                ->with('user:id,name,avatar')
                ->get()
                ->map(fn ($m) => [
                    'id' => $m->user->id,
                    'name' => $m->user->name,
                    'avatar' => $m->user->avatar,
                ]),
        ]);
    }

    public function store(Request $request, Workspace $workspace, Project $project): RedirectResponse
    {
        Gate::authorize('update', $project);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'lead_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $project->components()->create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Component created.']);

        return back();
    }

    public function update(Request $request, Workspace $workspace, Project $project, Component $component): RedirectResponse
    {
        Gate::authorize('update', $project);
        abort_unless((int) $component->project_id === (int) $project->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'lead_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $component->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Component updated.']);

        return back();
    }

    public function destroy(Workspace $workspace, Project $project, Component $component): RedirectResponse
    {
        Gate::authorize('update', $project);
        abort_unless((int) $component->project_id === (int) $project->id, 404);

        $component->tasks()->detach();
        $component->delete();

        Inertia::flash('toast', ['type' => 'info', 'message' => 'Component deleted.']);

        return back();
    }

    public function addTask(Request $request, Workspace $workspace, Project $project, Component $component): RedirectResponse
    {
        Gate::authorize('update', $project);
        abort_unless((int) $component->project_id === (int) $project->id, 404);

        $validated = $request->validate([
            'task_id' => ['required', 'integer', 'exists:tasks,id'],
        ]);

        $component->tasks()->syncWithoutDetaching([$validated['task_id']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Task added to component.']);

        return back();
    }

    public function removeTask(Request $request, Workspace $workspace, Project $project, Component $component): RedirectResponse
    {
        Gate::authorize('update', $project);
        abort_unless((int) $component->project_id === (int) $project->id, 404);

        $validated = $request->validate([
            'task_id' => ['required', 'integer', 'exists:tasks,id'],
        ]);

        $component->tasks()->detach($validated['task_id']);

        Inertia::flash('toast', ['type' => 'info', 'message' => 'Task removed from component.']);

        return back();
    }
}
