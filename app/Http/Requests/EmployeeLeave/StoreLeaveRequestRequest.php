<?php

namespace App\Http\Requests\EmployeeLeave;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaveRequestRequest extends FormRequest
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
        $otherLeaveTypeId = (int) config('hm.employee_leave.other_leave_type_id', 99);

        return [
            'leave_type' => ['required', 'integer', Rule::in($leaveTypeKeys)],
            'leave_type_other' => [
                Rule::requiredIf(fn () => (int) $this->input('leave_type') === $otherLeaveTypeId),
                'nullable',
                'string',
                'min:2',
                'max:100',
            ],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'min:3', 'max:200'],
        ];
    }

    public function leaveType(): int
    {
        return (int) $this->input('leave_type');
    }

    public function leaveTypeOther(): ?string
    {
        $value = trim((string) $this->input('leave_type_other'));

        return $value === '' ? null : $value;
    }

    public function startDate(): Carbon
    {
        return Carbon::parse((string) $this->input('start_date'))->startOfDay();
    }

    public function endDate(): Carbon
    {
        return Carbon::parse((string) $this->input('end_date'))->startOfDay();
    }

    public function reason(): string
    {
        return trim((string) $this->input('reason'));
    }
}
