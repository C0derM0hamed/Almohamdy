<?php

namespace App\Services\Inquiries;

use App\Models\Branch;
use App\Models\InquiryAndService;
use App\Models\InquiryAndServiceReply;
use App\Support\Inquiries\InquiryUserNameResolver;
use Carbon\Carbon;

class InquiryTimelineService
{
    /**
     * @return list<array{
     *     actor_name: string,
     *     department: string,
     *     date: string,
     *     time: string,
     *     message: string,
     *     status_color: string,
     *     sort_key: int
     * }>
     */
    public function build(InquiryAndService $inquiry): array
    {
        $events = [];

        $creationTime = $this->inquiryTimestamp($inquiry);

        if ($creationTime !== null) {
            $events[] = $this->formatEvent(
                actorName: InquiryUserNameResolver::resolve((int) $inquiry->created_by),
                department: $this->departmentLabel((int) $inquiry->inquired_section, (int) $inquiry->branch_id),
                datetime: $creationTime,
                message: $this->actionMessage('created'),
                statusColor: '#cbd5e1',
                sortKey: $creationTime->timestamp,
            );
        }

        $replies = $inquiry->relationLoaded('replies')
            ? $inquiry->replies
            : collect();

        foreach ($replies as $reply) {
            $replyTime = $this->replyTimestamp($reply);

            if ($replyTime === null) {
                continue;
            }

            $sectionId = (int) ($reply->inquired_section ?: $inquiry->inquired_section);

            $events[] = $this->formatEvent(
                actorName: InquiryUserNameResolver::resolve((int) $reply->created_by),
                department: $this->departmentLabel($sectionId, (int) ($reply->branch_id ?: $inquiry->branch_id)),
                datetime: $replyTime,
                message: $this->replyMessage($reply),
                statusColor: $reply->status?->badgeColor() ?? '#e2e8f0',
                sortKey: $replyTime->timestamp,
            );
        }

        usort($events, fn (array $a, array $b) => $a['sort_key'] <=> $b['sort_key']);

        return array_values(array_map(function (array $event) {
            unset($event['sort_key']);

            return $event;
        }, $events));
    }

    private function formatEvent(
        string $actorName,
        string $department,
        Carbon $datetime,
        string $message,
        string $statusColor,
        int $sortKey,
    ): array {
        return [
            'actor_name' => $actorName,
            'department' => $department,
            'date' => $datetime->format('Y/m/d'),
            'time' => $datetime->format('h:i A'),
            'message' => $message,
            'status_color' => $statusColor,
            'sort_key' => $sortKey,
        ];
    }

    private function inquiryTimestamp(InquiryAndService $inquiry): ?Carbon
    {
        $timestamp = (int) $inquiry->date;

        if ($timestamp > 0) {
            return Carbon::createFromTimestamp($timestamp);
        }

        if ($inquiry->created_at) {
            return Carbon::parse($inquiry->created_at);
        }

        return null;
    }

    private function replyTimestamp(InquiryAndServiceReply $reply): ?Carbon
    {
        if ($reply->created_at) {
            return Carbon::parse($reply->created_at);
        }

        return null;
    }

    private function departmentLabel(int $sectionId, int $fallbackBranchId): string
    {
        $resolvedId = $sectionId > 0 ? $sectionId : $fallbackBranchId;

        if ($resolvedId > 0) {
            $branch = Branch::query()
                ->select(['id', 'name_en', 'name_ar'])
                ->find($resolvedId);

            if ($branch !== null) {
                return $branch->localizedName();
            }
        }

        return '—';
    }

    private function replyMessage(InquiryAndServiceReply $reply): string
    {
        $details = trim((string) $reply->inquiry_details);

        if ($details !== '') {
            return $details;
        }

        $statusId = (int) $reply->inquiry_status_id;

        if ($statusId > 0) {
            $updateKey = 'inquiries.update_statuses.'.$statusId;
            $updateLabel = __($updateKey);

            if ($updateLabel !== $updateKey) {
                return $updateLabel;
            }

            return $this->actionMessage((string) $statusId, $reply->status?->localizedName());
        }

        return '—';
    }

    private function actionMessage(string $key, ?string $fallback = null): string
    {
        $translationKey = 'inquiries.timeline_actions.'.$key;
        $message = __($translationKey);

        if ($message !== $translationKey) {
            return $message;
        }

        return $fallback ?? '—';
    }
}
