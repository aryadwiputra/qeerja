<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkspaceSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->workspace) ?? false;
    }

    public function rules(): array
    {
        return [
            'default_locale' => ['nullable', 'string', 'in:en,id'],
            'default_timezone' => ['nullable', 'string', 'timezone:all'],
            'auto_watch_own_tasks' => ['nullable', 'boolean'],
        ];
    }

    public function validatedWithKeys(): array
    {
        $allowed = ['default_locale', 'default_timezone', 'auto_watch_own_tasks'];
        $result = [];

        foreach ($allowed as $key) {
            if ($this->has($key)) {
                $result[$key] = $this->input($key);
            }
        }

        return $result;
    }
}
