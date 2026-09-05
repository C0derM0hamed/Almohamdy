<?php

namespace App\Http\Requests\Licenses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LicenseIndexRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('department_id') && $this->filled('branch_id')) {
            $this->merge(['department_id' => $this->input('branch_id')]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'],
            'department_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'authority_id' => ['nullable', 'integer', Rule::exists('license_authorities', 'id')],
            'type_id' => ['nullable', 'integer', Rule::exists('license_types', 'id')],
            'responsible_user_id' => ['nullable', 'integer', Rule::exists('ra_users', 'hr_id')],
            'status_id' => ['nullable', 'integer', Rule::exists('license_statuses', 'id')],
            'expiry_from' => ['nullable', 'date'],
            'expiry_to' => ['nullable', 'date', 'after_or_equal:expiry_from'],
            'expiry_window' => ['nullable', Rule::in(['30', '60', '90', 'expired'])],
            'report' => ['nullable', Rule::in(['licenses', 'payments', 'alerts', 'responsibilities'])],
            'sort' => ['nullable', Rule::in(['expiry_asc', 'expiry_desc', 'created_desc', 'created_asc'])],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ];
    }

    public function filters(): array
    {
        return [
            'search' => trim((string) $this->input('search', '')),
            'department_id' => $this->integerOrNull('department_id'),
            'authority_id' => $this->integerOrNull('authority_id'),
            'type_id' => $this->integerOrNull('type_id'),
            'responsible_user_id' => $this->integerOrNull('responsible_user_id'),
            'status_id' => $this->integerOrNull('status_id'),
            'expiry_from' => $this->filled('expiry_from') ? $this->date('expiry_from')?->toDateString() : null,
            'expiry_to' => $this->filled('expiry_to') ? $this->date('expiry_to')?->toDateString() : null,
            'expiry_window' => $this->filled('expiry_window') ? (string) $this->input('expiry_window') : null,
            'report' => (string) $this->input('report', 'licenses'),
            'sort' => (string) $this->input('sort', 'expiry_asc'),
        ];
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', config('hm.licenses.per_page', 15));
    }

    private function integerOrNull(string $key): ?int
    {
        return $this->filled($key) ? (int) $this->input($key) : null;
    }
}
