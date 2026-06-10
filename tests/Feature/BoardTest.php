<?php

use App\Models\User;

test('project viewers can view a board', function () {
    $viewer = User::factory()->create();
    $workspace = createWorkspaceMember($viewer, 'viewer');
    $project = createProjectForWorkspace($workspace, $viewer, 'viewer');

    $this->actingAs($viewer)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->get(route('projects.board', [$workspace, $project]))
        ->assertOk();
});

test('users outside a workspace cannot view its board', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceMember($owner, 'owner');
    $project = createProjectForWorkspace($workspace, $owner);
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->get(route('projects.board', [$workspace, $project]))
        ->assertForbidden();
});
