<?php

namespace App\Http\Requests\GovAccounts;

use Illuminate\Foundation\Http\FormRequest;

class SaveGovAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['employee_user_id' => ['required', 'integer'], 'department_id' => ['required', 'integer'], 'authority_id' => ['required', 'integer'], 'service_id' => ['required', 'integer'], 'role_id' => ['required', 'integer'], 'justification' => ['required', 'string', 'min:3', 'max:5000'], 'notes' => ['nullable', 'string', 'max:5000']];
    }

    public function payload(): array
    {
        return ['employee_user_id' => $this->integer('employee_user_id'), 'department_id' => $this->integer('department_id'), 'authority_id' => $this->integer('authority_id'), 'service_id' => $this->integer('service_id'), 'role_id' => $this->integer('role_id'), 'justification' => trim((string) $this->input('justification')), 'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null];
    }
}
