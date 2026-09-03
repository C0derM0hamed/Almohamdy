<?php

namespace App\Http\Requests\GovAccounts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGovAccountLifecycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['modify', 'permission_change', 'suspend', 'close'])],
            'service_id' => ['nullable', 'integer'],
            'requested_role_id' => ['nullable', 'required_if:type,permission_change', 'integer'],
            'justification' => ['required', 'string', 'min:3', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function payload(): array
    {
        return array_filter([
            'type' => (string) $this->input('type'),
            'service_id' => $this->filled('service_id') ? $this->integer('service_id') : null,
            'requested_role_id' => $this->filled('requested_role_id') ? $this->integer('requested_role_id') : null,
            'justification' => trim((string) $this->input('justification')),
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ], static fn ($value): bool => $value !== null);
    }
}
