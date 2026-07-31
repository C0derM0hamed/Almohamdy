<?php

namespace App\Http\Requests\EmployeeLeave;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $leaveTypeKeys = array_keys(config('hm.employee_leave.leave_types', []));

        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(['pending', 'approved', 'rejected', ''])],
            'leave_type' => ['nullable', 'integer', Rule::in($leaveTypeKeys)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function search(): string
    {
        return trim((string) $this->input('search', ''));
    }

    public function status(): ?string
    {
        $status = trim((string) $this->input('status', ''));

        return $status === '' ? null : $status;
    }

    public function leaveType(): ?int
    {
        $value = $this->input('leave_type');

        return $value === null || $value === '' ? null : (int) $value;
    }

    public function hasFilters(): bool
    {
        return $this->search() !== ''
            || $this->status() !== null
            || $this->leaveType() !== null;
    }
}
