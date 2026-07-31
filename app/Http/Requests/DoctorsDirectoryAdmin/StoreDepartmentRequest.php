<?php

namespace App\Http\Requests\DoctorsDirectoryAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
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
            'speciality_id' => ['required', 'integer', Rule::exists('specialized_clinics', 'id')],
            'department_id' => ['required', 'integer', Rule::exists('outpatient_clinics', 'id')],
            'publish' => ['nullable', 'boolean'],
        ];
    }

    public function specialityId(): int
    {
        return (int) $this->input('speciality_id');
    }

    public function departmentId(): int
    {
        return (int) $this->input('department_id');
    }

    public function publish(): int
    {
        return $this->boolean('publish') ? 1 : 0;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $specialityId = $this->specialityId();
            $departmentId = $this->departmentId();
            $ignoreId = $this->route('section');
            $ignoreId = is_numeric($ignoreId) ? (int) $ignoreId : null;

            $exists = DB::table('outpatient_clinics_sections')
                ->where('specialized_clinics_id', $specialityId)
                ->where('outpatient_clinics_id', $departmentId)
                ->when($ignoreId !== null && $ignoreId > 0, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists();

            if ($exists) {
                $v->errors()->add('department_id', __('doctors_directory_admin.department_assignment_exists'));
            }
        });
    }
}

