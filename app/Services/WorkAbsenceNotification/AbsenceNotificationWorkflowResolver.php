<?php

namespace App\Services\WorkAbsenceNotification;

use App\Models\AbsenceNotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AbsenceNotificationWorkflowResolver
{
    public const PENDING = 'pending';

    public const ACTION_TAKEN = 'action_taken';

    public const ACTIVATED = 'activated';

    /**
     * @return list<string>
     */
    public static function statusKeys(): array
    {
        return [
            self::PENDING,
            self::ACTION_TAKEN,
            self::ACTIVATED,
        ];
    }

    public function resolve(AbsenceNotificationService $notification): string
    {
        if ($this->isActivated($notification)) {
            return self::ACTIVATED;
        }

        if ($this->isActionTaken($notification)) {
            return self::ACTION_TAKEN;
        }

        return self::PENDING;
    }

    public function label(string $statusKey): string
    {
        return __('work_absence_notification.status.'.$statusKey);
    }

    /**
     * @return array{total: int, pending: int, action_taken: int, activated: int, this_month: int}
     */
    public function summarize(Collection $records, int $monthStart, int $monthEnd): array
    {
        $summary = [
            'total' => $records->count(),
            'pending' => 0,
            'action_taken' => 0,
            'activated' => 0,
            'this_month' => 0,
        ];

        foreach ($records as $record) {
            $status = $this->resolve($record);
            $summary[$status]++;

            $createdAt = (int) $record->date;

            if ($createdAt >= $monthStart && $createdAt <= $monthEnd) {
                $summary['this_month']++;
            }
        }

        return $summary;
    }

    public function applyWorkflowStatusFilter(Builder $query, string $status): Builder
    {
        return match ($status) {
            self::PENDING => $query->where(function (Builder $inner) {
                $inner->whereNull('action_type')
                    ->orWhere('action_type', 0);
            }),
            self::ACTION_TAKEN => $query->where('action_type', '>', 0)
                ->where(function (Builder $inner) {
                    $inner->whereNull('activated_by')
                        ->orWhere('activated_by', 0);
                })
                ->where(function (Builder $inner) {
                    $inner->whereNull('activated_at')
                        ->orWhere('activated_at', '');
                }),
            self::ACTIVATED => $query->where(function (Builder $inner) {
                $inner->where('activated_by', '>', 0)
                    ->orWhere(function (Builder $activatedAt) {
                        $activatedAt->whereNotNull('activated_at')
                            ->where('activated_at', '!=', '');
                    });
            }),
            default => $query,
        };
    }

    private function isActivated(AbsenceNotificationService $notification): bool
    {
        if ((int) $notification->activated_by > 0) {
            return true;
        }

        return $notification->activatedAtCarbon() !== null;
    }

    private function isActionTaken(AbsenceNotificationService $notification): bool
    {
        return (int) $notification->action_type > 0 && ! $this->isActivated($notification);
    }
}
