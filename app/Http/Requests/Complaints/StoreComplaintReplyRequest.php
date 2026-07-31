<?php

namespace App\Http\Requests\Complaints;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComplaintReplyRequest extends FormRequest
{
    public function authorize(): bool { return (int) session('hr_user_id') > 0; }

    public function rules(): array
    {
        return [
            'status_id' => ['required', 'integer', Rule::exists('complaints_status', 'id')->where('publish', 1)],
            'details' => ['required_if:status_id,1,2,3,4,5,6', 'string', 'max:10000'],
            'status_other' => ['nullable', 'string', 'max:255'],
            'satis' => ['nullable', 'string', 'max:50'],
            'right2' => ['nullable', 'string', 'max:20'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,pdf', 'max:10240'],
        ];
    }
}
