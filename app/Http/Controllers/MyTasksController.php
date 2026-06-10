<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MyTasksController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $status = $request->query('status');
        $priorityId = $request->query('priority_id');
        $projectId = $request->query('project_id');

        $tasks = $user->assignedTasks()
            ->with([
                'project:id,name,key,color,slug,workspace_id',
                'project.workspace:id,slug,name',
                'priority:id,name,key,level,color',
                'taskType:id,name,key,color',
                'assignees:id,name,avatar',
                'boardColumn:id,name,status_key,color',
            ])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($priorityId, fn ($q) => $q->where('priority_id', $priorityId))
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->whereNull('completed_at')
            ->latest('tasks.created_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn ($task) => [
                'id' => $task->id,
                'code' => $task->code,
                'title' => $task->title,
                'status' => $task->status,
                'due_date' => $task->due_date,
                'completed_at' => $task->completed_at,
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
                'board_column' => [
                    'id' => $task->boardColumn->id,
                    'name' => $task->boardColumn->name,
                    'status_key' => $task->boardColumn->status_key,
                    'color' => $task->boardColumn->color,
                ],
                'assignees' => $task->assignees->map(fn ($a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'avatar' => $a->avatar,
                ]),
                'project' => [
                    'id' => $task->project->id,
                    'name' => $task->project->name,
                    'key' => $task->project->key,
                    'color' => $task->project->color,
                    'slug' => $task->project->slug,
                ],
                'workspace' => [
                    'id' => $task->project->workspace->id,
                    'name' => $task->project->workspace->name,
                    'slug' => $task->project->workspace->slug,
                ],
            ]);

        $projects = $user->projects()
            ->select('projects.id', 'projects.name', 'projects.key', 'projects.color')
            ->orderBy('name')
            ->get();

        return Inertia::render('my-tasks', [
            'tasks' => $tasks,
            'projects' => $projects,
            'filters' => [
                'status' => $status,
                'priority_id' => $priorityId,
                'project_id' => $projectId,
            ],
        ]);
    }
}
