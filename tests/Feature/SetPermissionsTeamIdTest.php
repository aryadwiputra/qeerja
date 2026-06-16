<?php

use App\Models\Project;
use App\Models\User;

test('user can access workspace project without current_workspace_id in session', function () {
    $user = User::factory()->create();
    $workspace = createWorkspaceMember($user, 'owner');
    $project = Project::factory()->create([
        'workspace_id' => $workspace->id,
        'created_by' => $user->id,
        'visibility' => 'workspace',
    ]);

    $this->actingAs($user)
        ->get(route('projects.show', [$workspace, $project]))
        ->assertOk();
});

test('user can access workspace without current_workspace_id in session', function () {
    $user = User::factory()->create();
    $workspace = createWorkspaceMember($user, 'member');

    $this->actingAs($user)
        ->get(route('workspaces.show', $workspace))
        ->assertOk();
});

test('user with explicit session workspace uses that workspace', function () {
    $user = User::factory()->create();
    $workspace1 = createWorkspaceMember($user, 'owner');
    $workspace2 = createWorkspaceMember($user, 'member');

    $project = Project::factory()->create([
        'workspace_id' => $workspace2->id,
        'created_by' => $user->id,
        'visibility' => 'workspace',
    ]);

    $this->actingAs($user)
        ->withSession(['current_workspace_id' => $workspace2->id])
        ->get(route('projects.show', [$workspace2, $project]))
        ->assertOk();
});
