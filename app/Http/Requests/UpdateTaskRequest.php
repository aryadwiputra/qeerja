<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->task) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'task_type_id' => ['sometimes', 'required', Rule::exists('task_types', 'id')->where('workspace_id', $this->workspace->id)],
            'priority_id' => ['nullable', Rule::exists('priorities', 'id')->where('workspace_id', $this->workspace->id)],
            'board_column_id' => ['sometimes', 'required', Rule::exists('board_columns', 'id')->whereIn('board_id', $this->project->boards()->select('id'))],
            'status' => ['nullable', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'assignee_ids' => ['nullable', 'array'],
            'assignee_ids.*' => ['integer', Rule::exists('project_members', 'user_id')->where('project_id', $this->project->id)],
            'label_ids' => ['nullable', 'array'],
            'label_ids.*' => ['integer', Rule::exists('labels', 'id')->where('workspace_id', $this->workspace->id)],
            'epic_ids' => ['nullable', 'array'],
            'epic_ids.*' => ['integer', Rule::exists('epics', 'id')->where('project_id', $this->project->id)],
            'sprint_ids' => ['nullable', 'array'],
            'sprint_ids.*' => ['integer', Rule::exists('sprints', 'id')->where('project_id', $this->project->id)],
            'parent_id' => ['nullable', 'integer', Rule::exists('tasks', 'id')->where('project_id', $this->project->id)],
            'watcher_ids' => ['nullable', 'array'],
            'watcher_ids.*' => ['integer', Rule::exists('project_members', 'user_id')->where('project_id', $this->project->id)],
        ];
    }
}
