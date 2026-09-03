<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:2', 'max:100', 'regex:/^\S+$/'],
            'password' => ['required', 'string', 'min:2', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => __('login.username').' '.__('validation.required'),
            'password.required' => 'كلمة المرور مطلوبة.',
        ];
    }

    public function username(): string
    {
        return trim((string) $this->input('username'));
    }

    public function password(): string
    {
        return trim((string) $this->input('password'));
    }
}
