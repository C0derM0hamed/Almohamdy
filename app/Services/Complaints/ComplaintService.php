<?php

namespace App\Services\Complaints;

use App\Models\Complaint;
use App\Models\ComplaintReply;
use App\Models\ComplaintStatus;
use App\Repositories\Complaints\ComplaintReplyRepository;
use App\Repositories\Complaints\ComplaintRepository;
use App\Repositories\Complaints\ComplaintStatusRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ComplaintService
{
    public function __construct(
        private readonly ComplaintRepository $complaintRepository,
        private readonly ComplaintReplyRepository $replyRepository,
        private readonly ComplaintStatusRepository $statusRepository,
    ) {}

    /**
     * @return array{total: int, active: int, processed: int, closed: int}
     */
    public function dashboardSummary(): array
    {
        return $this->complaintRepository->dashboardCounts();
    }

    /**
     * @return list<array{label: string, value: int, icon: string, icon_class?: string, progress?: float}>
     */
    public function summaryCards(array $summary): array
    {
        return [
            [
                'label' => __('complaints.stats.total'),
                'value' => (int) ($summary['total'] ?? 0),
                'icon' => 'chat-square-text',
            ],
            [
                'label' => __('complaints.stats.active'),
                'value' => (int) ($summary['active'] ?? 0),
                'icon' => 'lightning-charge',
                'icon_class' => 'cp-summary-card__icon--warning',
            ],
            [
                'label' => __('complaints.stats.processed'),
                'value' => (int) ($summary['processed'] ?? 0),
                'icon' => 'check-circle',
                'icon_class' => 'cp-summary-card__icon--success',
            ],
            [
                'label' => __('complaints.stats.closed'),
                'value' => (int) ($summary['closed'] ?? 0),
                'icon' => 'archive',
                'icon_class' => 'cp-summary-card__icon--muted',
            ],
        ];
    }

    /**
     * @return array{
     *     processing_rate: int,
     *     most_active_department: string,
     *     latest_update: string|null,
     *     latest_update_label: string
     * }
     */
    public function dashboardInsights(): array
    {
        return $this->complaintRepository->dashboardInsights();
    }

    /**
     * @return LengthAwarePaginator<int, Complaint>
     */
    public function listPaginated(string $search, ?int $status): LengthAwarePaginator
    {
        $perPage = (int) config('hm.complaints.per_page', 15);

        return $this->complaintRepository->paginateFiltered($search, $status, $perPage);
    }

    public function findForDetail(int $id): ?Complaint
    {
        return $this->complaintRepository->findForDetail($id);
    }

    /**
     * @return Collection<int, ComplaintStatus>
     */
    public function statusOptions(): Collection
    {
        return $this->statusRepository->published();
    }

    public function statusLabel(Complaint $complaint): string
    {
        if ((int) $complaint->status === 0) {
            return __('complaints.status.new');
        }

        if ($complaint->relationLoaded('currentStatus') && $complaint->currentStatus) {
            return $complaint->currentStatus->localizedName();
        }

        return '—';
    }

    public function statusColor(Complaint $complaint): string
    {
        if ((int) $complaint->status === 0) {
            return '#e9ecef';
        }

        if ($complaint->relationLoaded('currentStatus') && $complaint->currentStatus) {
            return $complaint->currentStatus->badgeColor();
        }

        return '#e2e8f0';
    }

    /**
     * @return list<array{reply: ComplaintReply, status_label: string, status_color: string}>
     */
    public function timeline(int $complaintId): array
    {
        $seenStatusIds = [];
        $events = [];

        foreach ($this->replyRepository->repliesForComplaint($complaintId) as $reply) {
            $statusId = (int) $reply->complaint_status_id;

            if (in_array($statusId, $seenStatusIds, true)) {
                continue;
            }

            $seenStatusIds[] = $statusId;

            $events[] = [
                'reply' => $reply,
                'status_label' => $reply->status?->localizedName() ?? '—',
                'status_color' => $reply->status?->badgeColor() ?? '#e2e8f0',
            ];
        }

        return $events;
    }
}
