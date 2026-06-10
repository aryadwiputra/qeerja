<?php

use App\Models\User;

test('authenticated users can create a workspace and become owner', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('workspaces.store'), [
            'name' => 'Engineering',
            'slug' => 'engineering',
            'description' => 'Product engineering workspace.',
        ])
        ->assertRedirect(route('workspaces.settings', 'engineering'));

    $workspace = $user->workspaces()->where('slug', 'engineering')->first();

    expect($workspace)->not->toBeNull();
    expect($workspace->pivot->role)->toBe('owner');
});

test('workspace viewers cannot update workspace settings', function () {
    $viewer = User::factory()->create();
    $workspace = createWorkspaceMember($viewer, 'viewer');

    $this->actingAs($viewer)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->put(route('workspaces.update', $workspace), [
            'name' => 'Blocked update',
            'slug' => $workspace->slug,
            'description' => 'Nope.',
        ])
        ->assertForbidden();
});
