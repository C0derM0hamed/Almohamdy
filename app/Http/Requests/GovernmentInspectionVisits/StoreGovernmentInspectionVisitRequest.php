<?php

namespace App\Http\Requests\GovernmentInspectionVisits;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreGovernmentInspectionVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $findings = collect($this->input('findings', []))
            ->filter(fn ($row) => is_array($row) && filled(trim((string) ($row['title'] ?? ''))))
            ->values()
            ->all();

        $this->merge([
            'findings' => $findings,
            'representative_name' => trim((string) $this->input('representative_name', '')),
            'report' => trim((string) $this->input('report', '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'visit_type_id' => ['required', 'integer', 'min:1', Rule::exists('government_inspection_visits_types', 'id')],
            'branch_id' => ['required', 'integer', 'min:1', Rule::exists('branches', 'id')],
            'visit_date' => ['required', 'date'],
            'subject' => ['required', 'string', 'min:3', 'max:255'],
            'report' => ['nullable', 'string', 'max:5000'],
            'authority_id' => ['required', 'integer', 'min:1', Rule::exists('government_circulars_issuing_authority', 'id')],
            'representative_name' => ['nullable', 'string', 'max:255'],
            'section_id' => ['required', 'integer', 'min:1', Rule::exists('government_circulars_sections', 'id')],
            'abuses_status' => ['required', Rule::in(['1', '2'])],
            'notes_status' => ['required', Rule::in(['1', '2'])],
            'reply_time' => ['nullable', 'date', 'after:visit_date'],
            'findings' => ['nullable', 'array'],
            'findings.*.type' => ['required', Rule::in([1, 2])],
            'findings.*.title' => ['required', 'string', 'min:2', 'max:1000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
            'confirm_details' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirm_details.accepted' => __('inspection_visits.validation.confirm_details'),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $hasViolations = (string) $this->input('abuses_status') === '1';
            $hasNotes = (string) $this->input('notes_status') === '1';
            $findings = collect($this->input('findings', []));

            if ($hasViolations && $this->input('reply_time') === null) {
                $validator->errors()->add('reply_time', __('inspection_visits.validation.reply_time_required'));
            }

            if ($hasViolations && $findings->where('type', 1)->isEmpty()) {
                $validator->errors()->add('findings', __('inspection_visits.validation.violations_required'));
            }

            if ($hasNotes && $findings->where('type', 2)->isEmpty()) {
                $validator->errors()->add('findings', __('inspection_visits.validation.notes_required'));
            }

            if (! $hasViolations && ! $hasNotes && $findings->isNotEmpty()) {
                $validator->errors()->add('findings', __('inspection_visits.validation.findings_not_allowed'));
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $findings = collect($this->input('findings', []))
            ->filter(fn ($row) => is_array($row) && filled($row['title'] ?? null))
            ->map(fn ($row) => [
                'type' => (int) $row['type'],
                'abuse_note_title' => trim((string) $row['title']),
            ])
            ->values()
            ->all();

        return [
            'visit_type_id' => (int) $this->input('visit_type_id'),
            'branch_id' => (int) $this->input('branch_id'),
            'visit_date' => $this->date('visit_date')?->format('Y-m-d H:i:s'),
            'subject' => trim((string) $this->input('subject')),
            'report' => trim((string) $this->input('report', '')),
            'authority_id' => (int) $this->input('authority_id'),
            'representative_name' => trim((string) $this->input('representative_name', '')),
            'section_id' => (int) $this->input('section_id'),
            'abuses_status' => (string) $this->input('abuses_status'),
            'notes_status' => (string) $this->input('notes_status'),
            'reply_time' => $this->filled('reply_time')
                ? $this->date('reply_time')?->format('Y-m-d H:i:s')
                : null,
            'findings' => $findings,
        ];
    }
}
