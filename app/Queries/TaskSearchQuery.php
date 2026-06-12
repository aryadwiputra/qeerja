<?php

namespace App\Queries;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class TaskSearchQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Task>
     */
    public function build(User $user, array $filters): Builder
    {
        return Task::query()
            ->whereHas('project.members', fn (Builder $query) => $query->where('user_id', $user->id))
            ->with([
                'project:id,workspace_id,name,key,slug,color',
                'project.workspace:id,name,slug',
                'priority:id,name,key,level,color',
                'taskType:id,name,key,color',
                'boardColumn:id,name,status_key,color',
                'reporter:id,name,avatar',
                'assignees:id,name,avatar',
                'labels:id,name,color',
            ])
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['workspace_id'] ?? null, fn (Builder $query, int $workspaceId) => $query->whereHas('project', fn (Builder $query) => $query->where('workspace_id', $workspaceId)))
            ->when($filters['project_id'] ?? null, fn (Builder $query, int $projectId) => $query->where('project_id', $projectId))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['assignee_id'] ?? null, fn (Builder $query, int $assigneeId) => $query->whereHas('assignees', fn (Builder $query) => $query->where('users.id', $assigneeId)))
            ->when($filters['reporter_id'] ?? null, fn (Builder $query, int $reporterId) => $query->where('reporter_id', $reporterId))
            ->when($filters['priority_id'] ?? null, fn (Builder $query, int $priorityId) => $query->where('priority_id', $priorityId))
            ->when($filters['label_id'] ?? null, fn (Builder $query, int $labelId) => $query->whereHas('labels', fn (Builder $query) => $query->where('labels.id', $labelId)))
            ->when($filters['due_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('due_date', '>=', $date))
            ->when($filters['due_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('due_date', '<=', $date))
            ->when($filters['created_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['created_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->when(($filters['state'] ?? 'active') === 'active', fn (Builder $query) => $query->whereNull('completed_at')->whereNull('archived_at'))
            ->when(($filters['state'] ?? 'active') === 'completed', fn (Builder $query) => $query->whereNotNull('completed_at')->whereNull('archived_at'))
            ->when(($filters['state'] ?? 'active') === 'archived', fn (Builder $query) => $query->whereNotNull('archived_at'))
            ->latest('tasks.updated_at');
    }
}
