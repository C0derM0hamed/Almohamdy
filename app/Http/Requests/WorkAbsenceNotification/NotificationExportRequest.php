<?php

namespace App\Http\Requests\WorkAbsenceNotification;

use App\Http\Requests\WorkAbsenceNotification\Concerns\AuthorizesWorkAbsenceNotification;
use App\Support\WorkAbsenceNotification\WorkAbsenceNotificationPermissions;
use Illuminate\Validation\Rule;

class NotificationExportRequest extends NotificationIndexRequest
{
    use AuthorizesWorkAbsenceNotification;

    public function authorize(): bool
    {
        $this->authorizePermission(WorkAbsenceNotificationPermissions::VIEW);

        return $this->authorizePermission(WorkAbsenceNotificationPermissions::EXPORT);
    }
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'format' => ['required', 'string', Rule::in(['csv', 'excel'])],
        ]);
    }

    public function exportFormat(): string
    {
        return (string) $this->input('format');
    }
}
