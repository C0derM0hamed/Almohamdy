<?php

namespace App\Http\Requests\GovAccounts;

use Illuminate\Foundation\Http\FormRequest;

class AcceptEmployeeUndertakingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['employee_undertaking' => ['accepted']];
    }
}
