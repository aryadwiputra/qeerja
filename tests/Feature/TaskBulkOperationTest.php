<?php

use App\Models\Label;
use App\Models\Task;
use App\Models\User;

test('project managers can move selected tasks to a column', function () {
    $manager = User::factory()->create();
    $workspace = createWorkspaceMember($manager, 'manager');
    $project = createProjectForWorkspace($workspace, $manager, 'manager');
    $tasks = collect([createTaskForProject($project, $manager), createTaskForProject($project, $manager)]);
    $doneColumn = $project->boards()->first()->columns()->where('status_key', 'done')->first();

    $this->actingAs($manager)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.tasks.bulk', [$workspace, $project]), [
            'task_ids' => $tasks->pluck('id')->all(),
            'operation' => 'move_column',
            'board_column_id' => $doneColumn->id,
        ])
        ->assertRedirect();

    expect(Task::whereKey($tasks->pluck('id'))->pluck('board_column_id')->unique()->all())->toBe([$doneColumn->id])
        ->and(Task::whereKey($tasks->pluck('id'))->pluck('status')->unique()->all())->toBe(['done'])
        ->and($tasks->first()->activities()->where('action', 'status_changed')->exists())->toBeTrue();
});

test('project managers can replace assignees on selected tasks', function () {
    $manager = User::factory()->create();
    $assignee = User::factory()->create();
    $workspace = createWorkspaceMember($manager, 'manager');
    $project = createProjectForWorkspace($workspace, $manager, 'manager');
    $workspace->members()->create(['user_id' => $assignee->id, 'role' => 'member', 'status' => 'active']);
    $project->members()->create(['user_id' => $assignee->id, 'role' => 'developer']);
    $task = createTaskForProject($project, $manager);

    $this->actingAs($manager)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.tasks.bulk', [$workspace, $project]), [
            'task_ids' => [$task->id],
            'operation' => 'assignees',
            'assignee_mode' => 'replace',
            'assignee_ids' => [$assignee->id],
        ])
        ->assertRedirect();

    expect($task->assignees()->pluck('users.id')->all())->toBe([$assignee->id])
        ->and($task->activities()->where('action', 'assigned')->exists())->toBeTrue();
});

test('project managers can clear priority on selected tasks', function () {
    $manager = User::factory()->create();
    $workspace = createWorkspaceMember($manager, 'manager');
    $project = createProjectForWorkspace($workspace, $manager, 'manager');
    $task = createTaskForProject($project, $manager);

    $this->actingAs($manager)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.tasks.bulk', [$workspace, $project]), [
            'task_ids' => [$task->id],
            'operation' => 'priority',
            'priority_id' => null,
        ])
        ->assertRedirect();

    expect($task->refresh()->priority_id)->toBeNull()
        ->and($task->activities()->where('action', 'priority_changed')->exists())->toBeTrue();
});

test('project managers can add labels to selected tasks', function () {
    $manager = User::factory()->create();
    $workspace = createWorkspaceMember($manager, 'manager');
    $project = createProjectForWorkspace($workspace, $manager, 'manager');
    $task = createTaskForProject($project, $manager);
    $label = Label::factory()->create([
        'workspace_id' => $workspace->id,
        'project_id' => $project->id,
    ]);

    $this->actingAs($manager)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.tasks.bulk', [$workspace, $project]), [
            'task_ids' => [$task->id],
            'operation' => 'labels',
            'label_mode' => 'add',
            'label_ids' => [$label->id],
        ])
        ->assertRedirect();

    expect($task->labels()->pluck('labels.id')->all())->toBe([$label->id])
        ->and($task->activities()->where('action', 'labels_changed')->exists())->toBeTrue();
});

test('project managers can archive selected tasks', function () {
    $manager = User::factory()->create();
    $workspace = createWorkspaceMember($manager, 'manager');
    $project = createProjectForWorkspace($workspace, $manager, 'manager');
    $task = createTaskForProject($project, $manager);

    $this->actingAs($manager)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.tasks.bulk', [$workspace, $project]), [
            'task_ids' => [$task->id],
            'operation' => 'archive',
        ])
        ->assertRedirect();

    expect($task->refresh()->archived_at)->not->toBeNull()
        ->and($task->activities()->where('action', 'archived')->exists())->toBeTrue();
});

test('project managers can delete selected tasks', function () {
    $manager = User::factory()->create();
    $workspace = createWorkspaceMember($manager, 'manager');
    $project = createProjectForWorkspace($workspace, $manager, 'manager');
    $task = createTaskForProject($project, $manager);

    $this->actingAs($manager)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.tasks.bulk', [$workspace, $project]), [
            'task_ids' => [$task->id],
            'operation' => 'delete',
        ])
        ->assertRedirect();

    expect(Task::find($task->id))->toBeNull()
        ->and(Task::withTrashed()->find($task->id)?->trashed())->toBeTrue();
});

test('viewers cannot bulk update tasks', function () {
    $viewer = User::factory()->create();
    $workspace = createWorkspaceMember($viewer, 'manager');
    $project = createProjectForWorkspace($workspace, $viewer, 'viewer');
    $task = createTaskForProject($project, $viewer);

    $this->actingAs($viewer)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.tasks.bulk', [$workspace, $project]), [
            'task_ids' => [$task->id],
            'operation' => 'archive',
        ])
        ->assertForbidden();
});

test('bulk operations reject tasks from another project', function () {
    $manager = User::factory()->create();
    $workspace = createWorkspaceMember($manager, 'manager');
    $project = createProjectForWorkspace($workspace, $manager, 'manager');
    $otherProject = createProjectForWorkspace($workspace, $manager, 'manager');
    $task = createTaskForProject($otherProject, $manager);

    $this->actingAs($manager)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.tasks.bulk', [$workspace, $project]), [
            'task_ids' => [$task->id],
            'operation' => 'archive',
        ])
        ->assertSessionHasErrors('task_ids.0');
});

test('bulk operations are limited to one hundred tasks', function () {
    $manager = User::factory()->create();
    $workspace = createWorkspaceMember($manager, 'manager');
    $project = createProjectForWorkspace($workspace, $manager, 'manager');
    $taskIds = collect(range(1, 101))->map(fn () => createTaskForProject($project, $manager)->id)->all();

    $this->actingAs($manager)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.tasks.bulk', [$workspace, $project]), [
            'task_ids' => $taskIds,
            'operation' => 'archive',
        ])
        ->assertSessionHasErrors('task_ids');
});
