<?php

namespace App\Http\Requests;

use App\Support\Rbac;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjectMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageMembers', $this->project) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', Rule::exists('workspace_members', 'user_id')->where('workspace_id', $this->workspace->id)->where('status', 'active'), Rule::unique('project_members')->where('project_id', $this->project->id)],
            'role' => ['required', Rule::in(Rbac::PROJECT_ROLES)],
        ];
    }
}
