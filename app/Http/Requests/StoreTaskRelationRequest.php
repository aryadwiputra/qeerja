<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRelationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->task) ?? false;
    }

    public function rules(): array
    {
        return [
            'related_task_id' => [
                'required',
                'integer',
                Rule::exists('tasks', 'id')->where('project_id', $this->project->id),
            ],
            'relation_type' => ['required', Rule::in(['blocks', 'relates_to', 'duplicates'])],
        ];
    }
}
