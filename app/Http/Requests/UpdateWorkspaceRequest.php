<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->workspace) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:180', 'alpha_dash', 'unique:workspaces,slug,'.$this->route('workspace')->id],
            'description' => ['nullable', 'string'],
        ];
    }
}
