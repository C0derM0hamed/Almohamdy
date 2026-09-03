<?php

namespace App\Http\Requests\Licenses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class CompleteLicenseRenewalRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->hasFile('license_copy') && $this->hasFile('attachment')) {
            $this->files->set('license_copy', $this->file('attachment'));
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'new_expiry_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'license_copy' => ['nullable', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png'],
        ];
    }

    public function newExpiryDate(): string
    {
        return (string) $this->date('new_expiry_date')?->toDateString();
    }

    public function notes(): ?string
    {
        $value = trim((string) $this->input('notes', ''));

        return $value !== '' ? $value : null;
    }

    public function licenseCopy(): ?UploadedFile
    {
        $file = $this->file('license_copy');

        return $file instanceof UploadedFile ? $file : null;
    }
}
