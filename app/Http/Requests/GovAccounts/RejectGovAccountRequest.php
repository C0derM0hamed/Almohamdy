<?php

namespace App\Http\Requests\GovAccounts;

use Illuminate\Foundation\Http\FormRequest;

class RejectGovAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:3', 'max:5000']];
    }

    public function reason(): string
    {
        return trim((string) $this->input('reason'));
    }
}
