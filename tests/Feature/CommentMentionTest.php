<?php

use App\Models\Notification;
use App\Models\User;

test('comment with mention creates mention record and notification', function () {
    $commenter = User::factory()->create(['name' => 'Commenter']);
    $mentioned = User::factory()->create(['name' => 'Mentioned']);
    $workspace = createWorkspaceMember($commenter, 'manager');
    $workspace->members()->create(['user_id' => $mentioned->id, 'role' => 'member', 'status' => 'active']);
    $project = createProjectForWorkspace($workspace, $commenter, 'developer');
    $project->members()->create(['user_id' => $mentioned->id, 'role' => 'developer']);
    $task = createTaskForProject($project, $commenter);

    $this->actingAs($commenter)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.tasks.comments.store', [$workspace, $project, $task]), [
            'body' => 'Hey @Mentioned check this out',
        ])
        ->assertRedirect();

    $comment = $task->comments()->first();

    expect($comment)->not->toBeNull();
    expect($comment->mentions()->count())->toBe(1);
    expect($comment->mentions()->first()->user_id)->toBe($mentioned->id);
    expect($comment->mentions()->first()->mentioned_text)->toBe('Mentioned');
    expect($comment->mentions()->first()->notified_at)->not->toBeNull();

    $notification = Notification::where('user_id', $mentioned->id)->first();
    expect($notification)->not->toBeNull();
    expect($notification->type)->toBe('task.mentioned');
    expect($notification->data['task_code'])->toBe($task->code);
});

test('non project members cannot be mentioned', function () {
    $commenter = User::factory()->create(['name' => 'Commenter']);
    $outsider = User::factory()->create(['name' => 'Outsider']);
    $workspace = createWorkspaceMember($commenter, 'manager');
    $project = createProjectForWorkspace($workspace, $commenter, 'developer');
    $task = createTaskForProject($project, $commenter);

    $this->actingAs($commenter)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.tasks.comments.store', [$workspace, $project, $task]), [
            'body' => 'Hey @Outsider check this out',
        ])
        ->assertRedirect();

    $comment = $task->comments()->first();
    expect($comment->mentions()->count())->toBe(0);

    expect(Notification::where('user_id', $outsider->id)->exists())->toBeFalse();
});

test('comment author does not get self mention notification', function () {
    $commenter = User::factory()->create(['name' => 'SelfMention']);
    $workspace = createWorkspaceMember($commenter, 'manager');
    $project = createProjectForWorkspace($workspace, $commenter, 'developer');
    $task = createTaskForProject($project, $commenter);

    $this->actingAs($commenter)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.tasks.comments.store', [$workspace, $project, $task]), [
            'body' => 'Hey @SelfMention mentioning myself',
        ])
        ->assertRedirect();

    $comment = $task->comments()->first();

    expect($comment->mentions()->where('user_id', $commenter->id)->exists())->toBeTrue();

    expect(Notification::where('user_id', $commenter->id)->exists())->toBeFalse();
});

test('editing a comment adds new mentions and keeps existing ones', function () {
    $commenter = User::factory()->create(['name' => 'Commenter']);
    $userA = User::factory()->create(['name' => 'Alice']);
    $userB = User::factory()->create(['name' => 'Bob']);
    $workspace = createWorkspaceMember($commenter, 'manager');

    foreach ([$userA, $userB] as $member) {
        $workspace->members()->create(['user_id' => $member->id, 'role' => 'member', 'status' => 'active']);
    }

    $project = createProjectForWorkspace($workspace, $commenter, 'developer');

    foreach ([$userA, $userB] as $member) {
        $project->members()->create(['user_id' => $member->id, 'role' => 'developer']);
    }

    $task = createTaskForProject($project, $commenter);

    $this->actingAs($commenter)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.tasks.comments.store', [$workspace, $project, $task]), [
            'body' => 'Hey @Alice check this',
        ]);

    $comment = $task->comments()->first();

    expect($comment->mentions()->count())->toBe(1);
    expect($comment->mentions()->where('user_id', $userA->id)->exists())->toBeTrue();

    $this->actingAs($commenter)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->patch(route('projects.tasks.comments.update', [$workspace, $project, $task, $comment]), [
            'body' => 'Hey @Alice and @Bob check this',
        ]);

    $comment->refresh();

    expect($comment->mentions()->count())->toBe(2);
    expect($comment->mentions()->where('user_id', $userA->id)->exists())->toBeTrue();
    expect($comment->mentions()->where('user_id', $userB->id)->exists())->toBeTrue();

    $bNotification = Notification::where('user_id', $userB->id)->first();
    expect($bNotification)->not->toBeNull();
    expect($bNotification->type)->toBe('task.mentioned');
});

test('editing a comment removes mentions that are no longer present', function () {
    $commenter = User::factory()->create(['name' => 'Commenter']);
    $userA = User::factory()->create(['name' => 'Alice']);
    $userB = User::factory()->create(['name' => 'Bob']);
    $workspace = createWorkspaceMember($commenter, 'manager');

    foreach ([$userA, $userB] as $member) {
        $workspace->members()->create(['user_id' => $member->id, 'role' => 'member', 'status' => 'active']);
    }

    $project = createProjectForWorkspace($workspace, $commenter, 'developer');

    foreach ([$userA, $userB] as $member) {
        $project->members()->create(['user_id' => $member->id, 'role' => 'developer']);
    }

    $task = createTaskForProject($project, $commenter);

    $this->actingAs($commenter)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.tasks.comments.store', [$workspace, $project, $task]), [
            'body' => 'Hey @Alice and @Bob check this',
        ]);

    $comment = $task->comments()->first();

    expect($comment->mentions()->count())->toBe(2);

    $this->actingAs($commenter)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->patch(route('projects.tasks.comments.update', [$workspace, $project, $task, $comment]), [
            'body' => 'Hey @Alice only',
        ]);

    $comment->refresh();

    expect($comment->mentions()->count())->toBe(1);
    expect($comment->mentions()->where('user_id', $userA->id)->exists())->toBeTrue();
    expect($comment->mentions()->where('user_id', $userB->id)->exists())->toBeFalse();
});
