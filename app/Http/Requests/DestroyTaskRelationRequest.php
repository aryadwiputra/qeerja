<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DestroyTaskRelationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->task) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
