<?php

namespace App\Http\Requests\GovAccounts;

use App\Models\GovAccountRequest as GovAccountRequestModel;
use Illuminate\Foundation\Http\FormRequest;

class CompleteGovAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if (GovAccountRequestModel::query()->find((int) $this->route('request'))?->type !== 'create') {
            return [];
        }

        return ['username' => ['required', 'string', 'max:255'], 'login_url' => ['nullable', 'url', 'max:2048'], 'reference_no' => ['nullable', 'string', 'max:255'], 'role_id' => ['required', 'integer'], 'account_created_at' => ['required', 'date'], 'notes' => ['nullable', 'string', 'max:5000']];
    }

    public function payload(): array
    {
        return $this->validated();
    }
}
