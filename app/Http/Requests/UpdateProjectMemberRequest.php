<?php

namespace App\Http\Requests;

use App\Support\Rbac;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageMembers', $this->project) ?? false;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(Rbac::PROJECT_ROLES)],
        ];
    }
}
