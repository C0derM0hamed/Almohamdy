<?php

namespace App\Http\Requests\SystemAdministration;

use App\Support\DemoAccounts;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $routeUser = $this->route('user');
        $userId = is_object($routeUser) ? $routeUser->hr_id : $routeUser;

        return [
            'hr_first_name' => ['required', 'string', 'max:150'],
            'hr_last_name' => ['nullable', 'string', 'max:150'],
            'hr_email_address' => ['required', 'email', 'max:50', Rule::unique('ra_users', 'hr_email_address')
                ->ignore($userId, 'hr_id')
                // Narrow exception: the dedicated demo accounts deliberately
                // share two real OTP inboxes, so their rows must not block a
                // normal user from using an address. Uniqueness between
                // normal users is unchanged.
                ->whereNotIn('hr_username', DemoAccounts::usernames())],
            'hr_username' => ['required', 'string', 'max:25', Rule::unique('ra_users', 'hr_username')->ignore($userId, 'hr_id')],
            'password' => [$userId ? 'nullable' : 'required', 'string', 'min:8', 'max:64', 'confirmed'],
            'mobile' => ['nullable', 'string', 'regex:/^05[0-9]{8}$/'],
            'hr_user_level' => ['required', Rule::in(['0', '1', '2', '3', '4'])],
            'companies_groups_id' => ['required', 'integer', 'min:1'],
            // branch_id is retained as the primary legacy branch. New forms
            // submit branch_ids[] so one user can be assigned to multiple
            // branches without breaking older modules.
            'branch_id' => ['required', 'integer', 'min:1'],
            'branch_ids' => ['nullable', 'array', 'min:1'],
            'branch_ids.*' => ['integer', 'distinct', 'min:1'],
            'groupid' => ['required', 'integer', 'min:0'],
            'activated' => ['required', Rule::in(['0', '1'])],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'max:255'],
        ];
    }
}
