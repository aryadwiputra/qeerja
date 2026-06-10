<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->project) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'key' => ['required', 'string', 'max:20', 'alpha_dash', 'uppercase', Rule::unique('projects')->where('workspace_id', $this->workspace->id)->ignore($this->project)],
            'slug' => ['required', 'string', 'max:180', 'alpha_dash', Rule::unique('projects')->where('workspace_id', $this->workspace->id)->ignore($this->project)],
            'description' => ['nullable', 'string'],
            'visibility' => ['nullable', 'in:private,workspace'],
            'color' => ['nullable', 'string', 'max:30'],
        ];
    }
}
