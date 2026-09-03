<?php

namespace App\Http\Requests\Licenses;

use Illuminate\Foundation\Http\FormRequest;

class RejectLicenseUndertakingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function rejectionReason(): ?string
    {
        $reason = trim((string) $this->input('rejection_reason', ''));

        return $reason !== '' ? $reason : null;
    }
}
