<?php

use App\Models\User;

test('project lead can update settings', function () {
    $lead = User::factory()->create();
    $workspace = createWorkspaceMember($lead, 'manager');
    $project = createProjectForWorkspace($workspace, $lead, 'lead');
    $board = $project->boards()->first();

    $this->actingAs($lead)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->put(route('projects.settings.update', [$workspace, $project]), [
            'default_board_id' => $board->id,
            'auto_assign_reporter' => true,
        ])
        ->assertRedirect();

    $setting = $project->settings()->where('key', 'default_board_id')->first();
    expect((int) $setting->value['value'])->toBe($board->id);

    $autoAssign = $project->settings()->where('key', 'auto_assign_reporter')->first();
    expect($autoAssign->value['value'])->toBeTrue();
});

test('project manager can update settings', function () {
    $manager = User::factory()->create();
    $workspace = createWorkspaceMember($manager, 'manager');
    $project = createProjectForWorkspace($workspace, $manager, 'manager');
    $board = $project->boards()->first();

    $this->actingAs($manager)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->put(route('projects.settings.update', [$workspace, $project]), [
            'default_board_id' => $board->id,
        ])
        ->assertRedirect();
});

test('project viewer cannot update settings', function () {
    $viewer = User::factory()->create();
    $workspace = createWorkspaceMember($viewer, 'viewer');
    $project = createProjectForWorkspace($workspace, $viewer, 'viewer');
    $board = $project->boards()->first();

    $this->actingAs($viewer)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->put(route('projects.settings.update', [$workspace, $project]), [
            'default_board_id' => $board->id,
        ])
        ->assertForbidden();
});
