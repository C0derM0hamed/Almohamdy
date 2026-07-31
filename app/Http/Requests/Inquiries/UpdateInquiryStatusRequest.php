<?php

namespace App\Http\Requests\Inquiries;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateInquiryStatusRequest extends FormRequest
{
    public const ASSIGNMENT_DEPARTMENT = 'department';

    public const ASSIGNMENT_EMPLOYEE = 'employee';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notes' => trim((string) $this->input('notes', '')),
            'assignment_type' => trim((string) $this->input('assignment_type', '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $forwardStatusId = (int) config('hm.inquiries.forward_status_id', 999999);
        $allowedStatuses = array_map('intval', config('hm.inquiries.update_status_ids', [3, 4, 5, 999999, 6]));

        return [
            'status_id' => ['required', 'integer', Rule::in($allowedStatuses)],
            'notes' => ['nullable', 'string', 'max:1000'],
            'department_id' => [
                Rule::requiredIf(fn () => (int) $this->input('status_id') === $forwardStatusId),
                'nullable',
                'integer',
                'min:1',
            ],
            'assignment_type' => [
                Rule::requiredIf(fn () => (int) $this->input('status_id') === $forwardStatusId),
                'nullable',
                'string',
                Rule::in([self::ASSIGNMENT_DEPARTMENT, self::ASSIGNMENT_EMPLOYEE]),
            ],
            'employee_id' => [
                Rule::requiredIf(fn () => (int) $this->input('status_id') === $forwardStatusId
                    && $this->input('assignment_type') === self::ASSIGNMENT_EMPLOYEE),
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $forwardStatusId = (int) config('hm.inquiries.forward_status_id', 999999);

            if ((int) $this->input('status_id') !== $forwardStatusId) {
                return;
            }

            if ((int) $this->input('department_id') <= 0) {
                $validator->errors()->add('department_id', __('inquiries.status_form.department_required'));
            }

            if ($this->input('assignment_type') === self::ASSIGNMENT_EMPLOYEE
                && (int) $this->input('employee_id') <= 0) {
                $validator->errors()->add('employee_id', __('inquiries.status_form.employee_required'));
            }
        });
    }

    /**
     * @return array{
     *     status_id:int,
     *     notes:string,
     *     department_id:?int,
     *     assignment_type:?string,
     *     employee_id:?int
     * }
     */
    public function payload(): array
    {
        $statusId = (int) $this->input('status_id');
        $forwardStatusId = (int) config('hm.inquiries.forward_status_id', 999999);
        $isForward = $statusId === $forwardStatusId;

        return [
            'status_id' => $statusId,
            'notes' => trim((string) $this->input('notes', '')),
            'department_id' => $isForward ? (int) $this->input('department_id') : null,
            'assignment_type' => $isForward ? (string) $this->input('assignment_type') : null,
            'employee_id' => $isForward && $this->input('assignment_type') === self::ASSIGNMENT_EMPLOYEE
                ? (int) $this->input('employee_id')
                : null,
        ];
    }
}
