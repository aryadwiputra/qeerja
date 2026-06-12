<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchTasksRequest;
use App\Models\Label;
use App\Models\Priority;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Queries\TaskSearchQuery;
use Illuminate\Database\Eloquent\Builder;
use Inertia\Inertia;
use Inertia\Response;

class TaskSearchController extends Controller
{
    public function index(SearchTasksRequest $request, TaskSearchQuery $searchQuery): Response
    {
        $user = $request->user();
        $filters = $request->validated();
        $accessibleProjectIds = $user->projects()->pluck('projects.id');
        $accessibleWorkspaceIds = $user->projects()->distinct()->pluck('projects.workspace_id');

        $tasks = $searchQuery->build($user, $filters)
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($task) => [
                'id' => $task->id,
                'code' => $task->code,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status,
                'due_date' => $task->due_date,
                'completed_at' => $task->completed_at,
                'archived_at' => $task->archived_at,
                'updated_at' => $task->updated_at,
                'priority' => $task->priority ? [
                    'id' => $task->priority->id,
                    'name' => $task->priority->name,
                    'key' => $task->priority->key,
                    'level' => $task->priority->level,
                    'color' => $task->priority->color,
                ] : null,
                'task_type' => [
                    'id' => $task->taskType->id,
                    'name' => $task->taskType->name,
                    'key' => $task->taskType->key,
                    'color' => $task->taskType->color,
                ],
                'board_column' => $task->boardColumn ? [
                    'id' => $task->boardColumn->id,
                    'name' => $task->boardColumn->name,
                    'status_key' => $task->boardColumn->status_key,
                    'color' => $task->boardColumn->color,
                ] : null,
                'reporter' => [
                    'id' => $task->reporter->id,
                    'name' => $task->reporter->name,
                    'avatar' => $task->reporter->avatar,
                ],
                'assignees' => $task->assignees->map(fn ($assignee) => [
                    'id' => $assignee->id,
                    'name' => $assignee->name,
                    'avatar' => $assignee->avatar,
                ]),
                'labels' => $task->labels->map(fn ($label) => [
                    'id' => $label->id,
                    'name' => $label->name,
                    'color' => $label->color,
                ]),
                'project' => [
                    'id' => $task->project->id,
                    'name' => $task->project->name,
                    'key' => $task->project->key,
                    'slug' => $task->project->slug,
                    'color' => $task->project->color,
                ],
                'workspace' => [
                    'id' => $task->project->workspace->id,
                    'name' => $task->project->workspace->name,
                    'slug' => $task->project->workspace->slug,
                ],
            ]);

        return Inertia::render('tasks/search', [
            'tasks' => $tasks,
            'filters' => [
                'q' => $filters['q'] ?? null,
                'workspace_id' => $filters['workspace_id'] ?? null,
                'project_id' => $filters['project_id'] ?? null,
                'status' => $filters['status'] ?? null,
                'assignee_id' => $filters['assignee_id'] ?? null,
                'reporter_id' => $filters['reporter_id'] ?? null,
                'priority_id' => $filters['priority_id'] ?? null,
                'label_id' => $filters['label_id'] ?? null,
                'due_from' => $filters['due_from'] ?? null,
                'due_to' => $filters['due_to'] ?? null,
                'created_from' => $filters['created_from'] ?? null,
                'created_to' => $filters['created_to'] ?? null,
                'state' => $filters['state'] ?? 'active',
            ],
            'options' => [
                'workspaces' => Workspace::query()
                    ->whereIn('id', $accessibleWorkspaceIds)
                    ->orderBy('name')
                    ->get(['id', 'name', 'slug']),
                'projects' => Project::query()
                    ->whereIn('id', $accessibleProjectIds)
                    ->orderBy('name')
                    ->get(['id', 'workspace_id', 'name', 'key', 'slug', 'color']),
                'priorities' => Priority::query()
                    ->whereIn('workspace_id', $accessibleWorkspaceIds)
                    ->orderBy('level')
                    ->get(['id', 'workspace_id', 'name', 'key', 'level', 'color']),
                'labels' => Label::query()
                    ->whereIn('project_id', $accessibleProjectIds)
                    ->orderBy('name')
                    ->get(['id', 'project_id', 'name', 'color']),
                'users' => User::query()
                    ->whereHas('projects', fn (Builder $query) => $query->whereIn('projects.id', $accessibleProjectIds))
                    ->orderBy('name')
                    ->get(['id', 'name', 'avatar']),
            ],
        ]);
    }
}
