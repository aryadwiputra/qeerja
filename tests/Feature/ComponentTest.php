<?php

use App\Models\Component;
use App\Models\User;

test('project managers can manage components', function () {
    $manager = User::factory()->create();
    $workspace = createWorkspaceMember($manager, 'manager');
    $project = createProjectForWorkspace($workspace, $manager, 'manager');

    $this->actingAs($manager)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.components.store', [$workspace, $project]), [
            'name' => 'Frontend',
            'description' => 'React components',
        ])
        ->assertRedirect();

    $component = $project->components()->where('name', 'Frontend')->first();
    expect($component)->not->toBeNull()
        ->and($component->description)->toBe('React components');

    $this->actingAs($manager)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->put(route('projects.components.update', [$workspace, $project, $component]), [
            'name' => 'Frontend Updated',
            'description' => 'Updated desc',
        ])
        ->assertRedirect();

    expect($component->refresh()->name)->toBe('Frontend Updated');

    $this->actingAs($manager)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->delete(route('projects.components.destroy', [$workspace, $project, $component]))
        ->assertRedirect();

    expect(Component::whereKey($component->id)->exists())->toBeFalse();
});

test('project managers can add and remove tasks from components', function () {
    $manager = User::factory()->create();
    $workspace = createWorkspaceMember($manager, 'manager');
    $project = createProjectForWorkspace($workspace, $manager, 'manager');
    $task = createTaskForProject($project, $manager);
    $component = Component::create([
        'project_id' => $project->id,
        'name' => 'Backend',
    ]);

    $this->actingAs($manager)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.components.add-task', [$workspace, $project, $component]), [
            'task_id' => $task->id,
        ])
        ->assertRedirect();

    expect($component->tasks()->where('task_id', $task->id)->exists())->toBeTrue();

    $this->actingAs($manager)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->delete(route('projects.components.remove-task', [$workspace, $project, $component]), [
            'task_id' => $task->id,
        ])
        ->assertRedirect();

    expect($component->tasks()->where('task_id', $task->id)->exists())->toBeFalse();
});

test('project viewers cannot manage components', function () {
    $viewer = User::factory()->create();
    $workspace = createWorkspaceMember($viewer, 'manager');
    $project = createProjectForWorkspace($workspace, $viewer, 'viewer');

    $this->actingAs($viewer)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.components.store', [$workspace, $project]), [
            'name' => 'Blocked',
        ])
        ->assertForbidden();
});

test('project members can view components page', function () {
    $manager = User::factory()->create();
    $workspace = createWorkspaceMember($manager, 'manager');
    $project = createProjectForWorkspace($workspace, $manager, 'manager');

    $this->actingAs($manager)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->get(route('projects.components.index', [$workspace, $project]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('projects/components/index'));
});
