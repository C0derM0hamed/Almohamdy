<?php

namespace App\Http\Requests\WorkAbsenceNotification;

use App\Http\Requests\WorkAbsenceNotification\Concerns\AuthorizesWorkAbsenceNotification;
use App\Repositories\WorkAbsenceNotification\AbsenceNotificationMemoRepository;
use App\Repositories\WorkAbsenceNotification\AbsenceNotificationRepository;
use App\Services\WorkAbsenceNotification\AbsenceNotificationMemoService;
use App\Support\WorkAbsenceNotification\WorkAbsenceNotificationPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreateMemoRequest extends FormRequest
{
    use AuthorizesWorkAbsenceNotification;

    public function authorize(): bool
    {
        return $this->authorizePermission(WorkAbsenceNotificationPermissions::PROCESS);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $memoTypeIds = app(AbsenceNotificationMemoRepository::class)
            ->memoTypeOptions()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return [
            'memo_type' => ['required', 'integer', Rule::in($memoTypeIds)],
            'recipient_ids' => ['required', 'array', 'min:1'],
            'recipient_ids.*' => ['required', 'integer', 'distinct', 'min:1'],
            'begin_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:begin_date'],
            'notes' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $notification = app(AbsenceNotificationRepository::class)->findForProcessing($this->notificationId());

            if ($notification === null) {
                $validator->errors()->add(
                    'notification',
                    __('work_absence_notification.errors.notification_not_found'),
                );

                return;
            }

            if (! app(AbsenceNotificationMemoService::class)->canCreate($notification)) {
                $validator->errors()->add(
                    'notification',
                    __('work_absence_notification.memo.errors.cannot_create'),
                );

                return;
            }

            $requestedRecipients = $this->recipientIds();
            $validRecipients = app(AbsenceNotificationMemoRepository::class)->validRecipientIds($requestedRecipients);

            if (count($validRecipients) !== count($requestedRecipients)) {
                $validator->errors()->add(
                    'recipient_ids',
                    __('work_absence_notification.memo.errors.invalid_recipients'),
                );
            }
        });
    }

    public function notificationId(): int
    {
        return (int) $this->route('notification');
    }

    public function memoTypeId(): int
    {
        return (int) $this->input('memo_type');
    }

    /**
     * @return list<int>
     */
    public function recipientIds(): array
    {
        return collect((array) $this->input('recipient_ids', []))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function beginDate(): ?string
    {
        $value = trim((string) $this->input('begin_date', ''));

        return $value === '' ? null : $value;
    }

    public function endDate(): ?string
    {
        $value = trim((string) $this->input('end_date', ''));

        return $value === '' ? null : $value;
    }

    public function notes(): ?string
    {
        $value = trim((string) $this->input('notes', ''));

        return $value === '' ? null : $value;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'memo_type' => __('work_absence_notification.memo.fields.memo_type'),
            'recipient_ids' => __('work_absence_notification.memo.fields.recipient_ids'),
            'recipient_ids.*' => __('work_absence_notification.memo.fields.recipient_ids'),
            'begin_date' => __('work_absence_notification.memo.fields.begin_date'),
            'end_date' => __('work_absence_notification.memo.fields.end_date'),
            'notes' => __('work_absence_notification.memo.fields.notes'),
        ];
    }
}
