<?php

use App\Jobs\ProcessGitHubWebhookJob;
use App\Models\Integration;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config()->set('services.github.webhook_secret', 'test-secret-123');
});

test('webhook request with invalid signature returns 401', function () {
    $workspace = createWorkspaceMember(User::factory()->create(), 'owner');
    $project = createProjectForWorkspace($workspace, $workspace->members()->first()->user);

    $this->postJson(
        route('projects.github.webhook', [$workspace, $project]),
        ['commits' => []],
        ['X-Hub-Signature-256' => 'sha256=bad'],
    )
        ->assertUnauthorized()
        ->assertJson(['error' => 'Invalid signature']);
});

test('webhook request with valid signature dispatches job', function () {
    Queue::fake();

    $user = User::factory()->create();
    $workspace = createWorkspaceMember($user, 'owner');
    $project = createProjectForWorkspace($workspace, $user);
    $payload = ['commits' => []];
    $signature = 'sha256='.hash_hmac('sha256', json_encode($payload), 'test-secret-123');

    $this->postJson(
        route('projects.github.webhook', [$workspace, $project]),
        $payload,
        ['X-Hub-Signature-256' => $signature, 'X-GitHub-Event' => 'push'],
    )
        ->assertOk()
        ->assertJson(['status' => 'accepted']);

    Queue::assertPushed(ProcessGitHubWebhookJob::class);
});

test('push event with matching task code adds comment and activity', function () {
    $user = User::factory()->create();
    $workspace = createWorkspaceMember($user, 'owner');
    $project = createProjectForWorkspace($workspace, $user);
    $task = createTaskForProject($project, $user);

    $job = new ProcessGitHubWebhookJob(
        $workspace->id,
        $project->id,
        'push',
        [
            'ref' => 'refs/heads/main',
            'commits' => [
                [
                    'id' => 'abc123def456',
                    'message' => "Implement feature {$task->code}",
                    'url' => 'https://github.com/owner/repo/commit/abc123def456',
                ],
            ],
            'repository' => ['full_name' => 'owner/repo', 'default_branch' => 'main'],
            'sender' => ['login' => 'devuser', 'id' => 42],
        ],
    );

    $job->handle();

    $task->refresh();

    expect($task->comments()->count())->toBe(1)
        ->and($task->comments()->first()->body)->toContain('devuser', 'abc123d')
        ->and($task->activities()->where('action', 'github_push')->exists())->toBeTrue();
});

test('push event with closes keyword completes task', function () {
    $user = User::factory()->create();
    $workspace = createWorkspaceMember($user, 'owner');
    $project = createProjectForWorkspace($workspace, $user);
    $task = createTaskForProject($project, $user);

    $job = new ProcessGitHubWebhookJob(
        $workspace->id,
        $project->id,
        'push',
        [
            'ref' => 'refs/heads/main',
            'commits' => [
                [
                    'id' => 'abc123def456',
                    'message' => "closes {$task->code}",
                    'url' => 'https://github.com/owner/repo/commit/abc123def456',
                ],
            ],
            'repository' => ['full_name' => 'owner/repo', 'default_branch' => 'main'],
            'sender' => ['login' => 'devuser', 'id' => 42],
        ],
    );

    $job->handle();

    $task->refresh();

    expect($task->completed_at)->not->toBeNull();
});

test('push event to non-default branch is ignored', function () {
    $user = User::factory()->create();
    $workspace = createWorkspaceMember($user, 'owner');
    $project = createProjectForWorkspace($workspace, $user);
    $task = createTaskForProject($project, $user);

    $job = new ProcessGitHubWebhookJob(
        $workspace->id,
        $project->id,
        'push',
        [
            'ref' => 'refs/heads/feature-branch',
            'commits' => [
                [
                    'id' => 'abc123def456',
                    'message' => "{$task->code} on branch",
                    'url' => 'https://github.com/owner/repo/commit/abc123def456',
                ],
            ],
            'repository' => ['full_name' => 'owner/repo', 'default_branch' => 'main'],
            'sender' => ['login' => 'devuser', 'id' => 42],
        ],
    );

    $job->handle();

    $task->refresh();

    expect($task->comments()->count())->toBe(0);
});

test('pull request event with matching code adds comment and activity', function () {
    $user = User::factory()->create();
    $workspace = createWorkspaceMember($user, 'owner');
    $project = createProjectForWorkspace($workspace, $user);
    $task = createTaskForProject($project, $user);

    $job = new ProcessGitHubWebhookJob(
        $workspace->id,
        $project->id,
        'pull_request',
        [
            'action' => 'opened',
            'pull_request' => [
                'number' => 42,
                'title' => "Feature: {$task->code}",
                'html_url' => 'https://github.com/owner/repo/pull/42',
                'merged' => false,
            ],
            'repository' => ['full_name' => 'owner/repo'],
            'sender' => ['login' => 'devuser'],
        ],
    );

    $job->handle();

    $task->refresh();

    expect($task->comments()->count())->toBe(1)
        ->and($task->comments()->first()->body)->toContain('opened', '#42')
        ->and($task->activities()->where('action', 'github_pr')->exists())->toBeTrue();
});

test('merged pull request completes task', function () {
    $user = User::factory()->create();
    $workspace = createWorkspaceMember($user, 'owner');
    $project = createProjectForWorkspace($workspace, $user);
    $task = createTaskForProject($project, $user);

    $job = new ProcessGitHubWebhookJob(
        $workspace->id,
        $project->id,
        'pull_request',
        [
            'action' => 'closed',
            'pull_request' => [
                'number' => 43,
                'title' => "Feature: {$task->code}",
                'html_url' => 'https://github.com/owner/repo/pull/43',
                'merged' => true,
            ],
            'repository' => ['full_name' => 'owner/repo'],
            'sender' => ['login' => 'devuser'],
        ],
    );

    $job->handle();

    $task->refresh();

    expect($task->completed_at)->not->toBeNull();
});

test('unmerged closed pull request does not complete task', function () {
    $user = User::factory()->create();
    $workspace = createWorkspaceMember($user, 'owner');
    $project = createProjectForWorkspace($workspace, $user);
    $task = createTaskForProject($project, $user);

    $job = new ProcessGitHubWebhookJob(
        $workspace->id,
        $project->id,
        'pull_request',
        [
            'action' => 'closed',
            'pull_request' => [
                'number' => 44,
                'title' => "Feature: {$task->code}",
                'html_url' => 'https://github.com/owner/repo/pull/44',
                'merged' => false,
            ],
            'repository' => ['full_name' => 'owner/repo'],
            'sender' => ['login' => 'devuser'],
        ],
    );

    $job->handle();

    $task->refresh();

    expect($task->completed_at)->toBeNull();
});

test('disconnect removes integration', function () {
    $user = User::factory()->create();
    $workspace = createWorkspaceMember($user, 'owner');
    $project = createProjectForWorkspace($workspace, $user);

    Integration::create([
        'workspace_id' => $workspace->id,
        'project_id' => $project->id,
        'provider' => 'github',
        'provider_user_id' => '12345',
        'access_token' => 'encrypted-token',
    ]);

    $this->actingAs($user)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->delete(route('projects.github.destroy', [$workspace, $project]))
        ->assertRedirect();

    expect(Integration::where('project_id', $project->id)->count())->toBe(0);
});
