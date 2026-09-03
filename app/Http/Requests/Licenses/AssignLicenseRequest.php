<?php

namespace App\Http\Requests\Licenses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssignLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['responsible_user_id' => ['required', 'integer', Rule::exists('ra_users', 'hr_id')]];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! DB::table('ra_users')->where('hr_id', (int) $this->input('responsible_user_id'))
                ->where('companies_groups_id', (int) session('companies_groups_id', 0))->exists()) {
                $validator->errors()->add('responsible_user_id', __('licenses.validation.invalid_responsible'));
            }
        });
    }

    public function responsibleUserId(): int
    {
        return (int) $this->input('responsible_user_id');
    }
}
