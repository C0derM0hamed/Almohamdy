<?php

namespace App\Http\Requests\GovAccounts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveGovAccountReferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $reference = (string) $this->route('reference');

        return match ($reference) {
            'authorities', 'roles' => [
                'name_ar' => ['required', 'string', 'min:2', 'max:255'],
                'name_en' => ['required', 'string', 'min:2', 'max:255'],
                'ranking' => ['nullable', 'integer', 'min:0', 'max:999999'],
                'publish' => ['nullable', 'boolean'],
            ],
            'services' => [
                'authority_id' => ['required', 'integer', Rule::exists('gov_account_authorities', 'id')->where('companies_groups_id', (int) session('companies_groups_id', 0))],
                'name_ar' => ['required', 'string', 'min:2', 'max:255'],
                'name_en' => ['required', 'string', 'min:2', 'max:255'],
                'ranking' => ['nullable', 'integer', 'min:0', 'max:999999'],
                'publish' => ['nullable', 'boolean'],
            ],
            'department-heads' => [
                'department_id' => ['required', 'integer', Rule::exists('branches_departments', 'id')],
                'user_id' => ['required', 'integer', Rule::exists('ra_users', 'hr_id')->where('companies_groups_id', (int) session('companies_groups_id', 0))],
                'publish' => ['nullable', 'boolean'],
            ],
            default => ['reference' => ['required', Rule::in([])]],
        };
    }

    public function payload(): array
    {
        if ((string) $this->route('reference') === 'department-heads') {
            return [
                'department_id' => $this->integer('department_id'),
                'user_id' => $this->integer('user_id'),
                'publish' => $this->boolean('publish'),
            ];
        }

        return array_filter([
            'authority_id' => $this->filled('authority_id') ? $this->integer('authority_id') : null,
            'name_ar' => trim((string) $this->input('name_ar')),
            'name_en' => trim((string) $this->input('name_en')),
            'ranking' => $this->integer('ranking'),
            'publish' => $this->boolean('publish'),
        ], static fn ($value): bool => $value !== null);
    }
}
