<?php

namespace App\Http\Requests\WorkAbsenceNotification;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAbsenceNotificationRequest extends FormRequest
{
    private const RELATIONSHIPS = ['أب', 'أم', 'أخ', 'أخت', 'ابن', 'ابنة', 'زوج', 'زوجة'];

    public function authorize(): bool
    {
        return (int) session('hr_user_id', 0) > 0
            && (int) session('hr_branch_id', 0) > 0
            && (int) session('companies_groups_id', 0) > 0;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $type = (int) $this->input('memo_types_id');
        $requiresDocumentChoice = in_array($type, [1, 2, 3, 4], true);
        $yesterday = Carbon::today()->subDay()->toDateString();
        $tomorrow = Carbon::today()->addDay()->toDateString();

        return [
            'memo_types_id' => [
                'required',
                'integer',
                Rule::exists('absence_notification_service_types', 'id')->where('publish', 1),
                Rule::in([1, 2, 3, 4, 5]),
            ],
            'begin_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.$yesterday,
                $type === 4 ? 'before_or_equal:today' : 'before_or_equal:'.$tomorrow,
            ],
            'end_date' => [
                Rule::requiredIf(in_array($type, [1, 2], true)),
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:begin_date',
            ],
            'document_status' => [Rule::requiredIf($requiresDocumentChoice), 'nullable', Rule::in(['1', '2', 1, 2])],
            'sick_leave_file' => [
                Rule::requiredIf($requiresDocumentChoice && (int) $this->input('document_status') === 1),
                'nullable',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,gif,pdf',
                'mimetypes:image/jpeg,image/png,image/gif,application/pdf',
            ],
            'relationship' => [Rule::requiredIf($type === 2), 'nullable', 'string', Rule::in(self::RELATIONSHIPS)],
            'deceased_relationship' => [
                Rule::requiredIf($type === 4),
                'nullable',
                'integer',
                Rule::exists('absence_notification_service_death_leave_categories', 'id')->where('publish', 1),
            ],
            'medical_authority' => [Rule::requiredIf($type === 3), 'nullable', 'string', 'max:255'],
            'absence_days' => [Rule::requiredIf($type === 5), 'nullable', 'integer', 'min:0', 'max:365'],
            'absence_reason' => [Rule::requiredIf($type === 5), 'nullable', 'string', 'max:255'],
            'acknowledgement' => ['accepted'],
        ];
    }

    /** @return array<string, mixed> */
    public function requestData(): array
    {
        return [
            'memo_types_id' => (int) $this->input('memo_types_id'),
            'begin_date' => (string) $this->input('begin_date'),
            'end_date' => $this->nullableString('end_date'),
            'relationship' => $this->nullableString('relationship'),
            'deceased_relationship' => $this->filled('deceased_relationship') ? (int) $this->input('deceased_relationship') : null,
            'medical_authority' => $this->nullableString('medical_authority'),
            'absence_days' => $this->filled('absence_days') ? (int) $this->input('absence_days') : 0,
            'absence_reason' => $this->nullableString('absence_reason'),
        ];
    }

    private function nullableString(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
