<?php

namespace App\Http\Requests\Licenses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class LicensePaymentActionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->hasFile('file') && $this->hasFile('attachment')) {
            $this->files->set('file', $this->file('attachment'));
        }
        if (! $this->filled('comment') && $this->filled('description')) {
            $this->merge(['comment' => $this->input('description')]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comment' => ['required_without:file', 'nullable', 'string', 'max:10000'],
            'file' => ['required_without:comment', 'nullable', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,gif,webp,xls,xlsx'],
        ];
    }

    public function comment(): ?string
    {
        $value = trim((string) $this->input('comment', ''));

        return $value !== '' ? $value : null;
    }

    public function attachment(): ?UploadedFile
    {
        $file = $this->file('file');

        return $file instanceof UploadedFile ? $file : null;
    }
}
