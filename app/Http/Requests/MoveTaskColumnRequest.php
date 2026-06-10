<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveTaskColumnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('moveColumn', $this->task) ?? false;
    }

    public function rules(): array
    {
        $boardId = $this->task->board_id;

        return [
            'board_column_id' => ['required', Rule::exists('board_columns', 'id')->where('board_id', $boardId)],
            'position' => ['required', 'numeric'],
        ];
    }
}
