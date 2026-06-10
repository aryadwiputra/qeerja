<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->project) ?? false;
    }

    public function rules(): array
    {
        return [
            'default_board_id' => ['nullable', 'integer', Rule::exists('boards', 'id')->where('project_id', $this->project->id)],
            'default_assignee_id' => ['nullable', 'integer', Rule::exists('project_members', 'user_id')->where('project_id', $this->project->id)],
            'auto_assign_reporter' => ['nullable', 'boolean'],
        ];
    }

    public function validatedWithKeys(): array
    {
        $allowed = ['default_board_id', 'default_assignee_id', 'auto_assign_reporter'];
        $result = [];

        foreach ($allowed as $key) {
            if ($this->has($key)) {
                $result[$key] = $this->input($key);
            }
        }

        return $result;
    }
}
