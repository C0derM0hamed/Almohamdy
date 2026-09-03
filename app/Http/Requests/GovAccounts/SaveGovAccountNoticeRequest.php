<?php

namespace App\Http\Requests\GovAccounts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveGovAccountNoticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'authority_id' => ['required', 'integer'],
            'service_id' => ['nullable', 'integer', 'required_if:targeting_mode,service'],
            'description' => ['required', 'string', 'max:10000'],
            'event_date' => ['required', 'date'],
            'event_time' => ['required', 'date_format:H:i'],
            'meeting_url' => ['nullable', 'url:http,https', 'max:2048'],
            'attendance_method' => ['required', Rule::in(['online', 'in_person', 'hybrid'])],
            'location' => ['nullable', 'string', 'max:255', 'required_if:attendance_method,in_person,hybrid'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'targeting_mode' => ['required', Rule::in(['all', 'users', 'departments', 'service'])],
            'user_ids' => ['nullable', 'array', 'required_if:targeting_mode,users'],
            'user_ids.*' => ['integer', 'distinct'],
            'department_ids' => ['nullable', 'array', 'required_if:targeting_mode,departments'],
            'department_ids.*' => ['integer', 'distinct'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,xls,xlsx'],
        ];
    }

    public function payload(): array
    {
        return [
            'title' => trim((string) $this->input('title')),
            'authority_id' => $this->integer('authority_id'),
            'service_id' => $this->filled('service_id') ? $this->integer('service_id') : null,
            'description' => trim((string) $this->input('description')),
            'event_date' => $this->date('event_date')->format('Y-m-d'),
            'event_time' => (string) $this->input('event_time'),
            'meeting_url' => $this->filled('meeting_url') ? trim((string) $this->input('meeting_url')) : null,
            'attendance_method' => (string) $this->input('attendance_method'),
            'location' => $this->filled('location') ? trim((string) $this->input('location')) : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
            'targeting' => [
                'mode' => (string) $this->input('targeting_mode'),
                'ids' => collect($this->input($this->input('targeting_mode') === 'departments' ? 'department_ids' : 'user_ids', []))->map(fn ($id): int => (int) $id)->unique()->values()->all(),
            ],
        ];
    }

    public function attachments(): array
    {
        return $this->file('attachments', []);
    }
}
