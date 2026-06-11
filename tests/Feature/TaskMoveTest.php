<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = createWorkspaceMember($this->user, 'manager');
    $this->project = createProjectForWorkspace($this->workspace, $this->user, 'developer');
    $this->board = $this->project->boards()->first();
    $this->todoColumn = $this->board->columns()->where('status_key', 'todo')->first();
    $this->doneColumn = $this->board->columns()->where('status_key', 'done')->first();
});

test('task can be moved to another column', function () {
    $task = createTaskForProject($this->project, $this->user);

    $this->actingAs($this->user)
        ->withSession(['current_workspace_id' => $this->workspace->id])
        ->post(route('projects.tasks.move', [$this->workspace, $this->project, $task]), [
            'board_column_id' => $this->doneColumn->id,
            'position' => 0,
        ])
        ->assertRedirect();

    $task->refresh();

    expect($task)
        ->board_column_id->toBe($this->doneColumn->id)
        ->status->toBe($this->doneColumn->status_key);

    expect((int) $task->position)->toBe(0);
});

test('task move rebalances positions in the target column', function () {
    $nextNumber = fn () => $this->project->tasks()->withTrashed()->max('task_number') + 1;

    $n = $nextNumber();
    $taskA = Task::factory()->create([
        'project_id' => $this->project->id,
        'board_id' => $this->board->id,
        'board_column_id' => $this->todoColumn->id,
        'task_type_id' => $this->project->workspace->taskTypes()->first()->id,
        'priority_id' => $this->project->workspace->priorities()->first()->id,
        'reporter_id' => $this->user->id,
        'status' => $this->todoColumn->status_key,
        'position' => 0,
        'task_number' => $n,
        'code' => $this->project->key.'-'.$n,
    ]);

    $n = $nextNumber();
    $taskB = Task::factory()->create([
        'project_id' => $this->project->id,
        'board_id' => $this->board->id,
        'board_column_id' => $this->todoColumn->id,
        'task_type_id' => $this->project->workspace->taskTypes()->first()->id,
        'priority_id' => $this->project->workspace->priorities()->first()->id,
        'reporter_id' => $this->user->id,
        'status' => $this->todoColumn->status_key,
        'position' => 1,
        'task_number' => $n,
        'code' => $this->project->key.'-'.$n,
    ]);

    $n = $nextNumber();
    $taskC = Task::factory()->create([
        'project_id' => $this->project->id,
        'board_id' => $this->board->id,
        'board_column_id' => $this->doneColumn->id,
        'task_type_id' => $this->project->workspace->taskTypes()->first()->id,
        'priority_id' => $this->project->workspace->priorities()->first()->id,
        'reporter_id' => $this->user->id,
        'status' => $this->doneColumn->status_key,
        'position' => 0,
        'task_number' => $n,
        'code' => $this->project->key.'-'.$n,
    ]);

    $n = $nextNumber();
    $taskD = Task::factory()->create([
        'project_id' => $this->project->id,
        'board_id' => $this->board->id,
        'board_column_id' => $this->doneColumn->id,
        'task_type_id' => $this->project->workspace->taskTypes()->first()->id,
        'priority_id' => $this->project->workspace->priorities()->first()->id,
        'reporter_id' => $this->user->id,
        'status' => $this->doneColumn->status_key,
        'position' => 1,
        'task_number' => $n,
        'code' => $this->project->key.'-'.$n,
    ]);

    // Move taskC from Done (pos 0) to Todo at position 1 (between taskA and taskB)
    $this->actingAs($this->user)
        ->withSession(['current_workspace_id' => $this->workspace->id])
        ->post(route('projects.tasks.move', [$this->workspace, $this->project, $taskC]), [
            'board_column_id' => $this->todoColumn->id,
            'position' => 1,
        ])
        ->assertRedirect();

    $taskC->refresh();

    expect($taskC)
        ->board_column_id->toBe($this->todoColumn->id);

    expect((int) $taskC->position)->toBe(1);

    // Todo column: taskA@0, taskC@1, taskB@2 (taskB shifted from 1 to 2)
    $todoPositions = Task::where('board_column_id', $this->todoColumn->id)
        ->orderBy('position')
        ->get()
        ->map(fn ($t) => (int) $t->position)
        ->values()
        ->toArray();

    // Done column: taskD@0 (gap closed by rebalance)
    $donePositions = Task::where('board_column_id', $this->doneColumn->id)
        ->orderBy('position')
        ->get()
        ->map(fn ($t) => (int) $t->position)
        ->values()
        ->toArray();

    expect($todoPositions)->toBe([0, 1, 2]);
    expect($donePositions)->toBe([0]);
});

test('task can be reordered within the same column', function () {
    $nextNumber = fn () => $this->project->tasks()->withTrashed()->max('task_number') + 1;

    $n = $nextNumber();
    $taskA = Task::factory()->create([
        'project_id' => $this->project->id,
        'board_id' => $this->board->id,
        'board_column_id' => $this->todoColumn->id,
        'task_type_id' => $this->project->workspace->taskTypes()->first()->id,
        'priority_id' => $this->project->workspace->priorities()->first()->id,
        'reporter_id' => $this->user->id,
        'status' => $this->todoColumn->status_key,
        'position' => 0,
        'task_number' => $n,
        'code' => $this->project->key.'-'.$n,
    ]);

    $n = $nextNumber();
    $taskB = Task::factory()->create([
        'project_id' => $this->project->id,
        'board_id' => $this->board->id,
        'board_column_id' => $this->todoColumn->id,
        'task_type_id' => $this->project->workspace->taskTypes()->first()->id,
        'priority_id' => $this->project->workspace->priorities()->first()->id,
        'reporter_id' => $this->user->id,
        'status' => $this->todoColumn->status_key,
        'position' => 1,
        'task_number' => $n,
        'code' => $this->project->key.'-'.$n,
    ]);

    $n = $nextNumber();
    $taskC = Task::factory()->create([
        'project_id' => $this->project->id,
        'board_id' => $this->board->id,
        'board_column_id' => $this->todoColumn->id,
        'task_type_id' => $this->project->workspace->taskTypes()->first()->id,
        'priority_id' => $this->project->workspace->priorities()->first()->id,
        'reporter_id' => $this->user->id,
        'status' => $this->todoColumn->status_key,
        'position' => 2,
        'task_number' => $n,
        'code' => $this->project->key.'-'.$n,
    ]);

    // Move taskA from position 0 to position 2 (end of list)
    $this->actingAs($this->user)
        ->withSession(['current_workspace_id' => $this->workspace->id])
        ->post(route('projects.tasks.move', [$this->workspace, $this->project, $taskA]), [
            'board_column_id' => $this->todoColumn->id,
            'position' => 2,
        ])
        ->assertRedirect();

    $positions = Task::where('board_column_id', $this->todoColumn->id)
        ->orderBy('position')
        ->get()
        ->mapWithKeys(fn ($t) => [(int) $t->position => $t->id])
        ->toArray();

    // Position 0 = taskB (shifted from 1), pos 1 = taskC (shifted from 2), pos 2 = taskA
    expect($positions)->toBe([
        0 => $taskB->id,
        1 => $taskC->id,
        2 => $taskA->id,
    ]);
});

test('task move updates status to match target column status_key', function () {
    $task = createTaskForProject($this->project, $this->user);

    expect($task->status)->toBe($this->todoColumn->status_key);

    $this->actingAs($this->user)
        ->withSession(['current_workspace_id' => $this->workspace->id])
        ->post(route('projects.tasks.move', [$this->workspace, $this->project, $task]), [
            'board_column_id' => $this->doneColumn->id,
            'position' => 0,
        ])
        ->assertRedirect();

    $task->refresh();

    expect($task->status)->toBe($this->doneColumn->status_key);
});

test('unauthorized user cannot move a task', function () {
    $task = createTaskForProject($this->project, $this->user);
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->withSession(['current_workspace_id' => $this->workspace->id])
        ->post(route('projects.tasks.move', [$this->workspace, $this->project, $task]), [
            'board_column_id' => $this->doneColumn->id,
            'position' => 0,
        ])
        ->assertForbidden();
});
