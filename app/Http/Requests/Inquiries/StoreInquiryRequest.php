<?php

namespace App\Http\Requests\Inquiries;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Schema;

class StoreInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array((int) session('companies_groups_id'), [1, 3], true)
            && (int) session('hr_branch_id') > 0
            && (int) session('hr_user_id') > 0;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'enquirer' => trim((string) $this->input('enquirer')),
            'mobile' => preg_replace('/\s+/', '', (string) $this->input('mobile')),
            'inquiry_details' => trim((string) $this->input('inquiry_details')),
        ]);
    }

    public function rules(): array
    {
        $branch = (int) $this->input('inquired_section');

        return [
            'enquirer' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'regex:/^05[0-9]{8}$/'],
            'inquired_section' => ['required', 'integer', Rule::exists('branches', 'id')->where(function ($query): void {
                if (Schema::hasColumn('branches', 'publish')) {
                    $query->where('publish', 1);
                }
            })],
            'job_title' => [
                'required',
                'integer',
                Rule::exists('job_titles', 'id')->where(fn ($query) => $query
                    ->where('branch_id', $branch)
                    ->where('publish', 1)),
            ],
            'inquiry_id' => [
                'required',
                'integer',
                Rule::when((int) $this->input('inquiry_id') !== 0, Rule::exists('inquiries', 'id')->where('publish', 1)),
            ],
            'inquiry_details' => [
                Rule::requiredIf((int) $this->input('inquiry_id') === 0),
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function payload(): array
    {
        return $this->safe()->only([
            'enquirer', 'mobile', 'inquired_section', 'job_title', 'inquiry_id', 'inquiry_details',
        ]);
    }
}
