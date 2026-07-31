<?php

namespace App\Http\Requests\GovernmentInspectionVisits;

use Illuminate\Foundation\Http\FormRequest;

class StoreGovernmentInspectionVisitAttachmentRequest extends FormRequest
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
            'attachment_name' => ['nullable', 'string', 'max:220'],
            'attachment_file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
        ];
    }

    public function attachmentName(): string
    {
        $name = trim((string) $this->input('attachment_name', ''));

        if ($name !== '') {
            return $name;
        }

        $file = $this->file('attachment_file');

        return $file ? pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME) : 'Attachment';
    }
}
