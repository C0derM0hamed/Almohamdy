<?php

namespace App\Http\Requests\Complaints;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool { return (int) session('hr_user_id') > 0 && (int) session('companies_groups_id') > 0; }

    protected function prepareForValidation(): void
    {
        foreach (['complainant_name', 'patient_name', 'defendant', 'file_number', 'details', 'mobile', 'id_no'] as $key) {
            $this->merge([$key => trim((string) $this->input($key, ''))]);
        }
    }

    public function rules(): array
    {
        return [
            'complainant_name' => ['required', 'string', 'max:100'],
            'patient_name' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'regex:/^[0-9+ ]{8,20}$/'],
            'id_no' => ['nullable', 'digits_between:1,12'],
            'file_number' => ['nullable', 'string', 'max:20'],
            'branches_departments_id' => ['required', 'integer', Rule::exists('branches_departments', 'id')->where('publish', 1)],
            'defendant' => ['required', 'string', 'max:100'],
            'details' => ['required', 'string', 'max:10000'],
            'type' => ['required', 'integer', Rule::in([1, 2])],
            'event_date' => ['nullable', 'date'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,pdf', 'max:10240'],
        ];
    }

    public function payload(): array { return $this->safe()->except(['attachment', 'event_date']) + ['event_date' => $this->input('event_date')]; }
}
