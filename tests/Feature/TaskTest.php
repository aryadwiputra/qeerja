<?php

use App\Models\Label;
use App\Models\User;

test('project members can create tasks when workspace role permits it', function () {
    $member = User::factory()->create();
    $workspace = createWorkspaceMember($member, 'member');
    $project = createProjectForWorkspace($workspace, $member, 'member');
    $taskType = $workspace->taskTypes()->first();
    $priority = $workspace->priorities()->first();

    $this->actingAs($member)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.tasks.store', [$workspace, $project]), [
            'title' => 'Create onboarding flow',
            'task_type_id' => $taskType->id,
            'priority_id' => $priority->id,
        ])
        ->assertRedirect();

    expect($project->tasks()->where('title', 'Create onboarding flow')->exists())->toBeTrue();
});

test('project viewers cannot create tasks', function () {
    $viewer = User::factory()->create();
    $workspace = createWorkspaceMember($viewer, 'viewer');
    $project = createProjectForWorkspace($workspace, $viewer, 'viewer');
    $taskType = $workspace->taskTypes()->first();

    $this->actingAs($viewer)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.tasks.store', [$workspace, $project]), [
            'title' => 'Blocked task',
            'task_type_id' => $taskType->id,
        ])
        ->assertForbidden();
});

test('developer project role restricts task deletion', function () {
    $developer = User::factory()->create();
    $workspace = createWorkspaceMember($developer, 'manager');
    $project = createProjectForWorkspace($workspace, $developer, 'developer');
    $task = createTaskForProject($project, $developer);

    $this->actingAs($developer)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->delete(route('projects.tasks.destroy', [$workspace, $project, $task]))
        ->assertForbidden();
});

test('authorized users can update task status assignees and labels', function () {
    $developer = User::factory()->create();
    $assignee = User::factory()->create();
    $workspace = createWorkspaceMember($developer, 'manager');
    $workspace->members()->create([
        'user_id' => $assignee->id,
        'role' => 'member',
        'status' => 'active',
    ]);
    $project = createProjectForWorkspace($workspace, $developer, 'developer');
    $project->members()->create([
        'user_id' => $assignee->id,
        'role' => 'developer',
    ]);
    $task = createTaskForProject($project, $developer);
    $doneColumn = $project->boards()->first()->columns()->where('status_key', 'done')->first();
    $label = Label::create([
        'workspace_id' => $workspace->id,
        'project_id' => $project->id,
        'name' => 'Bug',
        'slug' => 'bug',
        'color' => '#ef4444',
    ]);

    $this->actingAs($developer)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->patch(route('projects.tasks.update', [$workspace, $project, $task]), [
            'title' => 'Updated task title',
            'board_column_id' => $doneColumn->id,
            'assignee_ids' => [$assignee->id],
            'label_ids' => [$label->id],
        ])
        ->assertRedirect();

    $task->refresh();

    expect($task->title)->toBe('Updated task title')
        ->and($task->board_column_id)->toBe($doneColumn->id)
        ->and($task->status)->toBe('done')
        ->and($task->assignees()->pluck('users.id')->all())->toBe([$assignee->id])
        ->and($task->labels()->pluck('labels.id')->all())->toBe([$label->id]);
});

test('authorized users can assign epics and sprints to tasks', function () {
    $developer = User::factory()->create();
    $workspace = createWorkspaceMember($developer, 'manager');
    $project = createProjectForWorkspace($workspace, $developer, 'developer');
    $task = createTaskForProject($project, $developer);
    $epic = $project->epics()->create([
        'name' => 'Onboarding',
        'status' => 'active',
        'color' => '#2563eb',
    ]);
    $sprint = $project->sprints()->create([
        'name' => 'Sprint 1',
        'status' => 'active',
    ]);

    $this->actingAs($developer)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->patch(route('projects.tasks.update', [$workspace, $project, $task]), [
            'epic_ids' => [$epic->id],
            'sprint_ids' => [$sprint->id],
        ])
        ->assertRedirect();

    expect($task->epics()->pluck('epics.id')->all())->toBe([$epic->id])
        ->and($task->sprints()->pluck('sprints.id')->all())->toBe([$sprint->id])
        ->and($task->activities()->where('action', 'epic_changed')->exists())->toBeTrue()
        ->and($task->activities()->where('action', 'sprint_changed')->exists())->toBeTrue();
});
