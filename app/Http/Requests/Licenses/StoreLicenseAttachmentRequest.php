<?php

namespace App\Http\Requests\Licenses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreLicenseAttachmentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->hasFile('file') && $this->hasFile('attachment')) {
            $this->files->set('file', $this->file('attachment'));
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,gif,webp,xls,xlsx'],
            'description' => ['nullable', 'string', 'max:1000'],
            'context' => ['nullable', 'in:license,renewal'],
        ];
    }

    public function attachment(): UploadedFile
    {
        return $this->file('file');
    }

    public function description(): ?string
    {
        $value = trim((string) $this->input('description', ''));

        return $value !== '' ? $value : null;
    }

    public function context(): string
    {
        return (string) $this->input('context', 'license');
    }
}
