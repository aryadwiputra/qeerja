<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [Project::class, $this->workspace]) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'key' => ['required', 'string', 'max:20', 'alpha_dash', 'uppercase', Rule::unique('projects')->where('workspace_id', $this->workspace->id)],
            'slug' => ['required', 'string', 'max:180', 'alpha_dash', Rule::unique('projects')->where('workspace_id', $this->workspace->id)],
            'description' => ['nullable', 'string'],
            'visibility' => ['nullable', 'in:private,workspace'],
            'color' => ['nullable', 'string', 'max:30'],
        ];
    }
}
