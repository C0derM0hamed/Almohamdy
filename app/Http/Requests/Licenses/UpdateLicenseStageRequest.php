<?php

namespace App\Http\Requests\Licenses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLicenseStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['renewal_stage_id' => ['required', 'integer', Rule::exists('license_renewal_stages', 'id')]];
    }

    public function stageId(): int
    {
        return (int) $this->input('renewal_stage_id');
    }
}
