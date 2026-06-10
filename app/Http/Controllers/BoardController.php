<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BoardController extends Controller
{
    public function show(Workspace $workspace, Project $project): Response
    {
        $board = $project->boards()->where('is_default', true)->firstOrFail();

        Gate::authorize('view', $board);

        $columns = $board->columns()
            ->orderBy('position')
            ->get()
            ->map(fn ($col) => [
                'id' => $col->id,
                'name' => $col->name,
                'status_key' => $col->status_key,
                'color' => $col->color,
                'position' => $col->position,
                'is_done_column' => $col->is_done_column,
                'tasks' => $col->tasks()
                    ->with(['assignees:id,name,avatar', 'priority:id,name,key,level,color', 'taskType:id,name,key,icon,color', 'epics:id,name,color,status', 'sprints:id,name,status,start_date,end_date'])
                    ->orderBy('position')
                    ->get()
                    ->map(fn ($task) => [
                        'id' => $task->id,
                        'task_number' => $task->task_number,
                        'code' => $task->code,
                        'title' => $task->title,
                        'status' => $task->status,
                        'position' => $task->position,
                        'due_date' => $task->due_date,
                        'priority' => $task->priority ? [
                            'id' => $task->priority->id,
                            'name' => $task->priority->name,
                            'key' => $task->priority->key,
                            'color' => $task->priority->color,
                        ] : null,
                        'task_type' => [
                            'id' => $task->taskType->id,
                            'name' => $task->taskType->name,
                            'key' => $task->taskType->key,
                            'color' => $task->taskType->color,
                        ],
                        'assignees' => $task->assignees->map(fn ($u) => [
                            'id' => $u->id,
                            'name' => $u->name,
                            'avatar' => $u->avatar,
                        ]),
                        'epics' => $task->epics->map(fn ($epic) => [
                            'id' => $epic->id,
                            'name' => $epic->name,
                            'color' => $epic->color,
                            'status' => $epic->status,
                        ]),
                        'sprints' => $task->sprints->map(fn ($sprint) => [
                            'id' => $sprint->id,
                            'name' => $sprint->name,
                            'status' => $sprint->status,
                            'start_date' => $sprint->start_date,
                            'end_date' => $sprint->end_date,
                        ]),
                    ]),
            ]);

        return Inertia::render('projects/board', [
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
            'board' => [
                'id' => $board->id,
                'name' => $board->name,
                'type' => $board->type,
            ],
            'columns' => $columns,
            'taskTypes' => $workspace->taskTypes()->select('id', 'name', 'key', 'color')->get(),
            'priorities' => $workspace->priorities()->select('id', 'name', 'key', 'level')->get(),
            'epics' => $project->epics()->orderBy('name')->get(['id', 'name', 'color', 'status']),
            'sprints' => $project->sprints()->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'planned' THEN 1 WHEN 'completed' THEN 2 ELSE 3 END")->orderByDesc('start_date')->get(['id', 'name', 'status', 'start_date', 'end_date']),
        ]);
    }
}
