<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TaskActivity;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request, Workspace $workspace, Project $project): Response
    {
        Gate::authorize('view', $project);

        $filters = $request->validate([
            'action' => ['nullable', 'string'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $query = TaskActivity::query()
            ->with(['task:id,project_id,code,title', 'user:id,name,avatar'])
            ->whereHas('task', fn ($q) => $q->where('project_id', $project->id))
            ->latest();

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        $activities = $query->paginate(30)
            ->through(fn ($activity) => [
                'id' => $activity->id,
                'action' => $activity->action,
                'field_name' => $activity->field_name,
                'old_value' => $activity->old_value,
                'new_value' => $activity->new_value,
                'created_at' => $activity->created_at,
                'task' => [
                    'id' => $activity->task->id,
                    'code' => $activity->task->code,
                    'title' => $activity->task->title,
                ],
                'user' => $activity->user ? [
                    'id' => $activity->user->id,
                    'name' => $activity->user->name,
                    'avatar' => $activity->user->avatar,
                ] : null,
            ]);

        $actions = TaskActivity::query()
            ->whereHas('task', fn ($q) => $q->where('project_id', $project->id))
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $members = $project->members()
            ->with('user:id,name,avatar')
            ->get()
            ->map(fn ($m) => [
                'id' => $m->user->id,
                'name' => $m->user->name,
                'avatar' => $m->user->avatar,
            ]);

        return Inertia::render('projects/activity/index', [
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
            'activities' => $activities,
            'filters' => $filters,
            'actions' => $actions,
            'members' => $members,
        ]);
    }
}
