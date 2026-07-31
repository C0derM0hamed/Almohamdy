<?php

namespace App\Http\Requests\CorporateCommunications;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class StoreCorporateCommunicationRequest extends FormRequest
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
            'sector_id' => ['required', 'integer', 'min:1', Rule::exists('corporate_communications_sectors', 'id')],
            'authority_id' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('government_circulars_issuing_authority', 'id'),
            ],
            'sender_title_id' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('corporate_communications_sendertitle', 'id'),
            ],
            'issue_date' => ['required', 'date'],
            'received_date' => ['required', 'date'],
            'receiving_mechanism_id' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('government_circulars_receiving_mechanism', 'id'),
            ],
            'sender_gender' => ['required', 'in:1,2'],
            'sender' => ['required', 'string', 'min:2', 'max:200'],
            'job_title' => ['required', 'string', 'min:2', 'max:200'],
            'subject' => ['required', 'string', 'min:3', 'max:500'],
            'section_id' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('government_circulars_sections', 'id'),
            ],
            'response_deadline' => ['required', 'date', 'after_or_equal:received_date'],
            'attachment_files' => ['nullable', 'array', 'max:5'],
            'attachment_files.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png,xls,xlsx'],
            'confirm_sender' => ['accepted'],
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
            'confirm_sender.accepted' => __('correspondence.validation.confirm_sender'),
            'confirm_subject.accepted' => __('correspondence.validation.confirm_subject'),
            'confirm_department.accepted' => __('correspondence.validation.confirm_department'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'branch_id' => (int) $this->input('branch_id'),
            'sector_id' => (int) $this->input('sector_id'),
            'authority_id' => (int) $this->input('authority_id'),
            'sender_title_id' => (int) $this->input('sender_title_id'),
            'issue_date' => $this->date('issue_date')?->format('Y-m-d H:i:s'),
            'received_date' => $this->date('received_date')?->format('Y-m-d H:i:s'),
            'receiving_mechanism_id' => (int) $this->input('receiving_mechanism_id'),
            'sender_gender' => (string) $this->input('sender_gender'),
            'sender' => trim((string) $this->input('sender')),
            'job_title' => trim((string) $this->input('job_title')),
            'subject' => trim((string) $this->input('subject')),
            'section_id' => (int) $this->input('section_id'),
            'response_deadline' => $this->date('response_deadline')?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return list<UploadedFile>
     */
    public function attachments(): array
    {
        return array_values(array_filter(
            (array) $this->file('attachment_files', []),
            fn ($file) => $file instanceof UploadedFile,
        ));
    }
}
