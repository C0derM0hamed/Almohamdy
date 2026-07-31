<?php

namespace App\Http\Requests\WorkAbsenceNotification;

use App\Http\Requests\WorkAbsenceNotification\Concerns\AuthorizesWorkAbsenceNotification;
use App\Repositories\WorkAbsenceNotification\AbsenceNotificationRepository;
use App\Services\WorkAbsenceNotification\AbsenceNotificationWorkflowResolver;
use App\Services\WorkAbsenceNotification\WorkAbsenceNotificationService;
use App\Support\WorkAbsenceNotification\WorkAbsenceNotificationPermissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ActivateNotificationRequest extends FormRequest
{
    use AuthorizesWorkAbsenceNotification;

    public function authorize(): bool
    {
        return $this->authorizePermission(WorkAbsenceNotificationPermissions::ACTIVATE);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
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

            if (app(WorkAbsenceNotificationService::class)->canActivate($record)) {
                return;
            }

            $status = app(AbsenceNotificationWorkflowResolver::class)->resolve($record);

            if ($status === AbsenceNotificationWorkflowResolver::PENDING) {
                $validator->errors()->add(
                    'notification',
                    __('work_absence_notification.errors.cannot_activate_pending'),
                );

                return;
            }

            $validator->errors()->add(
                'notification',
                $status === AbsenceNotificationWorkflowResolver::ACTIVATED
                    ? __('work_absence_notification.errors.already_activated')
                    : __('work_absence_notification.errors.not_action_taken'),
            );
        });
    }

    public function notificationId(): int
    {
        return (int) $this->route('notification');
    }
}
