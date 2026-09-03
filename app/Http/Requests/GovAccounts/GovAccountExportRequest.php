<?php

namespace App\Http\Requests\GovAccounts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GovAccountExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report' => ['nullable', Rule::in(['accounts', 'requests', 'notices'])],
            'status' => ['nullable', 'string', 'max:30'],
            'type' => ['nullable', Rule::in(['create', 'modify', 'permission_change', 'suspend', 'close'])],
            'employee_user_id' => ['nullable', 'integer'],
            'authority_id' => ['nullable', 'integer'],
            'service_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    public function filters(): array
    {
        return [
            'report' => (string) $this->input('report', 'accounts'),
            'status' => $this->filled('status') ? (string) $this->input('status') : null,
            'type' => $this->filled('type') ? (string) $this->input('type') : null,
            'employee_user_id' => $this->filled('employee_user_id') ? $this->integer('employee_user_id') : null,
            'authority_id' => $this->filled('authority_id') ? $this->integer('authority_id') : null,
            'service_id' => $this->filled('service_id') ? $this->integer('service_id') : null,
            'date_from' => $this->filled('date_from') ? $this->date('date_from')->format('Y-m-d') : null,
            'date_to' => $this->filled('date_to') ? $this->date('date_to')->format('Y-m-d') : null,
        ];
    }
}
