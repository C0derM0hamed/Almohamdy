<?php

namespace App\Http\Requests\CorporateCommunications;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class StoreCorporateCommunicationOutgoingLetterRequest extends FormRequest
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
                'nullable',
                'integer',
                'min:1',
                Rule::exists('corporate_communications_sendertitle', 'id'),
            ],
            'issue_date' => ['required', 'date'],
            'recipient_name' => ['required', 'string', 'min:2', 'max:400'],
            'sender_gender' => ['nullable', 'in:1,2'],
            'job_title' => ['nullable', 'string', 'max:1000'],
            'receiving_mechanism_id' => [
                'nullable',
                'integer',
                'min:1',
                Rule::exists('government_circulars_receiving_mechanism', 'id'),
            ],
            'subject' => ['required', 'string', 'min:3', 'max:1000'],
            'letter_content' => ['required', 'string', 'min:10'],
            'response_deadline' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'attachment_files' => ['nullable', 'array', 'max:5'],
            'attachment_files.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png,xls,xlsx'],
            'attachment_names' => ['nullable', 'array'],
            'attachment_names.*' => ['nullable', 'string', 'max:200'],
            'confirm_recipient' => ['accepted'],
            'confirm_content' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirm_recipient.accepted' => __('outgoing_correspondence.validation.confirm_recipient'),
            'confirm_content.accepted' => __('outgoing_correspondence.validation.confirm_content'),
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
            'sender_title_id' => $this->filled('sender_title_id') ? (int) $this->input('sender_title_id') : 0,
            'issue_date' => $this->date('issue_date')?->format('Y-m-d H:i:s'),
            'recipient_name' => trim((string) $this->input('recipient_name')),
            'sender_gender' => $this->filled('sender_gender') ? (string) $this->input('sender_gender') : null,
            'job_title' => trim((string) $this->input('job_title', '')),
            'receiving_mechanism_id' => $this->filled('receiving_mechanism_id')
                ? (int) $this->input('receiving_mechanism_id')
                : null,
            'subject' => trim((string) $this->input('subject')),
            'letter_content' => trim((string) $this->input('letter_content')),
            'response_deadline' => $this->filled('response_deadline')
                ? $this->date('response_deadline')?->format('Y-m-d H:i:s')
                : null,
        ];
    }

    /**
     * @return list<array{file:UploadedFile,name:string}>
     */
    public function attachments(): array
    {
        $files = array_values(array_filter(
            (array) $this->file('attachment_files', []),
            fn ($file) => $file instanceof UploadedFile,
        ));
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
