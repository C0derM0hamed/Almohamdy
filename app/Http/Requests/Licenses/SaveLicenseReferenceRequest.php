<?php

namespace App\Http\Requests\Licenses;

use Illuminate\Foundation\Http\FormRequest;

class SaveLicenseReferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'min:2', 'max:255'],
            'name_en' => ['required', 'string', 'min:2', 'max:255'],
            'ranking' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'publish' => ['nullable', 'boolean'],
        ];
    }

    public function payload(): array
    {
        return [
            'name_ar' => trim((string) $this->input('name_ar')),
            'name_en' => trim((string) $this->input('name_en')),
            'ranking' => (int) $this->input('ranking', 0),
            'publish' => $this->boolean('publish'),
        ];
    }
}
