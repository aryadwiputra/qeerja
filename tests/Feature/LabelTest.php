<?php

use App\Models\Label;
use App\Models\User;

test('project managers can create labels', function () {
    $manager = User::factory()->create();
    $workspace = createWorkspaceMember($manager, 'manager');
    $project = createProjectForWorkspace($workspace, $manager, 'manager');

    $this->actingAs($manager)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.labels.store', [$workspace, $project]), [
            'name' => 'Bug',
            'color' => '#ef4444',
        ])
        ->assertRedirect();

    expect($project->labels()->where('slug', 'bug')->exists())->toBeTrue();
});

test('project viewers cannot manage labels', function () {
    $viewer = User::factory()->create();
    $workspace = createWorkspaceMember($viewer, 'manager');
    $project = createProjectForWorkspace($workspace, $viewer, 'viewer');

    $this->actingAs($viewer)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.labels.store', [$workspace, $project]), [
            'name' => 'Blocked',
        ])
        ->assertForbidden();
});

test('project managers can update and delete labels attached to tasks', function () {
    $manager = User::factory()->create();
    $workspace = createWorkspaceMember($manager, 'manager');
    $project = createProjectForWorkspace($workspace, $manager, 'manager');
    $task = createTaskForProject($project, $manager);
    $label = Label::create([
        'workspace_id' => $workspace->id,
        'project_id' => $project->id,
        'name' => 'Old label',
        'slug' => 'old-label',
        'color' => '#64748b',
    ]);
    $task->labels()->attach($label->id);

    $this->actingAs($manager)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->put(route('projects.labels.update', [$workspace, $project, $label]), [
            'name' => 'Needs design',
            'color' => '#8b5cf6',
        ])
        ->assertRedirect();

    expect($label->refresh()->slug)->toBe('needs-design')
        ->and($label->color)->toBe('#8b5cf6');

    $this->actingAs($manager)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->delete(route('projects.labels.destroy', [$workspace, $project, $label]))
        ->assertRedirect();

    expect(Label::whereKey($label->id)->exists())->toBeFalse()
        ->and($task->labels()->count())->toBe(0);
});
