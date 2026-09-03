<?php

namespace App\Http\Requests\Licenses;

use Illuminate\Foundation\Http\FormRequest;

class SaveLicenseEscalationGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'min:2', 'max:255'], 'publish' => ['nullable', 'boolean']];
    }

    public function payload(): array
    {
        return ['name' => trim((string) $this->input('name')), 'publish' => $this->boolean('publish')];
    }
}
