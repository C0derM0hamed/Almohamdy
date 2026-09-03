<?php

namespace App\Http\Requests\SystemAdministration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveUserPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permissions_version' => ['required', 'string', 'size:64'],
            'decisions' => ['required', 'array'],
            'decisions.*' => ['required', Rule::in(['allow', 'deny', 'inherit'])],
        ];
    }
}
