<?php

namespace App\Http\Requests\DoctorsDirectoryAdmin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDoctorRequest extends FormRequest
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
            'specialized_clinics_id' => ['required', 'integer', Rule::exists('specialized_clinics', 'id')],
            'code' => ['required', 'string', 'max:50'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'specialization_ar' => ['nullable', 'string', 'max:255'],
            'specialization_en' => ['nullable', 'string', 'max:255'],
            'holds_ar' => ['nullable', 'string', 'max:5000'],
            'holds_en' => ['nullable', 'string', 'max:5000'],
            'cases_ar' => ['nullable', 'string', 'max:5000'],
            'cases_en' => ['nullable', 'string', 'max:5000'],
            'age' => ['nullable', 'string', 'max:100'],
            'price' => ['nullable', 'integer', 'min:0'],
            'country_id' => ['nullable', 'integer', Rule::exists('countries', 'id')],
            'mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'ranking' => ['nullable', 'integer', 'min:0'],
            'publish' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $doctorId = $this->route('doctor');
            $doctorId = is_numeric($doctorId) ? (int) $doctorId : null;

            $service = app(\App\Services\DoctorsDirectoryAdmin\DoctorService::class);

            if ($service->codeExists($this->code(), $doctorId)) {
                $v->errors()->add('code', __('doctors_directory_admin.doctor_code_exists'));
            }
        });
    }

    public function specialityId(): int
    {
        return (int) $this->input('specialized_clinics_id');
    }

    public function code(): string
    {
        return trim((string) $this->input('code'));
    }

    public function publish(): string
    {
        return $this->boolean('publish') ? '1' : '0';
    }

    public function countryId(): ?int
    {
        $value = $this->input('country_id');

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    public function removePhoto(): bool
    {
        return $this->boolean('remove_photo');
    }

    /**
     * @return array<string, mixed>
     */
    public function doctorAttributes(): array
    {
        return [
            'specialized_clinics_id' => $this->specialityId(),
            'code' => $this->code(),
            'name_ar' => trim((string) $this->input('name_ar')),
            'name_en' => trim((string) $this->input('name_en')),
            'specialization_ar' => trim((string) $this->input('specialization_ar', '')),
            'specialization_en' => trim((string) $this->input('specialization_en', '')),
            'holds_ar' => (string) $this->input('holds_ar', ''),
            'holds_en' => (string) $this->input('holds_en', ''),
            'cases_ar' => (string) $this->input('cases_ar', ''),
            'cases_en' => (string) $this->input('cases_en', ''),
            'age' => trim((string) $this->input('age', '')),
            'price' => is_numeric($this->input('price')) ? (int) $this->input('price') : 0,
            'country_id' => $this->countryId(),
            'mobile' => trim((string) $this->input('mobile', '')),
            'email' => trim((string) $this->input('email', '')),
            'ranking' => is_numeric($this->input('ranking')) ? (int) $this->input('ranking') : 0,
            'publish' => $this->publish(),
        ];
    }
}
