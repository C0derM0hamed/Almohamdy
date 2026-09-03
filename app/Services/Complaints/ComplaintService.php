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
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

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
    public function listPaginated(string $search, ?int $status, ?int $perPage = null): LengthAwarePaginator
    {
        $perPage ??= (int) config('hm.complaints.per_page', 15);

        return $this->complaintRepository->paginateFiltered($search, $status, $perPage);
    }

    public function findForDetail(int $id): ?Complaint
    {
        return $this->complaintRepository->findForDetail($id);
    }

    public function departmentOptions(): Collection { return $this->complaintRepository->departmentOptions(); }

    public function create(array $payload, ?UploadedFile $attachment = null): Complaint
    {
        $complaint = $this->complaintRepository->create($payload);

        if ($attachment) {
            $path = $attachment->store('complaints/'.$complaint->id, 'local');
            $reply = new ComplaintReply([
                'complaints_id' => $complaint->id,
                'complaint_status_id' => 1,
                'details' => $payload['details'],
                'created_by' => (int) session('hr_user_id'),
                'file_name' => $path,
            ]);
            $reply->save();
        }

        return $complaint;
    }

    public function addReply(Complaint $complaint, int $statusId, array $payload, ?UploadedFile $attachment = null): ComplaintReply
    {
        $current = (int) $complaint->status;
        if (in_array($current, [5, 6], true)) {
            throw ValidationException::withMessages(['status_id' => __('complaints.workflow.terminal')]);
        }
        if ($statusId >= 2 && $statusId <= 4 && $current > 0 && $statusId !== $current + 1) {
            throw ValidationException::withMessages(['status_id' => __('complaints.workflow.sequential')]);
        }
        if ($statusId === 1 && $current !== 0) {
            throw ValidationException::withMessages(['status_id' => __('complaints.workflow.repeated')]);
        }

        return DB::transaction(function () use ($complaint, $statusId, $payload, $attachment): ComplaintReply {
            $filePath = $attachment?->store('complaints/'.$complaint->id, 'local');
            $reply = ComplaintReply::query()->create([
                'complaints_id' => $complaint->id,
                'complaint_status_id' => $statusId,
                'details' => $payload['details'] ?? null,
                'created_by' => (int) session('hr_user_id'),
                'file_name' => $filePath,
            ]);
            $update = ['status' => $statusId, 'updated_by' => (int) session('hr_user_id'), 'updated_at' => now()];
            if (array_key_exists('status_other', $payload)) $update['status_other'] = $payload['status_other'];
            if (array_key_exists('satis', $payload)) $update['Satis'] = $payload['satis'];
            if (array_key_exists('right2', $payload)) $update['right2'] = $payload['right2'];
            Complaint::query()->whereKey($complaint->id)->update($update);
            return $reply;
        });
    }

    public function replyForDownload(int $complaintId, int $replyId): ?ComplaintReply { return $this->complaintRepository->findReplyForDownload($complaintId, $replyId); }

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
        $events = [];

        foreach ($this->replyRepository->repliesForComplaint($complaintId) as $reply) {
            $statusId = (int) $reply->complaint_status_id;

            $events[] = [
                'reply' => $reply,
                'status_label' => $reply->status?->localizedName() ?? '—',
                'status_color' => $reply->status?->badgeColor() ?? '#e2e8f0',
            ];
        }

        return $events;
    }
}
