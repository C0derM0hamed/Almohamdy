<?php

namespace App\Http\Requests\Licenses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreExternalCommunicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reference_no' => ['nullable', 'string', 'max:150'],
            'letter_date' => ['required', 'date'],
            'authority' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:2', 'max:10000'],
            'attachment' => ['nullable', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,gif,webp,xls,xlsx'],
        ];
    }

    public function payload(): array
    {
        return [
            'reference_no' => $this->filled('reference_no') ? trim((string) $this->input('reference_no')) : null,
            'letter_date' => $this->date('letter_date')?->toDateString(),
            'authority' => $this->filled('authority') ? trim((string) $this->input('authority')) : null,
            'description' => trim((string) $this->input('description')),
        ];
    }

    public function attachment(): ?UploadedFile
    {
        $file = $this->file('attachment');

        return $file instanceof UploadedFile ? $file : null;
    }
}
