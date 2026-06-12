<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TaskBulkOperationService
{
    public function __construct(private readonly TaskActivityService $activity) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function apply(Project $project, User $actor, array $payload): int
    {
        /** @var Collection<int, Task> $tasks */
        $tasks = $project->tasks()
            ->whereKey($payload['task_ids'])
            ->with(['assignees:id', 'labels:id', 'epics:id', 'sprints:id', 'watchers:id', 'boardColumn'])
            ->get();

        abort_if($tasks->count() !== count($payload['task_ids']), 422, 'One or more tasks do not belong to this project.');

        $ability = $payload['operation'] === 'delete' ? 'delete' : 'update';
        $tasks->each(fn (Task $task) => Gate::authorize($ability, $task));

        DB::transaction(function () use ($tasks, $actor, $payload): void {
            match ($payload['operation']) {
                'move_column' => $this->moveColumn($tasks, $actor, (int) $payload['board_column_id']),
                'assignees' => $this->changeAssignees($tasks, $actor, $payload['assignee_mode'], array_map('intval', $payload['assignee_ids'] ?? [])),
                'priority' => $this->changePriority($tasks, $actor, array_key_exists('priority_id', $payload) ? $payload['priority_id'] : null),
                'labels' => $this->changeLabels($tasks, $actor, $payload['label_mode'], array_map('intval', $payload['label_ids'] ?? [])),
                'archive' => $this->archive($tasks, $actor),
                'delete' => $this->delete($tasks, $actor),
            };
        });

        $this->logBulkOperation($project, $actor, $payload['operation'], $tasks->pluck('id')->all());

        return $tasks->count();
    }

    /** @param  Collection<int, Task>  $tasks */
    private function moveColumn(Collection $tasks, User $actor, int $columnId): void
    {
        $column = BoardColumn::findOrFail($columnId);

        foreach ($tasks as $task) {
            $before = $task->only(['board_column_id']);
            $oldAssigneeIds = $this->ids($task->assignees);
            $oldEpicIds = $this->ids($task->epics);
            $oldSprintIds = $this->ids($task->sprints);
            $oldWatcherIds = $this->ids($task->watchers);

            $task->update([
                'board_id' => $column->board_id,
                'board_column_id' => $column->id,
                'status' => $column->status_key,
            ]);

            $this->activity->updated($task->refresh(), $actor, $before, $oldAssigneeIds, $oldAssigneeIds, $oldEpicIds, $oldEpicIds, $oldSprintIds, $oldSprintIds, $oldWatcherIds, $oldWatcherIds);
        }
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @param  list<int>  $assigneeIds
     */
    private function changeAssignees(Collection $tasks, User $actor, string $mode, array $assigneeIds): void
    {
        foreach ($tasks as $task) {
            $oldAssigneeIds = $this->ids($task->assignees);
            $newAssigneeIds = $this->applyIds($oldAssigneeIds, $assigneeIds, $mode);
            $oldEpicIds = $this->ids($task->epics);
            $oldSprintIds = $this->ids($task->sprints);
            $oldWatcherIds = $this->ids($task->watchers);

            $task->assignees()->sync($newAssigneeIds);

            $this->activity->updated($task->refresh(), $actor, [], $oldAssigneeIds, $newAssigneeIds, $oldEpicIds, $oldEpicIds, $oldSprintIds, $oldSprintIds, $oldWatcherIds, $oldWatcherIds);
        }
    }

    /** @param  Collection<int, Task>  $tasks */
    private function changePriority(Collection $tasks, User $actor, mixed $priorityId): void
    {
        foreach ($tasks as $task) {
            $before = $task->only(['priority_id']);
            $oldAssigneeIds = $this->ids($task->assignees);
            $oldEpicIds = $this->ids($task->epics);
            $oldSprintIds = $this->ids($task->sprints);
            $oldWatcherIds = $this->ids($task->watchers);

            $task->update(['priority_id' => $priorityId]);

            $this->activity->updated($task->refresh(), $actor, $before, $oldAssigneeIds, $oldAssigneeIds, $oldEpicIds, $oldEpicIds, $oldSprintIds, $oldSprintIds, $oldWatcherIds, $oldWatcherIds);
        }
    }

    /**
     * @param  Collection<int, Task>  $tasks
     * @param  list<int>  $labelIds
     */
    private function changeLabels(Collection $tasks, User $actor, string $mode, array $labelIds): void
    {
        foreach ($tasks as $task) {
            $oldLabelIds = $this->ids($task->labels);
            $newLabelIds = $this->applyIds($oldLabelIds, $labelIds, $mode);

            $task->labels()->sync($newLabelIds);
            $this->activity->bulkChanged($task, $actor, 'labels_changed', 'labels', implode(',', $oldLabelIds), implode(',', $newLabelIds));
        }
    }

    /** @param  Collection<int, Task>  $tasks */
    private function archive(Collection $tasks, User $actor): void
    {
        foreach ($tasks as $task) {
            $oldValue = $task->archived_at?->toISOString();

            $task->update(['archived_at' => now()]);
            $this->activity->bulkChanged($task->refresh(), $actor, 'archived', 'archived_at', $oldValue, $task->archived_at?->toISOString());
        }
    }

    /** @param  Collection<int, Task>  $tasks */
    private function delete(Collection $tasks, User $actor): void
    {
        foreach ($tasks as $task) {
            $this->activity->deleted($task, $actor);
            $task->delete();
        }
    }

    /**
     * @param  list<int>  $currentIds
     * @param  list<int>  $incomingIds
     * @return list<int>
     */
    private function applyIds(array $currentIds, array $incomingIds, string $mode): array
    {
        $ids = match ($mode) {
            'add' => array_unique([...$currentIds, ...$incomingIds]),
            'remove' => array_diff($currentIds, $incomingIds),
            'replace' => $incomingIds,
        };

        return array_values(array_map('intval', $ids));
    }

    /** @return list<int> */
    private function ids(Collection $models): array
    {
        return $models->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    /** @param  list<int>  $taskIds */
    private function logBulkOperation(Project $project, User $actor, string $operation, array $taskIds): void
    {
        ActivityLog::create([
            'workspace_id' => $project->workspace_id,
            'project_id' => $project->id,
            'user_id' => $actor->id,
            'subject_type' => $project->getMorphClass(),
            'subject_id' => $project->id,
            'action' => 'task_bulk_'.$operation,
            'description' => sprintf('bulk %s on %d tasks', str_replace('_', ' ', $operation), count($taskIds)),
            'properties' => [
                'operation' => $operation,
                'task_ids' => Arr::sort($taskIds),
            ],
        ]);
    }
}
