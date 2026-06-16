<?php

use App\Models\User;

test('project members can view workload page', function () {
    $manager = User::factory()->create();
    $workspace = createWorkspaceMember($manager, 'manager');
    $project = createProjectForWorkspace($workspace, $manager, 'manager');

    $this->actingAs($manager)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->get(route('projects.workload.index', [$workspace, $project]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('projects/workload'));
});

test('project managers can update member capacity', function () {
    $manager = User::factory()->create();
    $workspace = createWorkspaceMember($manager, 'manager');
    $project = createProjectForWorkspace($workspace, $manager, 'manager');

    $this->actingAs($manager)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->put(route('projects.workload.update-capacity', [$workspace, $project]), [
            'user_id' => $manager->id,
            'capacity_hours' => 40,
        ])
        ->assertRedirect();

    $member = $project->members()->where('user_id', $manager->id)->first();
    expect($member->capacity_hours)->toBe(40);
});

test('project viewers cannot update capacity', function () {
    $viewer = User::factory()->create();
    $workspace = createWorkspaceMember($viewer, 'manager');
    $project = createProjectForWorkspace($workspace, $viewer, 'viewer');

    $this->actingAs($viewer)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->put(route('projects.workload.update-capacity', [$workspace, $project]), [
            'user_id' => $viewer->id,
            'capacity_hours' => 40,
        ])
        ->assertForbidden();
});
