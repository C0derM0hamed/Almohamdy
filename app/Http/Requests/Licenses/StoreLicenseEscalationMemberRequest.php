<?php

namespace App\Http\Requests\Licenses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLicenseEscalationMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['user_id' => ['required', 'integer', Rule::exists('ra_users', 'hr_id')]];
    }

    public function userId(): int
    {
        return (int) $this->input('user_id');
    }
}
