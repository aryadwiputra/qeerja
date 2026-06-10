<?php

use App\Models\User;

test('workspace owner can create priority', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceMember($owner, 'owner');

    $this->actingAs($owner)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('workspaces.priorities.store', $workspace), [
            'name' => 'Critical',
            'level' => 5,
            'color' => '#ef4444',
        ])
        ->assertRedirect();

    expect($workspace->priorities()->where('name', 'Critical')->exists())->toBeTrue();
});

test('workspace admin can create priority', function () {
    $admin = User::factory()->create();
    $workspace = createWorkspaceMember($admin, 'admin');

    $this->actingAs($admin)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('workspaces.priorities.store', $workspace), [
            'name' => 'Critical',
            'level' => 4,
        ])
        ->assertRedirect();
});

test('workspace viewer cannot create priority', function () {
    $viewer = User::factory()->create();
    $workspace = createWorkspaceMember($viewer, 'viewer');

    $this->actingAs($viewer)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('workspaces.priorities.store', $workspace), [
            'name' => 'Critical',
            'level' => 5,
        ])
        ->assertForbidden();
});

test('workspace owner can update priority', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceMember($owner, 'owner');
    $priority = $workspace->priorities()->where('key', 'urgent')->first();

    $this->actingAs($owner)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->put(route('workspaces.priorities.update', [$workspace, $priority]), [
            'name' => 'Blocker',
            'level' => 6,
            'color' => '#dc2626',
        ])
        ->assertRedirect();

    $priority->refresh();
    expect($priority->name)->toBe('Blocker');
    expect($priority->level)->toBe(6);
});

test('workspace owner can delete priority', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceMember($owner, 'owner');
    $priority = $workspace->priorities()->create([
        'name' => 'Custom Prio',
        'key' => 'custom-prio',
        'level' => 10,
    ]);

    $this->actingAs($owner)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->delete(route('workspaces.priorities.destroy', [$workspace, $priority]))
        ->assertRedirect();

    expect($workspace->priorities()->where('id', $priority->id)->exists())->toBeFalse();
});

test('priority validation requires name and level', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceMember($owner, 'owner');

    $this->actingAs($owner)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('workspaces.priorities.store', $workspace), [
            'name' => '',
        ])
        ->assertSessionHasErrors(['name', 'level']);
});
