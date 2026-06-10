<?php

namespace App\Http\Requests;

use App\Support\Rbac;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkspaceMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageMembers', $this->workspace) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id', Rule::unique('workspace_members')->where('workspace_id', $this->workspace->id)],
            'role' => ['required', Rule::in(array_values(array_diff(Rbac::WORKSPACE_ROLES, ['owner'])))],
        ];
    }
}
