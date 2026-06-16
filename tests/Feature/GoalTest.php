<?php

use App\Models\Epic;
use App\Models\Goal;
use App\Models\KeyResult;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
    WorkspaceMember::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'role' => 'admin',
        'status' => 'active',
    ]);
    $this->actingAs($this->user);
});

test('workspace members can view goals index', function () {
    Goal::factory()->count(3)->create(['workspace_id' => $this->workspace->id]);

    $response = $this->get("/workspaces/{$this->workspace->slug}/goals");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('workspaces/goals/index'));
});

test('workspace members can view goal detail', function () {
    $goal = Goal::factory()->create(['workspace_id' => $this->workspace->id]);
    KeyResult::factory()->count(2)->create(['goal_id' => $goal->id]);

    $response = $this->get("/workspaces/{$this->workspace->slug}/goals/{$goal->id}");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('workspaces/goals/show'));
});

test('workspace members can create goal', function () {
    $response = $this->postJson("/workspaces/{$this->workspace->slug}/goals", [
        'title' => 'Improve user engagement',
        'description' => 'Increase daily active users by 50%',
        'target_date' => now()->addMonths(3)->format('Y-m-d'),
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('goals', [
        'workspace_id' => $this->workspace->id,
        'title' => 'Improve user engagement',
    ]);
});

test('workspace members can update goal', function () {
    $goal = Goal::factory()->create(['workspace_id' => $this->workspace->id]);

    $response = $this->putJson("/workspaces/{$this->workspace->slug}/goals/{$goal->id}", [
        'title' => 'Updated goal title',
        'status' => 'completed',
    ]);

    $response->assertStatus(200);
    $goal->refresh();
    $this->assertEquals('Updated goal title', $goal->title);
    $this->assertEquals('completed', $goal->status);
});

test('workspace members can delete goal', function () {
    $goal = Goal::factory()->create(['workspace_id' => $this->workspace->id]);

    $response = $this->deleteJson("/workspaces/{$this->workspace->slug}/goals/{$goal->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('goals', ['id' => $goal->id]);
});

test('workspace members can add key result to goal', function () {
    $goal = Goal::factory()->create(['workspace_id' => $this->workspace->id]);

    $response = $this->postJson("/workspaces/{$this->workspace->slug}/goals/{$goal->id}/key-results", [
        'title' => 'Increase daily active users',
        'target_value' => 1000,
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('key_results', [
        'goal_id' => $goal->id,
        'title' => 'Increase daily active users',
    ]);
});

test('workspace members can update key result', function () {
    $goal = Goal::factory()->create(['workspace_id' => $this->workspace->id]);
    $keyResult = KeyResult::factory()->create(['goal_id' => $goal->id, 'target_value' => 100]);

    $response = $this->putJson("/workspaces/{$this->workspace->slug}/goals/{$goal->id}/key-results/{$keyResult->id}", [
        'current_value' => 50,
        'status' => 'in_progress',
    ]);

    $response->assertStatus(200);
    $keyResult->refresh();
    $this->assertEquals(50, $keyResult->current_value);
    $this->assertEquals('in_progress', $keyResult->status);
});

test('workspace members can delete key result', function () {
    $goal = Goal::factory()->create(['workspace_id' => $this->workspace->id]);
    $keyResult = KeyResult::factory()->create(['goal_id' => $goal->id]);

    $response = $this->deleteJson("/workspaces/{$this->workspace->slug}/goals/{$goal->id}/key-results/{$keyResult->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('key_results', ['id' => $keyResult->id]);
});

test('workspace members can link epic to goal', function () {
    $project = Project::factory()->create(['workspace_id' => $this->workspace->id]);
    $goal = Goal::factory()->create(['workspace_id' => $this->workspace->id]);
    $epic = Epic::factory()->create(['project_id' => $project->id]);

    $response = $this->postJson("/workspaces/{$this->workspace->slug}/goals/{$goal->id}/epics", [
        'epic_id' => $epic->id,
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('epic_goals', [
        'goal_id' => $goal->id,
        'epic_id' => $epic->id,
    ]);
});

test('workspace members can unlink epic from goal', function () {
    $project = Project::factory()->create(['workspace_id' => $this->workspace->id]);
    $goal = Goal::factory()->create(['workspace_id' => $this->workspace->id]);
    $epic = Epic::factory()->create(['project_id' => $project->id]);
    $goal->epics()->attach($epic);

    $response = $this->deleteJson("/workspaces/{$this->workspace->slug}/goals/{$goal->id}/epics/{$epic->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('epic_goals', [
        'goal_id' => $goal->id,
        'epic_id' => $epic->id,
    ]);
});
