<?php

namespace App\Http\Requests\Licenses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\DB;

class UpdateLicensePaymentStatusRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('status') && $this->filled('status_id')) {
            $this->merge(['status' => DB::table('license_payment_request_statuses')->where('id', (int) $this->input('status_id'))->value('code')]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['received', 'in_progress', 'needs_documents', 'paid'])],
            'status_id' => ['nullable', 'integer', Rule::exists('license_payment_request_statuses', 'id')],
            'comment' => ['nullable', 'string', 'max:10000'],
            'proof' => ['nullable', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ((string) $this->input('status') === 'paid' && ! $this->hasFile('proof')) {
                $validator->errors()->add('proof', __('licenses.validation.payment_proof_required'));
            }
        });
    }

    public function statusCode(): string
    {
        return (string) $this->input('status');
    }

    public function comment(): ?string
    {
        $value = trim((string) $this->input('comment', ''));

        return $value !== '' ? $value : null;
    }

    public function proof(): ?UploadedFile
    {
        $file = $this->file('proof');

        return $file instanceof UploadedFile ? $file : null;
    }
}
