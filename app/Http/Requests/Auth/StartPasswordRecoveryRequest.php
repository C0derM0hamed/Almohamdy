<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class StartPasswordRecoveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'string', 'min:9', 'max:20', 'regex:/^[0-9+ -]+$/'],
        ];
    }

    public function username(): string
    {
        return trim((string) $this->input('username'));
    }

    public function mobile(): string
    {
        return trim((string) $this->input('mobile'));
    }
}
