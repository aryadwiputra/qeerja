<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreAttachmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->task) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, File|string>>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                File::types(['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'txt', 'md', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'zip'])
                    ->max(10 * 1024),
            ],
        ];
    }
}
