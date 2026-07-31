<?php

namespace App\Http\Requests\GovernmentDataRequests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateGovernmentDataRequestStatusRequest extends FormRequest
{
    public const STATUS_ESCALATED = 1;

    public const STATUS_RETURNED = 2;

    public const STATUS_ENTITY_NOTIFIED = 4;

    public const STATUS_REPLIED_BY_MANAGEMENT = 5;

    /**
     * @return list<int>
     */
    public static function updatableStatusIds(): array
    {
        return [
            self::STATUS_ESCALATED,
            self::STATUS_RETURNED,
            self::STATUS_ENTITY_NOTIFIED,
            self::STATUS_REPLIED_BY_MANAGEMENT,
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => trim((string) $this->input('reason', '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status_id' => ['required', 'integer', Rule::in(self::updatableStatusIds())],
            'reason' => ['nullable', 'string', 'min:3', 'max:500'],
            'notice_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
            'notice_name' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $statusId = (int) $this->input('status_id');

            if (in_array($statusId, [self::STATUS_ESCALATED, self::STATUS_RETURNED], true)
                && ! filled($this->input('reason'))) {
                $validator->errors()->add('reason', __('data_requests.validation.reason_required'));
            }

            if ($statusId === self::STATUS_ENTITY_NOTIFIED && ! $this->hasFile('notice_file')) {
                $validator->errors()->add('notice_file', __('data_requests.validation.notice_file_required'));
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'status_id' => (int) $this->input('status_id'),
            'reason' => trim((string) $this->input('reason', '')),
            'notice_name' => trim((string) $this->input('notice_name', '')),
        ];
    }
}
