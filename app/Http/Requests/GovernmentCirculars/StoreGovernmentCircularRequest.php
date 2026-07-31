<?php

namespace App\Http\Requests\GovernmentCirculars;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class StoreGovernmentCircularRequest extends FormRequest
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
            'authority_id' => ['required', 'integer', 'min:1', Rule::exists('government_circulars_issuing_authority', 'id')],
            'classification_id' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('government_circulars_issuing_authority_classification', 'id')
                    ->where(fn ($query) => $query->where('government_circulars_issuing_authority_id', (int) $this->input('authority_id'))),
            ],
            'issue_date' => ['required', 'date'],
            'received_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'receiving_mechanism_id' => ['required', 'integer', 'min:1', Rule::exists('government_circulars_receiving_mechanism', 'id')],
            'subject' => ['required', 'string', 'min:3', 'max:400'],
            'notification_type' => ['required', 'integer', 'min:1', Rule::exists('government_circulars_notification_type', 'id')],
            'branch_id' => ['required', 'integer', 'min:1', Rule::exists('branches', 'id')],
            'section_ids' => ['required', 'array', 'min:1'],
            'section_ids.*' => ['integer', 'min:1', Rule::exists('government_circulars_sections', 'id')],
            'cc_section_ids' => ['nullable', 'array'],
            'cc_section_ids.*' => ['integer', 'min:1', Rule::exists('government_circulars_sections', 'id')],
            'circular_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
            'circular_files' => ['nullable', 'array'],
            'circular_files.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
            'confirm_entity' => ['accepted'],
            'confirm_subject' => ['accepted'],
            'confirm_attachments' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirm_entity.accepted' => __('government_circulars.validation.confirm_entity'),
            'confirm_subject.accepted' => __('government_circulars.validation.confirm_subject'),
            'confirm_attachments.accepted' => __('government_circulars.validation.confirm_attachments'),
            'section_ids.required' => __('government_circulars.validation.section_required'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $primary = array_map('intval', (array) $this->input('section_ids', []));
        $cc = array_map('intval', (array) $this->input('cc_section_ids', []));
        $sectionIds = array_values(array_unique(array_filter(
            array_merge($primary, $cc),
            static fn ($id) => $id > 0
        )));

        return [
            'authority_id' => (int) $this->input('authority_id'),
            'classification_id' => (int) $this->input('classification_id'),
            'issue_date' => $this->date('issue_date')?->format('Y-m-d H:i:s'),
            'received_date' => $this->date('received_date')?->format('Y-m-d H:i:s'),
            'receiving_mechanism_id' => (int) $this->input('receiving_mechanism_id'),
            'subject' => trim((string) $this->input('subject')),
            'notification_type' => (int) $this->input('notification_type'),
            'branch_id' => (int) $this->input('branch_id'),
            'section_ids' => $sectionIds,
        ];
    }

    /**
     * @return list<UploadedFile>
     */
    public function uploadedFiles(): array
    {
        $files = [];

        $primary = $this->file('circular_file');
        if ($primary instanceof UploadedFile) {
            $files[] = $primary;
        }

        foreach ((array) $this->file('circular_files', []) as $file) {
            if ($file instanceof UploadedFile) {
                $files[] = $file;
            }
        }

        return $files;
    }
}
