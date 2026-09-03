<?php

namespace App\Repositories\Inquiries;

use App\Models\Branch;
use App\Models\InquiryAndService;
use App\Models\InquiryAndServiceReply;
use App\Models\InquiryAndServiceStatus;
use App\Models\Inquiry;
use App\Models\JobTitle;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InquiryAndServiceRepository
{
    /**
     * @var list<string>
     */
    private const LIST_COLUMNS = [
        'id',
        'date',
        'branch_id',
        'enquirer',
        'inquired_section',
        'mobile',
        'inquiry_id',
        'inquiry_details',
        'status',
        'companies_groups_id',
        'created_at',
    ];

    /**
     * @var list<string>
     */
    private const DETAIL_COLUMNS = [
        'id',
        'date',
        'branch_id',
        'enquirer',
        'inquired_section',
        'mobile',
        'inquiry_id',
        'inquiry_details',
        'status',
        'companies_groups_id',
        'created_by',
        'created_at',
        'job_title',
        'job_title_sender',
        'noAnswerTimes',
    ];

    public function scopedQuery(string $direction): Builder
    {
        $query = InquiryAndService::query()->select(self::LIST_COLUMNS);

        $companyGroupId = (int) session('companies_groups_id', 0);

        if ($companyGroupId > 0) {
            $query->where('companies_groups_id', $companyGroupId);
        }

        $branchId = (int) session('hr_branch_id', 0);

        if ($direction === 'outgoing') {
            if ($branchId > 0) {
                $query->where('branch_id', $branchId);
            }
            if (! in_array((int) session('hr_user_level', 0), [1, 2, 3, 4], true)
                && (int) session('job_title', 0) > 0) {
                $query->where('job_title_sender', (int) session('job_title'));
            }
        } else {
            $sectionIds = $this->incomingSectionIds($branchId);

            if ($sectionIds !== []) {
                $query->whereIn('inquired_section', $sectionIds);
            } elseif ($branchId > 0) {
                $query->where('inquired_section', $branchId);
            }

        }

        return $query;
    }

    /**
     * @return list<int>
     */
    private function incomingSectionIds(int $branchId): array
    {
        $map = config('hm.inquiries.branch_incoming_sections', []);

        if ($branchId > 0 && isset($map[$branchId])) {
            return array_map('intval', (array) $map[$branchId]);
        }

        return [];
    }

    /**
     * @return array<string, int>
     */
    public function statusCounts(
        string $direction,
        ?Carbon $dateFrom,
        ?Carbon $dateTo,
        ?int $departmentId,
        string $mobile,
    ): array {
        $stats = config('hm.inquiries.stat_statuses', []);
        $counts = [];

        foreach ($stats as $key => $statusIds) {
            $counts[$key] = $this->applyListFilters(
                $this->scopedQuery($direction),
                $dateFrom,
                $dateTo,
                $departmentId,
                $mobile,
                null,
            )->whereIn('status', (array) $statusIds)->count();
        }

        return $counts;
    }

    /**
     * @return LengthAwarePaginator<int, InquiryAndService>
     */
    public function paginateFiltered(
        string $direction,
        ?Carbon $dateFrom,
        ?Carbon $dateTo,
        ?int $departmentId,
        string $mobile,
        ?int $statusId,
        int $perPage,
    ): LengthAwarePaginator {
        return $this->applyListFilters(
            $this->scopedQuery($direction),
            $dateFrom,
            $dateTo,
            $departmentId,
            $mobile,
            $statusId,
        )
            ->with([
                'inquiredSection:id,name_en,name_ar',
                'inquiryType:id,name_en,name_ar,publish',
                'currentStatus:id,name_en,name_ar,info',
                'senderBranch:id,name_en,name_ar',
            ])
            ->withExists([
                'replies as has_forwarded_update' => fn (Builder $builder) => $builder
                    ->where('inquiry_status_id', (int) config('hm.inquiries.forward_status_id', 999999)),
            ])
            ->orderByRaw('CAST(date AS UNSIGNED) DESC')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function applyListFilters(
        Builder $query,
        ?Carbon $dateFrom,
        ?Carbon $dateTo,
        ?int $departmentId,
        string $mobile,
        ?int $statusId,
    ): Builder {
        return $query
            ->when($dateFrom !== null, function (Builder $builder) use ($dateFrom) {
                $builder->whereRaw('CAST(date AS UNSIGNED) >= ?', [$dateFrom->timestamp]);
            })
            ->when($dateTo !== null, function (Builder $builder) use ($dateTo) {
                $builder->whereRaw('CAST(date AS UNSIGNED) <= ?', [$dateTo->timestamp]);
            })
            ->when($departmentId !== null, fn (Builder $builder) => $builder->where('inquired_section', $departmentId))
            ->when($mobile !== '', fn (Builder $builder) => $builder->where('mobile', 'like', '%'.$mobile.'%'))
            ->when($statusId !== null, function (Builder $builder) use ($statusId) {
                if ($statusId === 999999) {
                    $builder->whereIn('status', config('hm.inquiries.new_status_ids', [999999, 1, 0]));
                } else {
                    $builder->where('status', $statusId);
                }
            });
    }

    /**
     * @return Collection<int, InquiryAndServiceStatus>
     */
    public function statusOptions(): Collection
    {
        return InquiryAndServiceStatus::query()
            ->select(['id', 'name_en', 'name_ar', 'info'])
            ->where('publish', 1)
            ->orderBy('id')
            ->get();
    }

    /**
     * Status choices for the Add Status modal (ordered).
     *
     * @return list<array{id:int,label:string}>
     */
    public function updateStatusOptions(): array
    {
        $ids = array_map('intval', config('hm.inquiries.update_status_ids', [3, 4, 5, 999999]));
        $options = [];

        foreach ($ids as $id) {
            $options[] = [
                'id' => $id,
                'label' => __('inquiries.update_statuses.'.$id),
            ];
        }

        return $options;
    }

    /**
     * @return Collection<int, Branch>
     */
    public function departmentOptions(): Collection
    {
        return Branch::query()
            ->select(['id', 'name_en', 'name_ar'])
            ->when(Schema::hasColumn('branches', 'publish'), fn (Builder $query) => $query->where('publish', 1))
            ->orderBy('name_en')
            ->get();
    }

    /** @return Collection<int, Inquiry> */
    public function inquiryTypeOptions(): Collection
    {
        return Inquiry::query()
            ->select(['id', 'name_en', 'name_ar'])
            ->where('publish', 1)
            ->orderBy('name_en')
            ->get();
    }

    /** @return Collection<int, JobTitle> */
    public function jobTitleOptions(): Collection
    {
        return JobTitle::query()
            ->select(['id', 'branch_id', 'name_en', 'name_ar'])
            ->where('publish', 1)
            ->orderBy('branch_id')
            ->orderBy('name_en')
            ->get();
    }

    /**
     * @param array{enquirer:string,mobile:string,inquired_section:int,job_title:int,inquiry_id:int,inquiry_details:?string} $payload
     */
    public function create(array $payload): InquiryAndService
    {
        return InquiryAndService::query()->create([
            ...$payload,
            'date' => (string) now()->timestamp,
            'branch_id' => (int) session('hr_branch_id'),
            'job_title_sender' => (int) session('job_title', 0) ?: null,
            'created_by' => (int) session('hr_user_id'),
            'created_at' => now(),
            'companies_groups_id' => (int) session('companies_groups_id'),
            'status' => 999999,
        ]);
    }

    public function findForDetail(int $id, string $direction): ?InquiryAndService
    {
        return $this->scopedQuery($direction)
            ->select(self::DETAIL_COLUMNS)
            ->whereKey($id)
            ->with([
                'inquiredSection:id,name_en,name_ar',
                'inquiryType:id,name_en,name_ar,publish',
                'currentStatus:id,name_en,name_ar,info',
                'senderBranch:id,name_en,name_ar',
                'replies' => fn ($query) => $query
                    ->select([
                        'id',
                        'inquiries_and_services_id',
                        'inquiry_status_id',
                        'branch_id',
                        'inquired_section',
                        'inquiry_details',
                        'created_by',
                        'created_at',
                    ])
                    ->with(['status:id,name_en,name_ar,info'])
                    ->orderBy('id'),
            ])
            ->first();
    }

    public function hasForwardedStatusUpdate(InquiryAndService $inquiry): bool
    {
        if (array_key_exists('has_forwarded_update', $inquiry->getAttributes())) {
            return (bool) $inquiry->getAttribute('has_forwarded_update');
        }

        if ($inquiry->relationLoaded('replies')) {
            return $inquiry->replies->contains(
                fn (InquiryAndServiceReply $reply) => (int) $reply->inquiry_status_id
                    === (int) config('hm.inquiries.forward_status_id', 999999)
            );
        }

        return $inquiry->replies()
            ->where('inquiry_status_id', (int) config('hm.inquiries.forward_status_id', 999999))
            ->exists();
    }

    /**
     * @param  array{
     *     status_id:int,
     *     notes:string,
     *     department_id:?int,
     *     assignment_type:?string,
     *     timeline_message:string
     * }  $payload
     */
    public function applyStatusUpdate(InquiryAndService $inquiry, array $payload): InquiryAndService
    {
        $forwardStatusId = (int) config('hm.inquiries.forward_status_id', 999999);
        $statusId = (int) $payload['status_id'];
        $isForward = $statusId === $forwardStatusId;
        $userId = (int) (session('hr_user_id') ?: session('hr_id', 0));
        $branchId = (int) session('hr_branch_id', 0);
        $newDepartmentId = $isForward
            ? (int) ($payload['department_id'] ?? 0)
            : (int) $inquiry->inquired_section;

        DB::transaction(function () use (
            $inquiry,
            $statusId,
            $isForward,
            $newDepartmentId,
            $payload,
            $userId,
            $branchId,
        ): void {
            $update = [
                'status' => $statusId,
            ];

            if ($isForward) {
                $update['inquired_section'] = $newDepartmentId;
            }

            InquiryAndService::query()
                ->whereKey($inquiry->id)
                ->update($update);

            InquiryAndServiceReply::query()->create([
                'inquiries_and_services_id' => $inquiry->id,
                'inquiry_status_id' => $statusId,
                'branch_id' => $branchId > 0 ? $branchId : (int) $inquiry->branch_id,
                'inquired_section' => $newDepartmentId,
                'inquiry_id' => (int) $inquiry->inquiry_id,
                'inquiry_details' => $payload['timeline_message'],
                'created_by' => $userId > 0 ? $userId : (int) $inquiry->created_by,
                'created_at' => now(),
            ]);
        });

        $inquiry->refresh();

        return $inquiry;
    }

    public function hasStatusReply(InquiryAndService $inquiry, int $statusId): bool
    {
        return $inquiry->replies()
            ->where('inquiry_status_id', $statusId)
            ->exists();
    }

    public function departmentLabel(int $departmentId): string
    {
        if ($departmentId <= 0) {
            return '—';
        }

        $branch = Branch::query()
            ->select(['id', 'name_en', 'name_ar'])
            ->find($departmentId);

        return $branch?->localizedName() ?: '—';
    }

    public function statusLabelById(int $statusId): string
    {
        if (in_array($statusId, config('hm.inquiries.new_status_ids', [999999, 1, 0]), true)) {
            return __('inquiries.status.new');
        }

        $updateKey = 'inquiries.update_statuses.'.$statusId;
        $translated = __($updateKey);

        if ($translated !== $updateKey) {
            return $translated;
        }

        $status = InquiryAndServiceStatus::query()
            ->select(['id', 'name_en', 'name_ar'])
            ->find($statusId);

        return $status?->localizedName() ?: '—';
    }
}
