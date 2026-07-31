<?php

namespace App\Http\Requests\WorkAbsenceNotification;

use App\Http\Requests\WorkAbsenceNotification\Concerns\AuthorizesWorkAbsenceNotification;
use App\Repositories\WorkAbsenceNotification\AbsenceNotificationRepository;
use App\Services\WorkAbsenceNotification\WorkAbsenceNotificationService;
use App\Support\WorkAbsenceNotification\WorkAbsenceNotificationPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProcessNotificationActionRequest extends FormRequest
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
        $actionTypeIds = app(AbsenceNotificationRepository::class)
            ->actionTypeOptions()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return [
            'action_type' => ['required', 'integer', Rule::in($actionTypeIds)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $record = app(AbsenceNotificationRepository::class)->findForProcessing($this->notificationId());

            if ($record === null) {
                $validator->errors()->add(
                    'notification',
                    __('work_absence_notification.errors.notification_not_found'),
                );

                return;
            }

            if (! app(WorkAbsenceNotificationService::class)->canProcess($record)) {
                $validator->errors()->add(
                    'action_type',
                    __('work_absence_notification.errors.not_pending'),
                );
            }
        });
    }

    public function notificationId(): int
    {
        return (int) $this->route('notification');
    }

    public function actionTypeId(): int
    {
        return (int) $this->input('action_type');
    }
}
