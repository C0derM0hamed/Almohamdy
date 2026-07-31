<?php

namespace App\Services\WorkAbsenceNotification;

use App\Models\AbsenceNotificationService;
use App\Models\AbsenceNotificationServiceMemo;
use Carbon\Carbon;

class AbsenceNotificationTimelineBuilder
{
    /**
     * @return list<array{
     *     stage: string,
     *     label: string,
     *     actor: string,
     *     detail: string,
     *     user: string,
     *     action_type: string,
     *     at: string,
     *     sort_at: int
     * }>
     */
    public function build(AbsenceNotificationService $notification): array
    {
        $events = [];

        $submittedAt = (int) $notification->date;

        if ($submittedAt > 0) {
            $events[] = $this->generalEvent(
                'submitted',
                $notification->employeeDisplayName(),
                '',
                $submittedAt,
            );
        }

        if ((int) $notification->action_type > 0) {
            $events[] = $this->actionProcessedEvent($notification);
        }

        if ($this->hasActivation($notification)) {
            $events[] = $this->notificationActivatedEvent($notification);
        }

        $events = array_merge($events, $this->buildMemoEvents($notification));

        usort($events, fn (array $a, array $b) => $a['sort_at'] <=> $b['sort_at']);

        return $events;
    }

    /**
     * @return list<array{
     *     stage: string,
     *     label: string,
     *     actor: string,
     *     detail: string,
     *     user: string,
     *     action_type: string,
     *     at: string,
     *     sort_at: int
     * }>
     */
    public function buildMemoEvents(AbsenceNotificationService $notification): array
    {
        $events = [];

        foreach ($notification->memos as $memo) {
            $events = array_merge($events, $this->memoEvents($memo));
        }

        usort($events, fn (array $a, array $b) => $a['sort_at'] <=> $b['sort_at']);

        return $events;
    }

    /**
     * @return list<array{
     *     stage: string,
     *     label: string,
     *     actor: string,
     *     detail: string,
     *     user: string,
     *     action_type: string,
     *     at: string,
     *     sort_at: int
     * }>
     */
    private function memoEvents(AbsenceNotificationServiceMemo $memo): array
    {
        $events = [];
        $memoAt = (int) $memo->date;

        if ($memoAt <= 0) {
            return $events;
        }

        $dateRange = $memo->formattedBeginDate().' — '.$memo->formattedEndDate();
        $recipientNames = $this->recipientNames($memo);
        $recipientCount = count($recipientNames);

        $events[] = $this->memoEvent(
            'memo_created',
            $memo->employeeDisplayName(),
            $memo->memoTypeLabel(),
            $dateRange,
            $memoAt,
        );

        if ($recipientCount > 0) {
            $events[] = $this->memoEvent(
                'memo_recipients_assigned',
                $memo->memoTypeLabel(),
                implode(', ', $recipientNames),
                __('work_absence_notification.timeline.memo_recipients_count', ['count' => $recipientCount]),
                $memoAt,
            );
        }

        foreach ($memo->recipients as $recipient) {
            $seenAt = $this->parseDatetime($recipient->seen_at);

            if ($seenAt !== null) {
                $events[] = $this->generalEventFromDatetime(
                    'recipient_viewed',
                    $recipient->recipient?->displayName() ?? '#'.$recipient->user_id,
                    $memo->memoTypeLabel(),
                    $seenAt,
                );
            }
        }

        return $events;
    }

    /**
     * @return list<string>
     */
    private function recipientNames(AbsenceNotificationServiceMemo $memo): array
    {
        $names = [];

        foreach ($memo->recipients as $recipient) {
            $names[] = $recipient->recipient?->displayName() ?? '#'.$recipient->user_id;
        }

        return $names;
    }

    /**
     * @return array{
     *     stage: string,
     *     label: string,
     *     actor: string,
     *     detail: string,
     *     user: string,
     *     action_type: string,
     *     at: string,
     *     sort_at: int
     * }
     */
    private function memoEvent(
        string $stage,
        string $user,
        string $actor,
        string $detail,
        int $timestamp,
    ): array {
        return array_merge($this->auditEvent($stage, $actor, $detail, $timestamp), [
            'user' => $user,
            'action_type' => $actor,
        ]);
    }

    /**
     * @return array{
     *     stage: string,
     *     label: string,
     *     actor: string,
     *     detail: string,
     *     user: string,
     *     action_type: string,
     *     at: string,
     *     sort_at: int
     * }
     */
    private function actionProcessedEvent(AbsenceNotificationService $notification): array
    {
        $actionAt = $this->actionTimestamp($notification);
        $user = $this->userDisplayName($notification, 'actionByUser');
        $actionType = $notification->actionTypeLabel();

        return $this->auditEvent('action_processed', $user, $actionType, $actionAt);
    }

    /**
     * @return array{
     *     stage: string,
     *     label: string,
     *     actor: string,
     *     detail: string,
     *     user: string,
     *     action_type: string,
     *     at: string,
     *     sort_at: int
     * }
     */
    private function notificationActivatedEvent(AbsenceNotificationService $notification): array
    {
        $activatedAt = $notification->activatedAtCarbon();
        $submittedAt = (int) $notification->date;
        $user = $this->userDisplayName($notification, 'activatedByUser');
        $actionType = (int) $notification->action_type > 0
            ? $notification->actionTypeLabel()
            : '—';

        if ($activatedAt !== null) {
            return $this->auditEventFromDatetime(
                'notification_activated',
                $user,
                $actionType,
                $activatedAt,
                $submittedAt,
            );
        }

        return $this->auditEvent(
            'notification_activated',
            $user,
            $actionType,
            $submittedAt,
        );
    }

    private function hasActivation(AbsenceNotificationService $notification): bool
    {
        return (int) $notification->activated_by > 0
            || $notification->activatedAtCarbon() !== null;
    }

    private function actionTimestamp(AbsenceNotificationService $notification): int
    {
        $actionAt = (int) $notification->action_date;

        return $actionAt > 0 ? $actionAt : (int) $notification->date;
    }

    private function userDisplayName(AbsenceNotificationService $notification, string $relation): string
    {
        if ($notification->relationLoaded($relation) && $notification->{$relation}) {
            return $notification->{$relation}->displayName();
        }

        return '—';
    }

    /**
     * @return array{
     *     stage: string,
     *     label: string,
     *     actor: string,
     *     detail: string,
     *     user: string,
     *     action_type: string,
     *     at: string,
     *     sort_at: int
     * }
     */
    private function auditEvent(string $stage, string $user, string $actionType, int $timestamp): array
    {
        $at = $timestamp > 0
            ? Carbon::createFromTimestamp($timestamp)->format('Y-m-d H:i')
            : '—';

        return [
            'stage' => $stage,
            'label' => __('work_absence_notification.timeline.'.$stage),
            'actor' => $user,
            'detail' => $actionType,
            'user' => $user,
            'action_type' => $actionType,
            'at' => $at,
            'sort_at' => $timestamp,
        ];
    }

    /**
     * @return array{
     *     stage: string,
     *     label: string,
     *     actor: string,
     *     detail: string,
     *     user: string,
     *     action_type: string,
     *     at: string,
     *     sort_at: int
     * }
     */
    private function auditEventFromDatetime(
        string $stage,
        string $user,
        string $actionType,
        ?Carbon $datetime,
        int $fallbackSortTimestamp = 0,
    ): array {
        $sortAt = $datetime?->timestamp ?? $fallbackSortTimestamp;

        return [
            'stage' => $stage,
            'label' => __('work_absence_notification.timeline.'.$stage),
            'actor' => $user,
            'detail' => $actionType,
            'user' => $user,
            'action_type' => $actionType,
            'at' => $datetime !== null
                ? $datetime->format('Y-m-d H:i')
                : ($fallbackSortTimestamp > 0
                    ? Carbon::createFromTimestamp($fallbackSortTimestamp)->format('Y-m-d H:i')
                    : '—'),
            'sort_at' => $sortAt,
        ];
    }

    /**
     * @return array{
     *     stage: string,
     *     label: string,
     *     actor: string,
     *     detail: string,
     *     user: string,
     *     action_type: string,
     *     at: string,
     *     sort_at: int
     * }
     */
    private function generalEvent(string $stage, string $actor, string $detail, int $timestamp): array
    {
        return array_merge($this->auditEvent($stage, $actor, $detail, $timestamp), [
            'user' => $actor,
            'action_type' => $detail,
        ]);
    }

    /**
     * @return array{
     *     stage: string,
     *     label: string,
     *     actor: string,
     *     detail: string,
     *     user: string,
     *     action_type: string,
     *     at: string,
     *     sort_at: int
     * }
     */
    private function generalEventFromDatetime(
        string $stage,
        string $actor,
        string $detail,
        ?Carbon $datetime,
    ): array {
        return array_merge(
            $this->auditEventFromDatetime($stage, $actor, $detail, $datetime),
            [
                'user' => $actor,
                'action_type' => $detail,
            ],
        );
    }

    private function parseDatetime(mixed $value): ?Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        $string = trim((string) $value);

        if ($string === '') {
            return null;
        }

        try {
            return Carbon::parse($string);
        } catch (\Throwable) {
            return null;
        }
    }
}
