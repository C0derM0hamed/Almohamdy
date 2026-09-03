<?php

namespace App\Http\Requests\GovAccounts;

use Illuminate\Foundation\Http\FormRequest;

class ResubmitGovAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['response' => ['required', 'string', 'min:3', 'max:5000']];
    }

    public function responseText(): string
    {
        return trim((string) $this->input('response'));
    }
}
