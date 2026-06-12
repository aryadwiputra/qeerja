<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

test('project members can view reports', function () {
    $member = User::factory()->create();
    $workspace = createWorkspaceMember($member, 'manager');
    $project = createProjectForWorkspace($workspace, $member, 'manager');

    actingAs($member)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->getJson(route('projects.reports.index', [$workspace, $project]))
        ->assertOk();
});

test('reports returns summary stats', function () {
    $user = User::factory()->create();
    $workspace = createWorkspaceMember($user, 'manager');
    $project = createProjectForWorkspace($workspace, $user, 'manager');

    $task = createTaskForProject($project, $user);
    $task2 = createTaskForProject($project, $user);
    $task2->update(['completed_at' => now()]);

    actingAs($user)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->getJson(route('projects.reports.index', [$workspace, $project]))
        ->assertJsonStructure([
            'summary' => ['total', 'completed', 'overdue', 'no_due_date', 'completion_rate', 'by_status'],
            'assignee_workload',
            'burndown',
        ])
        ->assertJsonPath('summary.total', 2)
        ->assertJsonPath('summary.completed', 1);
});

test('reports returns overdue count', function () {
    $user = User::factory()->create();
    $workspace = createWorkspaceMember($user, 'manager');
    $project = createProjectForWorkspace($workspace, $user, 'manager');

    $task = createTaskForProject($project, $user);
    $task->update(['due_date' => now()->subDay(), 'completed_at' => null]);

    actingAs($user)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->getJson(route('projects.reports.index', [$workspace, $project]))
        ->assertJsonPath('summary.overdue', 1);
});

test('reports returns assignee workload', function () {
    $user = User::factory()->create();
    $workspace = createWorkspaceMember($user, 'manager');
    $project = createProjectForWorkspace($workspace, $user, 'manager');

    createTaskForProject($project, $user);

    actingAs($user)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->getJson(route('projects.reports.index', [$workspace, $project]))
        ->assertJsonStructure([
            'assignee_workload' => [
                '*' => ['name', 'avatar', 'total', 'completed'],
            ],
        ]);
});

test('reports returns burndown for active sprint', function () {
    $user = User::factory()->create();
    $workspace = createWorkspaceMember($user, 'manager');
    $project = createProjectForWorkspace($workspace, $user, 'manager');

    $sprint = $project->sprints()->create([
        'name' => 'Sprint 1',
        'status' => 'active',
        'start_date' => now()->subDays(5)->format('Y-m-d'),
        'end_date' => now()->addDays(9)->format('Y-m-d'),
    ]);

    $task = createTaskForProject($project, $user);
    $task->sprints()->attach($sprint);
    $task->update(['completed_at' => now()]);

    actingAs($user)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->getJson(route('projects.reports.index', [$workspace, $project]))
        ->assertJsonStructure([
            'burndown' => ['sprint' => ['name', 'start_date', 'end_date'], 'data'],
        ])
        ->assertJsonPath('burndown.sprint.name', 'Sprint 1');
});

test('guests cannot view reports', function () {
    $user = User::factory()->create();
    $workspace = createWorkspaceMember($user, 'manager');
    $project = createProjectForWorkspace($workspace, $user, 'manager');

    $this->get(route('projects.reports.index', [$workspace, $project]))
        ->assertRedirect(route('login'));
});
