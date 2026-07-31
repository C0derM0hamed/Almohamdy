<?php

namespace App\Services\WorkAbsenceNotification;

use App\Models\AbsenceNotificationService;
use App\Models\AbsenceNotificationServiceMemo;
use App\Models\User;
use App\Repositories\WorkAbsenceNotification\AbsenceNotificationMemoRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AbsenceNotificationMemoService
{
    public function __construct(
        private readonly AbsenceNotificationMemoRepository $repository,
        private readonly AbsenceNotificationWorkflowResolver $workflowResolver,
    ) {}

    public function canCreate(AbsenceNotificationService $notification): bool
    {
        return $this->workflowResolver->resolve($notification) === AbsenceNotificationWorkflowResolver::ACTION_TAKEN;
    }

    /**
     * @param list<int> $recipientIds
     */
    public function create(
        int $notificationId,
        int $memoTypeId,
        array $recipientIds,
        ?string $beginDate = null,
        ?string $endDate = null,
        ?string $notes = null,
    ): AbsenceNotificationServiceMemo {
        $notification = $this->repository->findNotificationForMemo($notificationId);

        if ($notification === null) {
            throw ValidationException::withMessages([
                'notification' => __('work_absence_notification.errors.notification_not_found'),
            ]);
        }

        if (! $this->canCreate($notification)) {
            throw ValidationException::withMessages([
                'notification' => __('work_absence_notification.memo.errors.cannot_create'),
            ]);
        }

        $normalizedRecipientIds = array_values(array_unique(array_map('intval', $recipientIds)));

        $memoId = DB::transaction(function () use (
            $notification,
            $memoTypeId,
            $normalizedRecipientIds,
            $beginDate,
            $endDate,
            $notes,
        ): int {
            $attributes = [
                'absence_notification_service_id' => (int) $notification->id,
                'user_id' => (int) $notification->user_id,
                'memo_type' => $memoTypeId,
                'date' => (string) time(),
                'sms_tocken' => $this->generateSmsToken(),
                'begin_date' => $beginDate ?? (string) $notification->begin_date,
                'end_date' => $endDate ?? (string) $notification->end_date,
            ];

            $trimmedNotes = trim((string) $notes);

            if ($trimmedNotes !== '') {
                $attributes['pending_inquiries'] = $trimmedNotes;
            }

            $memoId = $this->repository->createMemo($attributes);

            $this->repository->createRecipients($memoId, $normalizedRecipientIds);

            return $memoId;
        });

        $memo = $this->repository->findMemoForTimeline($memoId);

        if ($memo === null) {
            throw ValidationException::withMessages([
                'memo' => __('work_absence_notification.memo.errors.create_failed'),
            ]);
        }

        return $memo;
    }

    /**
     * @return Collection<int, \App\Models\MemoType>
     */
    public function memoTypeOptions(): Collection
    {
        return $this->repository->memoTypeOptions();
    }

    /**
     * @return Collection<int, User>
     */
    public function recipientOptions(): Collection
    {
        return $this->repository->recipientOptions();
    }

    /**
     * @return array{total: int, viewed: int, pending_view: int}
     */
    public function recipientStatistics(AbsenceNotificationService $notification): array
    {
        $total = 0;
        $viewed = 0;

        foreach ($notification->memos as $memo) {
            foreach ($memo->recipients as $recipient) {
                $total++;

                if ($recipient->isSeen()) {
                    $viewed++;
                }
            }
        }

        return [
            'total' => $total,
            'viewed' => $viewed,
            'pending_view' => max(0, $total - $viewed),
        ];
    }

    /**
     * @return array{recipients_total: int, recipients_viewed: int, recipients_pending_view: int}
     */
    public function recipientDashboardCounts(): array
    {
        return $this->repository->recipientDashboardCounts();
    }

    private function generateSmsToken(): string
    {
        return 'memo'.bin2hex(random_bytes(32));
    }
}
