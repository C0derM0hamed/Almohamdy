<?php

namespace App\Http\Requests\Licenses;

use Illuminate\Foundation\Http\FormRequest;

class StartLicenseRenewalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['notes' => ['nullable', 'string', 'max:10000']];
    }

    public function notes(): ?string
    {
        $value = trim((string) $this->input('notes', ''));

        return $value !== '' ? $value : null;
    }
}
