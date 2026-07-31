<?php

namespace App\Http\Requests\WorkAbsenceNotification;

use App\Http\Requests\WorkAbsenceNotification\Concerns\AuthorizesWorkAbsenceNotification;
use App\Repositories\WorkAbsenceNotification\AbsenceNotificationRepository;
use App\Support\WorkAbsenceNotification\WorkAbsenceNotificationPermissions;
use App\Services\WorkAbsenceNotification\AbsenceNotificationWorkflowResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NotificationIndexRequest extends FormRequest
{
    use AuthorizesWorkAbsenceNotification;

    public function authorize(): bool
    {
        return $this->authorizePermission(WorkAbsenceNotificationPermissions::VIEW);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $typeIds = app(AbsenceNotificationRepository::class)
            ->notificationTypeOptions()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'notification_type' => ['nullable', 'integer', Rule::in($typeIds)],
            'employee' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', Rule::in(AbsenceNotificationWorkflowResolver::statusKeys())],
            'period' => ['nullable', 'string', Rule::in(['this_month'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function dateFrom(): ?Carbon
    {
        if ($this->period() === 'this_month') {
            return Carbon::now()->startOfMonth()->startOfDay();
        }

        $value = trim((string) $this->input('date_from', ''));

        return $value === '' ? null : Carbon::parse($value)->startOfDay();
    }

    public function dateTo(): ?Carbon
    {
        if ($this->period() === 'this_month') {
            return Carbon::now()->endOfMonth()->endOfDay();
        }

        $value = trim((string) $this->input('date_to', ''));

        return $value === '' ? null : Carbon::parse($value)->endOfDay();
    }

    public function notificationTypeId(): ?int
    {
        $value = $this->input('notification_type');

        return $value === null || $value === '' ? null : (int) $value;
    }

    public function employeeSearch(): string
    {
        return trim((string) $this->input('employee', ''));
    }

    public function workflowStatus(): ?string
    {
        $value = trim((string) $this->input('status', ''));

        return $value === '' ? null : $value;
    }

    public function period(): ?string
    {
        $value = trim((string) $this->input('period', ''));

        return $value === '' ? null : $value;
    }

    public function hasFilters(): bool
    {
        return $this->dateFrom() !== null
            || $this->dateTo() !== null
            || $this->notificationTypeId() !== null
            || $this->employeeSearch() !== ''
            || $this->workflowStatus() !== null
            || $this->period() !== null;
    }
}
