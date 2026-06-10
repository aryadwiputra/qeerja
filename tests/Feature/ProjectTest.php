<?php

use App\Models\Project;
use App\Models\User;

test('workspace managers can create projects', function () {
    $manager = User::factory()->create();
    $workspace = createWorkspaceMember($manager, 'manager');

    $this->actingAs($manager)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.store', $workspace), [
            'name' => 'Mobile App',
            'key' => 'MOB',
            'slug' => 'mobile-app',
            'description' => 'Mobile work.',
            'visibility' => 'private',
            'color' => '#2563EB',
        ])
        ->assertRedirect();

    expect(Project::where('slug', 'mobile-app')->exists())->toBeTrue();
});

test('workspace members cannot create projects', function () {
    $member = User::factory()->create();
    $workspace = createWorkspaceMember($member, 'member');

    $this->actingAs($member)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.store', $workspace), [
            'name' => 'Blocked Project',
            'key' => 'BLK',
            'slug' => 'blocked-project',
        ])
        ->assertForbidden();
});
