<?php

namespace App\Http\Requests\Licenses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreLicensePaymentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->hasFile('attachment') && ! $this->hasFile('attachments')) {
            $this->files->set('attachments', [$this->file('attachment')]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999.99'],
            'currency' => ['nullable', 'string', 'size:3', 'alpha'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_iban' => ['nullable', 'string', 'max:100'],
            'transfer_details' => ['nullable', 'string', 'max:5000'],
            'invoice_number' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,gif,webp,xls,xlsx'],
        ];
    }

    public function payload(): array
    {
        $payload = $this->safe()->except('attachments');
        $payload['amount'] = (float) $this->input('amount');
        $payload['currency'] = strtoupper((string) $this->input('currency', 'SAR'));

        foreach (['bank_name', 'account_iban', 'transfer_details', 'invoice_number', 'notes'] as $key) {
            $value = trim((string) ($payload[$key] ?? ''));
            $payload[$key] = $value !== '' ? $value : null;
        }

        return $payload;
    }

    /** @return list<UploadedFile> */
    public function attachments(): array
    {
        return array_values(array_filter((array) $this->file('attachments', []), fn ($file) => $file instanceof UploadedFile));
    }
}
