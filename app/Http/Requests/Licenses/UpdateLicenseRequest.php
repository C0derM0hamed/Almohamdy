<?php

namespace App\Http\Requests\Licenses;

class UpdateLicenseRequest extends StoreLicenseRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['attachments'], $rules['attachments.*']);

        return $rules;
    }
}
