<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('onboarding'))
        ->assertRedirect(route('login'));
});

test('authenticated users with workspace are redirected to dashboard', function () {
    $user = User::factory()->create();
    $workspace = createWorkspaceMember($user, 'owner');

    $this->actingAs($user)
        ->get(route('onboarding'))
        ->assertRedirect(route('dashboard'));
});

test('authenticated users without workspace can visit onboarding', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('onboarding'))
        ->assertOk();
});

test('onboarding page renders with correct props', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('onboarding'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('onboarding/index')
        ->has('hasWorkspace')
        ->has('hasProject')
        ->has('currentWorkspace')
    );
});

test('authenticated users with workspace can visit onboarding when onboarding_workspace_id exists', function () {
    $user = User::factory()->create();
    $workspace = createWorkspaceMember($user, 'owner');

    $response = $this->actingAs($user)
        ->withSession(['onboarding_workspace_id' => $workspace->id])
        ->get(route('onboarding'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('onboarding/index')
        ->where('hasWorkspace', true)
        ->where('currentWorkspace.slug', $workspace->slug)
    );
});
