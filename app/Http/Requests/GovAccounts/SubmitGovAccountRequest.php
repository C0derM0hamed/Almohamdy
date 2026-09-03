<?php

namespace App\Http\Requests\GovAccounts;

use Illuminate\Foundation\Http\FormRequest;

class SubmitGovAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['manager_undertaking' => ['accepted']];
    }
}
