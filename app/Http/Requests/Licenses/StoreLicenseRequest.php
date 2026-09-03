<?php

namespace App\Http\Requests\Licenses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'branch_ids' => array_values(array_unique(array_filter(array_map('intval', (array) $this->input('branch_ids', []))))),
            'license_number' => trim((string) $this->input('license_number', '')),
            'title' => trim((string) $this->input('title', '')),
            'notes' => trim((string) $this->input('notes', '')),
        ]);
    }

    public function rules(): array
    {
        return [
            'authority_id' => ['required', 'integer', Rule::exists('license_authorities', 'id')],
            'type_id' => ['required', 'integer', Rule::exists('license_types', 'id')],
            'license_number' => ['nullable', 'string', 'max:150', 'regex:/^\d+$/'],
            'title' => ['nullable', 'string', 'max:255'],
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['required', 'integer', 'distinct', Rule::exists('branches', 'id')],
            'responsible_user_id' => ['required', 'integer', Rule::exists('ra_users', 'hr_id')],
            'issue_date' => ['required', 'date'],
            'expiry_date' => ['required', 'date', 'after:issue_date'],
            'renewal_stage_id' => ['nullable', 'integer', Rule::exists('license_renewal_stages', 'id')],
            'notes' => ['nullable', 'string', 'max:10000'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,gif,webp,xls,xlsx'],
        ];
    }

    public function messages(): array
    {
        return [
            'license_number.regex' => __('licenses.validation.license_number_numeric'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $companyId = (int) session('companies_groups_id', 0);
            $branchIds = (array) $this->input('branch_ids', []);
            $allowedBranches = DB::table('branches')
                ->whereIn('id', $branchIds)
                ->where('companies_groups_id', $companyId)
                ->when((int) session('hr_user_level', 0) !== 3 && (int) session('hr_branch_id', 0) > 0,
                    fn ($query) => $query->where('id', (int) session('hr_branch_id')))
                ->count();

            if ($allowedBranches !== count($branchIds)) {
                $validator->errors()->add('branch_ids', __('licenses.validation.invalid_branch'));
            }

            foreach ([['license_authorities', 'authority_id'], ['license_types', 'type_id']] as [$table, $field]) {
                if (! DB::table($table)->where('id', (int) $this->input($field))->where('companies_groups_id', $companyId)->where('publish', true)->exists()) {
                    $validator->errors()->add($field, __('licenses.validation.invalid_reference'));
                }
            }

            if ($this->filled('renewal_stage_id') && ! DB::table('license_renewal_stages')
                ->where('id', (int) $this->input('renewal_stage_id'))->where('publish', true)->exists()) {
                $validator->errors()->add('renewal_stage_id', __('licenses.validation.invalid_reference'));
            }

            if (! DB::table('ra_users')->where('hr_id', (int) $this->input('responsible_user_id'))->where('companies_groups_id', $companyId)->exists()) {
                $validator->errors()->add('responsible_user_id', __('licenses.validation.invalid_responsible'));
            }
        });
    }

    public function payload(): array
    {
        return [
            'license_authority_id' => (int) $this->input('authority_id'),
            'license_type_id' => (int) $this->input('type_id'),
            'license_number' => $this->filled('license_number') ? (string) $this->input('license_number') : null,
            'title' => $this->filled('title') ? (string) $this->input('title') : null,
            'branch_ids' => array_map('intval', (array) $this->input('branch_ids')),
            'responsible_user_id' => (int) $this->input('responsible_user_id'),
            'issue_date' => $this->date('issue_date')?->toDateString(),
            'expiry_date' => $this->date('expiry_date')?->toDateString(),
            'renewal_stage_id' => $this->filled('renewal_stage_id') ? (int) $this->input('renewal_stage_id') : null,
            'notes' => $this->filled('notes') ? (string) $this->input('notes') : null,
        ];
    }

    /** @return list<UploadedFile> */
    public function attachments(): array
    {
        return array_values(array_filter((array) $this->file('attachments', []), fn ($file) => $file instanceof UploadedFile));
    }
}
