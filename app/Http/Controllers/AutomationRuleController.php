<?php

namespace App\Http\Controllers;

use App\Models\AutomationRule;
use App\Models\Project;
use App\Models\Workspace;
use App\Services\AutomationEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AutomationRuleController extends Controller
{
    public function index(Workspace $workspace, Project $project): Response
    {
        Gate::authorize('viewAny', [AutomationRule::class, $project]);

        $rules = $project->automationRules()
            ->orderBy('priority', 'desc')
            ->get();

        return Inertia::render('projects/automation/index', [
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
            'rules' => $rules,
            'options' => [
                'trigger_events' => [
                    ['value' => 'task.created', 'label' => 'Task Created'],
                    ['value' => 'task.status_changed', 'label' => 'Status Changed'],
                    ['value' => 'task.priority_changed', 'label' => 'Priority Changed'],
                    ['value' => 'task.assignee_added', 'label' => 'Assignee Added'],
                    ['value' => 'task.due_date_passed', 'label' => 'Due Date Passed'],
                ],
                'condition_fields' => [
                    ['value' => 'status', 'label' => 'Status'],
                    ['value' => 'priority', 'label' => 'Priority'],
                    ['value' => 'assignee', 'label' => 'Assignee'],
                    ['value' => 'label', 'label' => 'Label'],
                    ['value' => 'story_points', 'label' => 'Story Points'],
                ],
                'condition_operators' => [
                    ['value' => 'equals', 'label' => 'Equals'],
                    ['value' => 'not_equals', 'label' => 'Not Equals'],
                    ['value' => 'contains', 'label' => 'Contains'],
                    ['value' => 'not_contains', 'label' => 'Not Contains'],
                    ['value' => 'greater_than', 'label' => 'Greater Than'],
                    ['value' => 'less_than', 'label' => 'Less Than'],
                    ['value' => 'in', 'label' => 'In'],
                    ['value' => 'not_in', 'label' => 'Not In'],
                ],
                'action_types' => [
                    ['value' => 'assign', 'label' => 'Assign User'],
                    ['value' => 'add_label', 'label' => 'Add Label'],
                    ['value' => 'remove_label', 'label' => 'Remove Label'],
                    ['value' => 'set_priority', 'label' => 'Set Priority'],
                    ['value' => 'move_to_column', 'label' => 'Move to Column'],
                    ['value' => 'send_notification', 'label' => 'Send Notification'],
                    ['value' => 'add_comment', 'label' => 'Add Comment'],
                ],
                'board_columns' => $project->boards()
                    ->where('is_default', true)
                    ->first()
                    ?->columns()
                    ->orderBy('position')
                    ->get(['id', 'name', 'status_key', 'color']) ?? collect(),
                'priorities' => $workspace->priorities()
                    ->orderBy('level')
                    ->get(['id', 'name', 'key', 'color']),
                'labels' => $project->labels()
                    ->orderBy('name')
                    ->get(['id', 'name', 'slug', 'color']),
                'members' => $project->members()
                    ->with('user:id,name,avatar')
                    ->get()
                    ->map(fn ($m) => [
                        'id' => $m->user->id,
                        'name' => $m->user->name,
                        'avatar' => $m->user->avatar,
                    ]),
            ],
        ]);
    }

    public function store(Request $request, Workspace $workspace, Project $project): JsonResponse
    {
        Gate::authorize('create', [AutomationRule::class, $project]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'trigger_event' => 'required|string',
            'conditions' => 'nullable|array',
            'actions' => 'nullable|array',
            'priority' => 'integer|min:0',
        ]);

        $rule = $project->automationRules()->create($validated);

        return response()->json($rule);
    }

    public function update(Request $request, Workspace $workspace, Project $project, AutomationRule $rule): JsonResponse
    {
        Gate::authorize('update', $rule);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'enabled' => 'sometimes|boolean',
            'trigger_event' => 'sometimes|string',
            'conditions' => 'nullable|array',
            'actions' => 'nullable|array',
            'priority' => 'integer|min:0',
        ]);

        $rule->update($validated);

        return response()->json($rule);
    }

    public function destroy(Workspace $workspace, Project $project, AutomationRule $rule): JsonResponse
    {
        Gate::authorize('delete', $rule);

        $rule->delete();

        return response()->json(['message' => 'Rule deleted.']);
    }

    public function test(Workspace $workspace, Project $project, AutomationRule $rule): JsonResponse
    {
        Gate::authorize('view', $rule);

        $tasks = $project->tasks()
            ->whereNull('archived_at')
            ->get();

        $matchingTasks = $tasks->filter(function ($task) use ($rule) {
            $engine = app(AutomationEngine::class);

            return $engine->conditionsMatch($task, $rule->conditions ?? [], []);
        })->map(fn ($task) => [
            'id' => $task->id,
            'code' => $task->code,
            'title' => $task->title,
            'status' => $task->status,
        ]);

        return response()->json([
            'matching_tasks' => $matchingTasks->values(),
            'total_tasks' => $tasks->count(),
            'matching_count' => $matchingTasks->count(),
        ]);
    }
}
