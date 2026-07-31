<?php

namespace App\Http\Requests\GovernmentDataRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGovernmentDataRequestRequest extends FormRequest
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
            'branch_id' => ['required', 'integer', 'min:1', Rule::exists('branches', 'id')],
            'entity_id' => ['required', 'integer', 'min:1', Rule::exists('g_sections', 'id')],
            'data_type_id' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('g_sectionsub', 'id')->where(
                    fn ($query) => $query->where('id_sub', (int) $this->input('entity_id'))
                ),
            ],
            'request_date' => ['required', 'date'],
            'receipt_date' => ['required', 'date'],
            'receiving_method_id' => ['required', 'integer', 'min:1', Rule::exists('g_requestdelivry', 'id')],
            'subject' => ['required', 'string', 'min:3', 'max:100'],
            'section_ids' => ['required', 'array', 'min:1'],
            'section_ids.*' => ['integer', 'min:1', Rule::exists('government_circulars_sections', 'id')],
            'deadline' => ['required', 'date', 'after_or_equal:receipt_date'],
            'reminder_at' => ['nullable', 'date'],
            'attachment_files' => ['nullable', 'array', 'max:5'],
            'attachment_files.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png,xls,xlsx'],
            'attachment_names' => ['nullable', 'array'],
            'attachment_names.*' => ['nullable', 'string', 'max:200'],
            'confirm_entity' => ['accepted'],
            'confirm_subject' => ['accepted'],
            'confirm_department' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirm_entity.accepted' => __('data_requests.validation.confirm_entity'),
            'confirm_subject.accepted' => __('data_requests.validation.confirm_subject'),
            'confirm_department.accepted' => __('data_requests.validation.confirm_department'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'branch_id' => (int) $this->input('branch_id'),
            'entity_id' => (int) $this->input('entity_id'),
            'data_type_id' => (int) $this->input('data_type_id'),
            'request_date' => $this->date('request_date')?->format('Y-m-d'),
            'receipt_date' => $this->date('receipt_date')?->format('Y-m-d\TH:i'),
            'receiving_method_id' => (int) $this->input('receiving_method_id'),
            'subject' => trim((string) $this->input('subject')),
            'section_ids' => array_values(array_unique(array_filter(
                array_map('intval', (array) $this->input('section_ids', [])),
                static fn ($id) => $id > 0
            ))),
            'deadline' => $this->date('deadline')?->format('Y-m-d\TH:i'),
            'reminder_at' => $this->filled('reminder_at')
                ? $this->date('reminder_at')?->format('Y-m-d\TH:i')
                : null,
        ];
    }

    /**
     * @return list<array{file:\Illuminate\Http\UploadedFile,name:string}>
     */
    public function attachments(): array
    {
        $files = array_values(array_filter((array) $this->file('attachment_files', []), fn ($f) => $f !== null));
        $names = (array) $this->input('attachment_names', []);
        $rows = [];

        foreach ($files as $index => $file) {
            $name = trim((string) ($names[$index] ?? ''));
            if ($name === '') {
                $name = pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME);
            }

            $rows[] = [
                'file' => $file,
                'name' => $name,
            ];
        }

        return $rows;
    }
}
