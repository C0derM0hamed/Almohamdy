<?php

namespace App\Http\Requests\GovAccounts;

use Illuminate\Foundation\Http\FormRequest;

class ReviewGovAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['notes' => ['nullable', 'string', 'max:5000']];
    }

    public function notes(): ?string
    {
        return $this->filled('notes') ? trim((string) $this->input('notes')) : null;
    }
}
