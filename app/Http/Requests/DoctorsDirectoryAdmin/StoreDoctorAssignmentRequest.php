<?php

namespace App\Http\Requests\DoctorsDirectoryAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDoctorAssignmentRequest extends FormRequest
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
        return [
            'department_id' => ['required', 'integer', Rule::exists('outpatient_clinics', 'id')],
        ];
    }

    public function departmentId(): int
    {
        return (int) $this->input('department_id');
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $doctorId = $this->route('doctor');
            $doctorId = is_numeric($doctorId) ? (int) $doctorId : 0;

            if ($doctorId <= 0) {
                return;
            }

            $service = app(\App\Services\DoctorsDirectoryAdmin\DoctorService::class);
            $doctor = $service->findForEdit($doctorId);

            if ($doctor === null) {
                return;
            }

            if (! $service->isDepartmentLinkedToSpeciality((int) $doctor->specialized_clinics_id, $this->departmentId())) {
                $v->errors()->add('department_id', __('doctors_directory_admin.doctor_assignment_invalid_department'));

                return;
            }

            if ($service->assignmentExists($doctorId, $this->departmentId())) {
                $v->errors()->add('department_id', __('doctors_directory_admin.doctor_assignment_exists'));
            }
        });
    }
}
