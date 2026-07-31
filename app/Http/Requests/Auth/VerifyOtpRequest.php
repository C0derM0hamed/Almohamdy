<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
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
        $rules = [];

        foreach ($this->digitFields() as $field) {
            $rules[$field] = ['required', 'digits:1'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $message = __('otp.errors.incomplete_code');
        $messages = [];

        foreach ($this->digitFields() as $field) {
            $messages[$field.'.required'] = $message;
            $messages[$field.'.digits'] = $message;
        }

        return $messages;
    }

    public function otpCode(): string
    {
        return implode('', array_map(
            fn (string $field): string => (string) $this->input($field, ''),
            $this->digitFields(),
        ));
    }

    /**
     * @return list<string>
     */
    public function digitFields(): array
    {
        $length = max(4, min(8, (int) config('hm.otp.length', 6)));
        $fields = [];

        for ($i = 1; $i <= $length; $i++) {
            $fields[] = 'n'.$i;
        }

        return $fields;
    }
}
