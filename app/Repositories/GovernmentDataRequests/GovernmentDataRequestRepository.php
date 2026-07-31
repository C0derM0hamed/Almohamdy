<?php

namespace App\Repositories\GovernmentDataRequests;

use App\Models\Branch;
use App\Models\GovernmentCircularSection;
use App\Models\GovernmentCircularSectionAdministrator;
use App\Models\GovernmentDataRequest;
use App\Models\GovernmentDataRequestAnswerFile;
use App\Models\GovernmentDataRequestDataType;
use App\Models\GovernmentDataRequestEntity;
use App\Models\GovernmentDataRequestMailFile;
use App\Models\GovernmentDataRequestReceivingMethod;
use App\Models\GovernmentDataRequestStatus;
use App\Models\GovernmentDataRequestTimeline;
use App\Models\GovernmentDataRequestView;
use App\Support\BranchScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GovernmentDataRequestRepository
{
    public function scopedQuery(): Builder
    {
        $query = GovernmentDataRequest::query()->select([
            'id',
            'id_siction',
            'id_subsiction',
            'companies_groups_id',
            'branch_id',
            'subjec',
            'date',
            'Date_receipt',
            'Request_Receipt',
            'send_Section',
            'Data_delivery',
            'status',
            'userid',
            'create_at',
            'Reminderـtime',
            'c',
            'becuse',
            'AnswerText',
        ])
            ->where('companies_groups_id', (int) session('companies_groups_id', 0));

        return BranchScope::apply($query);
    }

    /**
     * @param  array{
     *     from_date:?string,
     *     to_date:?string,
     *     entity_id:?int,
     *     branch_id:?int,
     *     section_id:?int,
     *     status_id:?int
     * }  $filters
     */
    public function paginateFiltered(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->scopedQuery()
            ->with([
                'entity:id,name',
                'dataType:id,name,id_sub',
                'section:id,name_en,name_ar',
                'branch:id,name_en,name_ar',
                'currentStatus:id,name',
            ])
            ->withCount('views as recipients_count')
            ->when($filters['from_date'] !== null, function (Builder $query) use ($filters) {
                $query->whereDate('date', '>=', $filters['from_date']);
            })
            ->when($filters['to_date'] !== null, function (Builder $query) use ($filters) {
                $query->whereDate('date', '<=', $filters['to_date']);
            })
            ->when($filters['entity_id'] !== null, function (Builder $query) use ($filters) {
                $query->where('id_siction', $filters['entity_id']);
            })
            ->when($filters['branch_id'] !== null, function (Builder $query) use ($filters) {
                $query->where('branch_id', $filters['branch_id']);
            })
            ->when($filters['section_id'] !== null, function (Builder $query) use ($filters) {
                $query->where('send_Section', (string) $filters['section_id']);
            })
            ->when($filters['status_id'] !== null, function (Builder $query) use ($filters) {
                $query->where('status', $filters['status_id']);
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return Collection<int, object{status_id:int,label:string,color:string,total:int}>
     */
    public function statusCounters(): Collection
    {
        $counts = DB::table('g_data')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->where('companies_groups_id', (int) session('companies_groups_id', 0))
            ->when((int) session('hr_user_level', 0) !== 3 && (int) session('hr_branch_id', 0) > 0,
                fn ($query) => $query->where('branch_id', (int) session('hr_branch_id')))
            ->groupBy('status')
            ->pluck('total', 'status');

        return GovernmentDataRequestStatus::query()
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(function (GovernmentDataRequestStatus $status) use ($counts) {
                return (object) [
                    'status_id' => (int) $status->id,
                    'label' => $status->localizedName(),
                    'color' => $status->badgeColor(),
                    'total' => (int) ($counts[$status->id] ?? 0),
                ];
            });
    }

    public function findForDetail(int $id): ?GovernmentDataRequest
    {
        return $this->scopedQuery()
            ->with([
                'entity:id,name',
                'dataType:id,name,id_sub',
                'receivingMethod:id,name',
                'section:id,name_en,name_ar',
                'branch:id,name_en,name_ar',
                'currentStatus:id,name',
                'mailFiles',
                'answerFiles',
                'timelineEntries.statusRecord:id,name',
                'views.administrator:id,administrator,email,mobile,government_circulars_sections_id',
                'views.administrator.section:id,name_en,name_ar',
            ])
            ->withCount('views as recipients_count')
            ->whereKey($id)
            ->first();
    }

    public function findByToken(string $token): ?GovernmentDataRequest
    {
        return GovernmentDataRequest::query()
            ->with([
                'entity:id,name',
                'dataType:id,name,id_sub',
                'section:id,name_en,name_ar',
                'branch:id,name_en,name_ar',
                'currentStatus:id,name',
            ])
            ->where('c', $token)
            ->first();
    }

    public function updateStatusFields(int $requestId, array $attributes): void
    {
        GovernmentDataRequest::query()
            ->whereKey($requestId)
            ->update($attributes);
    }

    public function createTimelineEntry(int $requestId, int $statusId, int $userId): void
    {
        GovernmentDataRequestTimeline::query()->create([
            'status' => $statusId,
            'userid' => $userId,
            'id_data' => $requestId,
            'create_at' => now()->format('Y-m-d\TH:i'),
        ]);
    }

    public function createAnswerFile(int $requestId, string $name, string $file): void
    {
        GovernmentDataRequestAnswerFile::query()->create([
            'id_data' => $requestId,
            'name' => $name,
            'file' => $file,
        ]);
    }

    /**
     * @return Collection<int, GovernmentDataRequestView>
     */
    public function receiptViewsForRequest(GovernmentDataRequest $request): Collection
    {
        return GovernmentDataRequestView::query()
            ->with([
                'administrator:id,administrator,email,mobile,government_circulars_sections_id',
                'administrator.section:id,name_en,name_ar',
            ])
            ->where('id_data', (string) $request->c)
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): GovernmentDataRequest
    {
        return GovernmentDataRequest::query()->create($attributes);
    }

    public function createMailFile(int $requestId, string $name, string $file): void
    {
        GovernmentDataRequestMailFile::query()->create([
            'id_data' => (string) $requestId,
            'name' => $name,
            'file' => $file,
        ]);
    }

    /**
     * @param  list<int>  $administratorIds
     */
    public function createViews(string $token, array $administratorIds): int
    {
        if ($administratorIds === []) {
            return 0;
        }

        $now = now()->format('Y-m-d\TH:i');
        $rows = [];

        foreach (array_values(array_unique($administratorIds)) as $adminId) {
            $rows[] = [
                'userid' => $adminId,
                'id_data' => $token,
                'status' => 2,
                'type' => '0',
                'created_at' => $now,
            ];
        }

        return DB::table('g_view')->insert($rows) ? count($rows) : 0;
    }

    /**
     * @param  list<int>  $sectionIds
     * @return Collection<int, GovernmentCircularSectionAdministrator>
     */
    public function publishedAdministratorsForSections(array $sectionIds): Collection
    {
        if ($sectionIds === []) {
            return collect();
        }

        return GovernmentCircularSectionAdministrator::query()
            ->where('publish', 1)
            ->where('companies_groups_id', (int) session('companies_groups_id', 0))
            ->whereIn('government_circulars_sections_id', $sectionIds)
            ->where('government_circulars_sections_administrators_types_id', 1)
            ->orderBy('id')
            ->get(['id', 'government_circulars_sections_id']);
    }

    /**
     * @return Collection<int, GovernmentDataRequestEntity>
     */
    public function entityOptions(): Collection
    {
        return GovernmentDataRequestEntity::query()
            ->orderBy('id')
            ->get(['id', 'name']);
    }

    /**
     * @return Collection<int, GovernmentDataRequestDataType>
     */
    public function dataTypeOptions(?int $entityId = null): Collection
    {
        return GovernmentDataRequestDataType::query()
            ->when($entityId !== null, fn (Builder $q) => $q->where('id_sub', $entityId))
            ->orderBy('id')
            ->get(['id', 'name', 'id_sub']);
    }

    /**
     * @return Collection<int, GovernmentDataRequestReceivingMethod>
     */
    public function receivingMethodOptions(): Collection
    {
        return GovernmentDataRequestReceivingMethod::query()
            ->orderBy('id')
            ->get(['id', 'name']);
    }

    /**
     * @return Collection<int, GovernmentDataRequestStatus>
     */
    public function statusOptions(): Collection
    {
        return GovernmentDataRequestStatus::query()
            ->orderBy('id')
            ->get(['id', 'name']);
    }

    /**
     * @return Collection<int, GovernmentCircularSection>
     */
    public function sectionOptions(): Collection
    {
        return GovernmentCircularSection::query()
            ->where('publish', 1)
            ->orderBy('id')
            ->get(['id', 'name_en', 'name_ar']);
    }

    /**
     * @return Collection<int, Branch>
     */
    public function branchOptions(): Collection
    {
        return Branch::query()
            ->where('publish', 1)
            ->where('companies_groups_id', (int) session('companies_groups_id', 0))
            ->orderBy('ranking')
            ->orderBy('id')
            ->get(['id', 'name_en', 'name_ar']);
    }
}
