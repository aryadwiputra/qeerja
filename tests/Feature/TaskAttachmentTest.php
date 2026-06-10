<?php

use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('authorized users can upload and delete task attachments', function () {
    Storage::fake('public');

    $developer = User::factory()->create();
    $workspace = createWorkspaceMember($developer, 'manager');
    $project = createProjectForWorkspace($workspace, $developer, 'developer');
    $task = createTaskForProject($project, $developer);
    $file = UploadedFile::fake()->create('brief.pdf', 32, 'application/pdf');

    $this->actingAs($developer)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.tasks.attachments.store', [$workspace, $project, $task]), [
            'file' => $file,
        ])
        ->assertRedirect();

    $attachment = $task->attachments()->first();

    Storage::disk('public')->assertExists($attachment->file_path);

    expect($attachment->file_name)->toBe('brief.pdf')
        ->and($task->activities()->where('action', 'attachment_added')->exists())->toBeTrue();

    $this->actingAs($developer)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->delete(route('projects.tasks.attachments.destroy', [$workspace, $project, $task, $attachment]))
        ->assertRedirect();

    Storage::disk('public')->assertMissing($attachment->file_path);

    expect(TaskAttachment::whereKey($attachment->id)->exists())->toBeFalse();
});

test('project viewers cannot upload task attachments', function () {
    Storage::fake('public');

    $viewer = User::factory()->create();
    $workspace = createWorkspaceMember($viewer, 'viewer');
    $project = createProjectForWorkspace($workspace, $viewer, 'viewer');
    $task = createTaskForProject($project, $viewer);

    $this->actingAs($viewer)
        ->withSession(['current_workspace_id' => $workspace->id])
        ->post(route('projects.tasks.attachments.store', [$workspace, $project, $task]), [
            'file' => UploadedFile::fake()->create('blocked.pdf', 32, 'application/pdf'),
        ])
        ->assertForbidden();
});
