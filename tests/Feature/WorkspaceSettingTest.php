<?php

use App\Models\User;

test('workspace owner can update settings', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceMember($owner, 'owner');

    $this->actingAs($owner)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->put(route('workspaces.settings.update', $workspace), [
            'default_locale' => 'id',
            'default_timezone' => 'Asia/Makassar',
            'auto_watch_own_tasks' => false,
        ])
        ->assertRedirect();

    $setting = $workspace->settings()->where('key', 'default_locale')->first();
    expect($setting->value['value'])->toBe('id');

    $timezone = $workspace->settings()->where('key', 'default_timezone')->first();
    expect($timezone->value['value'])->toBe('Asia/Makassar');
});

test('workspace admin can update settings', function () {
    $admin = User::factory()->create();
    $workspace = createWorkspaceMember($admin, 'admin');

    $this->actingAs($admin)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->put(route('workspaces.settings.update', $workspace), [
            'default_locale' => 'id',
        ])
        ->assertRedirect();
});

test('workspace viewer cannot update settings', function () {
    $viewer = User::factory()->create();
    $workspace = createWorkspaceMember($viewer, 'viewer');

    $this->actingAs($viewer)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->put(route('workspaces.settings.update', $workspace), [
            'default_locale' => 'id',
        ])
        ->assertForbidden();
});

test('workspace settings validation rejects invalid locale', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceMember($owner, 'owner');

    $this->actingAs($owner)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->put(route('workspaces.settings.update', $workspace), [
            'default_locale' => 'fr',
        ])
        ->assertSessionHasErrors('default_locale');
});
