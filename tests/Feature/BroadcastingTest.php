<?php

use App\Events\TaskAssigned;
use App\Events\TaskCommented;
use App\Events\TaskUpdated;
use App\Models\User;
use App\Services\MentionNotificationService;
use App\Services\NotificationService;
use App\Services\WorkspaceRoleService;
use Illuminate\Support\Facades\Event;

test('notifyAssigned dispatches TaskAssigned event', function () {
    $assigner = User::factory()->create();
    $assignee = User::factory()->create();
    $workspace = createWorkspaceMember($assigner, 'manager');
    $workspace->members()->create([
        'user_id' => $assignee->id,
        'role' => 'member',
        'status' => 'active',
    ]);
    app(WorkspaceRoleService::class)->syncRole($assignee, $workspace, 'member');
    $project = createProjectForWorkspace($workspace, $assigner, 'manager');
    $project->members()->create([
        'user_id' => $assignee->id,
        'role' => 'developer',
    ]);
    $task = createTaskForProject($project, $assigner);

    Event::fake();
    $service = app(NotificationService::class);
    $service->notifyAssigned($task, $assignee, $assigner);

    Event::assertDispatched(TaskAssigned::class, function ($event) use ($assignee, $task) {
        return $event->userId === $assignee->id
            && $event->type === 'task.assigned'
            && $event->taskCode === $task->code
            && $event->taskId === $task->id;
    });
});

test('notifyComment dispatches TaskCommented event', function () {
    $commenter = User::factory()->create();
    $assignee = User::factory()->create();
    $workspace = createWorkspaceMember($commenter, 'manager');
    $workspace->members()->create([
        'user_id' => $assignee->id,
        'role' => 'member',
        'status' => 'active',
    ]);
    app(WorkspaceRoleService::class)->syncRole($assignee, $workspace, 'member');
    $project = createProjectForWorkspace($workspace, $commenter, 'manager');
    $project->members()->create([
        'user_id' => $assignee->id,
        'role' => 'developer',
    ]);
    $task = createTaskForProject($project, $commenter);
    $task->assignees()->attach($assignee);
    $comment = $task->comments()->create([
        'user_id' => $commenter->id,
        'body' => 'Test comment',
    ]);

    Event::fake();
    $service = app(NotificationService::class);
    $service->notifyComment($task, $commenter, $comment);

    Event::assertDispatched(TaskCommented::class, function ($event) use ($assignee, $task) {
        return $event->userId === $assignee->id
            && $event->type === 'task.commented'
            && $event->taskCode === $task->code
            && $event->taskId === $task->id;
    });
});

test('notifyWatchers dispatches TaskUpdated event', function () {
    $actor = User::factory()->create();
    $watcher = User::factory()->create();
    $workspace = createWorkspaceMember($actor, 'manager');
    $workspace->members()->create([
        'user_id' => $watcher->id,
        'role' => 'member',
        'status' => 'active',
    ]);
    app(WorkspaceRoleService::class)->syncRole($watcher, $workspace, 'member');
    $project = createProjectForWorkspace($workspace, $actor, 'manager');
    $project->members()->create([
        'user_id' => $watcher->id,
        'role' => 'developer',
    ]);
    $task = createTaskForProject($project, $actor);
    $task->watchers()->attach($watcher);

    Event::fake();
    $service = app(NotificationService::class);
    $service->notifyWatchers($task, $actor, collect([$watcher]));

    Event::assertDispatched(TaskUpdated::class, function ($event) use ($watcher, $task) {
        return $event->userId === $watcher->id
            && $event->type === 'task.updated'
            && $event->taskCode === $task->code
            && $event->taskId === $task->id;
    });
});

test('mention dispatches TaskUpdated event', function () {
    $commenter = User::factory()->create();
    $mentioned = User::factory()->create();
    $workspace = createWorkspaceMember($commenter, 'manager');
    $workspace->members()->create([
        'user_id' => $mentioned->id,
        'role' => 'member',
        'status' => 'active',
    ]);
    app(WorkspaceRoleService::class)->syncRole($mentioned, $workspace, 'member');
    $project = createProjectForWorkspace($workspace, $commenter, 'manager');
    $project->members()->create([
        'user_id' => $mentioned->id,
        'role' => 'developer',
    ]);
    $task = createTaskForProject($project, $commenter);
    $comment = $task->comments()->create([
        'user_id' => $commenter->id,
        'body' => 'Hello @'.$mentioned->name,
    ]);

    Event::fake();
    $service = app(MentionNotificationService::class);
    $service->notify($comment, $task, $commenter, collect([$mentioned]));

    Event::assertDispatched(TaskUpdated::class, function ($event) use ($mentioned, $task) {
        return $event->userId === $mentioned->id
            && $event->type === 'task.mentioned'
            && $event->taskCode === $task->code
            && $event->taskId === $task->id;
    });
});
