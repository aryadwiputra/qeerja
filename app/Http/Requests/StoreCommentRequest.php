<?php

namespace App\Http\Requests;

use App\Models\TaskComment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [TaskComment::class, $this->task]) ?? false;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string'],
            'parent_id' => ['nullable', Rule::exists('task_comments', 'id')->where('task_id', $this->task->id)],
        ];
    }
}
