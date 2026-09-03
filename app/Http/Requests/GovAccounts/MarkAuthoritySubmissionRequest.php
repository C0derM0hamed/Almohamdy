<?php

namespace App\Http\Requests\GovAccounts;

use Illuminate\Foundation\Http\FormRequest;

class MarkAuthoritySubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['authority_submitted_at' => ['required', 'date'], 'authority_reference' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string', 'max:5000']];
    }

    public function payload(): array
    {
        return $this->validated();
    }
}
