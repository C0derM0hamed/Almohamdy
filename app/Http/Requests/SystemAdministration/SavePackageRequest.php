<?php

namespace App\Http\Requests\SystemAdministration;

use App\Services\Auth\PermissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(PermissionService::class)->isAdmin();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'service_id' => ['required', 'integer', 'min:1', Rule::exists('services_sections', 'id')],
            'code1' => ['required', 'string', 'max:100'],
            'name_ar' => ['required', 'string', 'max:500'],
            'name_en' => ['nullable', 'string', 'max:500'],
            'notice_ar' => ['nullable', 'string', 'max:20000'],
            'notice_en' => ['nullable', 'string', 'max:20000'],
            'price' => ['nullable', 'string', 'max:100'],
            'notice1_ar' => ['nullable', 'string', 'max:20000'],
            'notice1_en' => ['nullable', 'string', 'max:20000'],
            'service_details' => ['nullable', 'string', 'max:30000'],
            'consultation_discount' => ['nullable', 'string', 'max:100'],
            'lab_x_rays_discount' => ['nullable', 'string', 'max:100'],
            'operations_hypnosis_discount' => ['nullable', 'string', 'max:100'],
            'delivery_discount' => ['nullable', 'string', 'max:100'],
            'publish' => ['nullable', 'boolean'],
            'attachment_files' => ['nullable', 'array', 'max:10'],
            'attachment_files.*' => ['file', 'max:15360', 'mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx'],
        ];
    }

    /** @return array<string, mixed> */
    public function packageAttributes(): array
    {
        return $this->only([
            'service_id', 'code1', 'name_ar', 'name_en', 'notice_ar', 'notice_en', 'price',
            'notice1_ar', 'notice1_en', 'service_details', 'consultation_discount',
            'lab_x_rays_discount', 'operations_hypnosis_discount', 'delivery_discount', 'publish',
        ]);
    }
}
