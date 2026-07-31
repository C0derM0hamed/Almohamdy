<?php

namespace App\Http\Requests\EmployeeLeave;

use App\Http\Requests\EmployeeLeave\Concerns\AuthorizesEmployeeLeave;
use App\Repositories\EmployeeLeave\LeaveRequestRepository;
use App\Services\EmployeeLeave\LeaveRequestService;
use App\Support\EmployeeLeave\EmployeeLeavePermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProcessBranchLeaveRequest extends FormRequest
{
    use AuthorizesEmployeeLeave;

    public function authorize(): bool
    {
        return $this->authorizePermission(EmployeeLeavePermissions::BRANCH_PROCESS);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', Rule::in(['approve', 'reject'])],
            'comment' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $record = app(LeaveRequestRepository::class)->findForDetail($this->leaveId());

            if ($record === null) {
                $validator->errors()->add('leave', __('employee_leave.errors.not_found'));

                return;
            }

            if (! app(LeaveRequestService::class)->canProcessBranch($record)) {
                $validator->errors()->add('decision', __('employee_leave.errors.branch_not_pending'));
            }
        });
    }

    public function leaveId(): int
    {
        return (int) $this->route('leave');
    }

    public function decision(): string
    {
        return (string) $this->input('decision');
    }

    public function comment(): string
    {
        return trim((string) $this->input('comment'));
    }
}
