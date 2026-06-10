<?php

use App\Models\User;

test('workspace owner can create task type', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceMember($owner, 'owner');

    $this->actingAs($owner)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('workspaces.task-types.store', $workspace), [
            'name' => 'Custom Type',
            'color' => '#ef4444',
        ])
        ->assertRedirect();

    expect($workspace->taskTypes()->where('name', 'Custom Type')->exists())->toBeTrue();
});

test('workspace admin can create task type', function () {
    $admin = User::factory()->create();
    $workspace = createWorkspaceMember($admin, 'admin');

    $this->actingAs($admin)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('workspaces.task-types.store', $workspace), [
            'name' => 'Custom Epic',
            'color' => '#8b5cf6',
        ])
        ->assertRedirect();
});

test('workspace viewer cannot create task type', function () {
    $viewer = User::factory()->create();
    $workspace = createWorkspaceMember($viewer, 'viewer');

    $this->actingAs($viewer)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('workspaces.task-types.store', $workspace), [
            'name' => 'Subtask',
        ])
        ->assertForbidden();
});

test('workspace owner can update task type', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceMember($owner, 'owner');
    $type = $workspace->taskTypes()->where('key', 'bug')->first();

    $this->actingAs($owner)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->put(route('workspaces.task-types.update', [$workspace, $type]), [
            'name' => 'Bug Report',
            'color' => '#dc2626',
        ])
        ->assertRedirect();

    $type->refresh();
    expect($type->name)->toBe('Bug Report');
    expect($type->color)->toBe('#dc2626');
});

test('workspace owner can delete task type', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceMember($owner, 'owner');
    $type = $workspace->taskTypes()->create([
        'name' => 'Custom',
        'key' => 'custom-type',
    ]);

    $this->actingAs($owner)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->delete(route('workspaces.task-types.destroy', [$workspace, $type]))
        ->assertRedirect();

    expect($workspace->taskTypes()->where('id', $type->id)->exists())->toBeFalse();
});

test('task type validation requires name', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceMember($owner, 'owner');

    $this->actingAs($owner)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('workspaces.task-types.store', $workspace), [
            'name' => '',
        ])
        ->assertSessionHasErrors('name');
});
