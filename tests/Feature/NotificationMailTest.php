<?php

use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskCommentedNotification;
use App\Notifications\TaskMentionedNotification;
use App\Services\MentionNotificationService;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification as NotificationFacade;

test('task assignment sends queued mail notification', function () {
    NotificationFacade::fake();

    $reporter = User::factory()->create();
    $assignee = User::factory()->create();
    $workspace = createWorkspaceMember($reporter, 'manager');
    $project = createProjectForWorkspace($workspace, $reporter, 'manager');
    $task = createTaskForProject($project, $reporter);

    app(NotificationService::class)->notifyAssigned($task, $assignee, $reporter);

    NotificationFacade::assertSentTo(
        $assignee,
        TaskAssignedNotification::class,
        fn (TaskAssignedNotification $notification, array $channels): bool => $notification instanceof ShouldQueue
            && in_array('mail', $channels, true)
            && $notification->task->is($task)
            && $notification->assignedBy->is($reporter),
    );
});

test('task comment sends queued mail notification to task reporter', function () {
    NotificationFacade::fake();

    $reporter = User::factory()->create();
    $commenter = User::factory()->create();
    $workspace = createWorkspaceMember($reporter, 'manager');
    $project = createProjectForWorkspace($workspace, $reporter, 'manager');
    $task = createTaskForProject($project, $reporter);
    $comment = $task->comments()->create([
        'user_id' => $commenter->id,
        'body' => 'Please review this update.',
    ]);

    app(NotificationService::class)->notifyComment($task, $commenter, $comment);

    NotificationFacade::assertSentTo(
        $reporter,
        TaskCommentedNotification::class,
        fn (TaskCommentedNotification $notification, array $channels): bool => $notification instanceof ShouldQueue
            && in_array('mail', $channels, true)
            && $notification->task->is($task)
            && $notification->comment->is($comment)
            && $notification->commenter->is($commenter),
    );
});

test('task mention sends queued mail notification to mentioned user', function () {
    NotificationFacade::fake();

    $reporter = User::factory()->create();
    $commenter = User::factory()->create();
    $mentioned = User::factory()->create();
    $workspace = createWorkspaceMember($reporter, 'manager');
    $project = createProjectForWorkspace($workspace, $reporter, 'manager');
    $task = createTaskForProject($project, $reporter);
    $comment = $task->comments()->create([
        'user_id' => $commenter->id,
        'body' => "Can you check this, @{$mentioned->name}?",
    ]);

    app(MentionNotificationService::class)->notify($comment, $task, $commenter, collect([$mentioned]));

    NotificationFacade::assertSentTo(
        $mentioned,
        TaskMentionedNotification::class,
        fn (TaskMentionedNotification $notification, array $channels): bool => $notification instanceof ShouldQueue
            && in_array('mail', $channels, true)
            && $notification->task->is($task)
            && $notification->comment->is($comment)
            && $notification->commenter->is($commenter),
    );
});
