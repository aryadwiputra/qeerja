<?php

namespace App\Http\Requests;

use App\Support\Rbac;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkspaceMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageMembers', $this->workspace) ?? false;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', Rule::in(array_values(array_diff(Rbac::WORKSPACE_ROLES, ['owner'])))],
        ];
    }
}
