<?php

namespace App\Repositories\GovernmentInspectionVisits;

use App\Models\Branch;
use App\Models\GovernmentCircularIssuingAuthority;
use App\Models\GovernmentCircularSection;
use App\Models\GovernmentCircularSectionAdministrator;
use App\Models\GovernmentInspectionStatus;
use App\Models\GovernmentInspectionVisit;
use App\Models\GovernmentInspectionVisitAttachment;
use App\Models\GovernmentInspectionVisitFinding;
use App\Models\GovernmentInspectionVisitNumber;
use App\Models\GovernmentInspectionVisitReceiptReport;
use App\Models\GovernmentInspectionVisitReplySubmission;
use App\Models\GovernmentInspectionVisitReturned;
use App\Models\GovernmentInspectionVisitTimeline;
use App\Models\GovernmentInspectionVisitType;
use App\Support\BranchScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GovernmentInspectionVisitRepository
{
    /**
     * @var list<string>
     */
    private const LIST_COLUMNS = [
        'id',
        'visit_number',
        'date',
        'branch_id',
        'government_circulars_issuing_authority_id',
        'visit_type',
        'visit_date',
        'reply_time',
        'government_circulars_sections_id',
        'users',
        'created_by',
        'created_at',
        'companies_groups_id',
        'status',
        'government_inspection_visits_types_id',
    ];

    public function scopedQuery(): Builder
    {
        $query = GovernmentInspectionVisit::query()
            ->select(self::LIST_COLUMNS)
            ->where('companies_groups_id', (int) session('companies_groups_id', 0));

        return BranchScope::apply($query);
    }

    /**
     * @param  array{
     *     from_date:?string,
     *     to_date:?string,
     *     visit_type_id:?int,
     *     authority_id:?int,
     *     branch_id:?int,
     *     section_id:?int,
     *     status_id:?int
     * }  $filters
     */
    public function paginateFiltered(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->scopedQuery()
            ->with([
                'authority:id,name_en,name_ar',
                'section:id,name_en,name_ar',
                'currentStatus:id,name_en,name_ar,info',
                'visitType:id,name_en,name_ar',
                'branch:id,name_en,name_ar',
                'visitNumberRecord:id,subject,abuses_status,notes_status,representative_name',
            ])
            ->withCount('receiptReports as recipients_count')
            ->when($filters['from_date'] !== null, function (Builder $query) use ($filters) {
                $query->whereDate('visit_date', '>=', $filters['from_date']);
            })
            ->when($filters['to_date'] !== null, function (Builder $query) use ($filters) {
                $query->whereDate('visit_date', '<=', $filters['to_date']);
            })
            ->when($filters['visit_type_id'] !== null, function (Builder $query) use ($filters) {
                $query->where('government_inspection_visits_types_id', $filters['visit_type_id']);
            })
            ->when($filters['authority_id'] !== null, function (Builder $query) use ($filters) {
                $query->where('government_circulars_issuing_authority_id', $filters['authority_id']);
            })
            ->when($filters['branch_id'] !== null, function (Builder $query) use ($filters) {
                $query->where('branch_id', $filters['branch_id']);
            })
            ->when($filters['section_id'] !== null, function (Builder $query) use ($filters) {
                $query->where('government_circulars_sections_id', (string) $filters['section_id']);
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
        $locale = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';
        $companyId = (int) session('companies_groups_id', 0);

        $counts = DB::table('government_inspection_visits')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->where('companies_groups_id', $companyId)
            ->when((int) session('hr_user_level', 0) !== 3 && (int) session('hr_branch_id', 0) > 0,
                fn ($query) => $query->where('branch_id', (int) session('hr_branch_id')))
            ->groupBy('status')
            ->pluck('total', 'status');

        return GovernmentInspectionStatus::query()
            ->where('publish', 1)
            ->orderBy('ranking')
            ->orderBy('id')
            ->get(['id', 'name_en', 'name_ar', 'info'])
            ->map(function (GovernmentInspectionStatus $status) use ($counts, $locale) {
                return (object) [
                    'status_id' => (int) $status->id,
                    'label' => trim((string) ($status->{$locale} ?: $status->name_en ?: $status->name_ar)),
                    'color' => $status->badgeColor(),
                    'total' => (int) ($counts[$status->id] ?? 0),
                ];
            });
    }

    public function findForDetail(int $id): ?GovernmentInspectionVisit
    {
        return $this->scopedQuery()
            ->with([
                'authority:id,name_en,name_ar',
                'section:id,name_en,name_ar',
                'currentStatus:id,name_en,name_ar,info',
                'visitType:id,name_en,name_ar',
                'branch:id,name_en,name_ar',
                'visitNumberRecord',
                'findings',
                'attachments',
                'returnedItems.finding',
                'replySubmissions',
                'timelineEntries.status:id,name_en,name_ar,info',
            ])
            ->withCount('receiptReports as recipients_count')
            ->whereKey($id)
            ->first();
    }

    public function updateStatus(int $visitId, int $statusId): void
    {
        GovernmentInspectionVisit::query()
            ->whereKey($visitId)
            ->update(['status' => $statusId]);
    }

    public function findBySmsToken(string $token): ?GovernmentInspectionVisit
    {
        return GovernmentInspectionVisit::query()
            ->with([
                'authority:id,name_en,name_ar',
                'section:id,name_en,name_ar',
                'branch:id,name_en,name_ar',
                'visitType:id,name_en,name_ar',
                'currentStatus:id,name_en,name_ar,info',
                'findings',
                'returnedItems.finding',
            ])
            ->where('sms_tocken', $token)
            ->first();
    }

    /**
     * @param  list<array{id:int,reply:string,uploaded_file:?string}>  $items
     */
    public function applyFindingReplies(array $items, int $repliedBy): void
    {
        $now = now();

        foreach ($items as $item) {
            GovernmentInspectionVisitFinding::query()
                ->whereKey((int) $item['id'])
                ->update([
                    'reply' => $item['reply'],
                    'uploaded_file' => $item['uploaded_file'],
                    'replied_at' => $now,
                    'replied_by' => $repliedBy,
                ]);
        }
    }

    /**
     * @param  list<array{id:int,reply:string,uploaded_file:?string}>  $items
     */
    public function applyReturnedReplies(array $items, int $repliedBy): void
    {
        $now = now();

        foreach ($items as $item) {
            GovernmentInspectionVisitReturned::query()
                ->whereKey((int) $item['id'])
                ->update([
                    'reply' => $item['reply'],
                    'uploaded_file' => $item['uploaded_file'],
                    'replied_at' => $now,
                    'replied_by' => $repliedBy,
                    'status' => 1,
                ]);
        }
    }

    /**
     * @return Collection<int, GovernmentInspectionVisitFinding>
     */
    public function pendingFindings(int $visitId): Collection
    {
        return GovernmentInspectionVisitFinding::query()
            ->where('government_inspection_visits_id', $visitId)
            ->whereNull('reply')
            ->whereNull('replied_at')
            ->whereNull('replied_by')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, GovernmentInspectionVisitReturned>
     */
    public function pendingReturnedItems(int $visitId): Collection
    {
        return GovernmentInspectionVisitReturned::query()
            ->with('finding')
            ->where('government_inspection_visits_id', $visitId)
            ->whereNull('reply')
            ->whereNull('replied_at')
            ->whereNull('replied_by')
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', 0);
            })
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createAttachment(array $attributes): GovernmentInspectionVisitAttachment
    {
        return GovernmentInspectionVisitAttachment::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createReplySubmission(array $attributes): GovernmentInspectionVisitReplySubmission
    {
        return GovernmentInspectionVisitReplySubmission::query()->create($attributes);
    }

    /**
     * @param  list<array{finding_id:int,reason:string,uploaded_file:?string}>  $returns
     */
    public function createReturnedItems(int $visitId, array $returns, int $createdBy): void
    {
        $now = now();

        foreach ($returns as $item) {
            GovernmentInspectionVisitReturned::query()->create([
                'government_inspection_visits_id' => $visitId,
                'government_inspection_visits_abuses_and_notes_id' => (int) $item['finding_id'],
                'reason' => $item['reason'],
                'uploaded_file' => $item['uploaded_file'] ?? null,
                'created_at' => $now,
                'created_by' => $createdBy,
                'status' => 0,
            ]);
        }
    }

    /**
     * @return Collection<int, GovernmentInspectionVisitReceiptReport>
     */
    public function receiptReportsForVisit(int $visitId): Collection
    {
        return GovernmentInspectionVisitReceiptReport::query()
            ->with([
                'administrator:id,government_circulars_sections_id,administrator,mobile,email,government_circulars_sections_administrators_types_id',
                'administrator.section:id,name_en,name_ar',
                'administrator.type:id,name_en,name_ar',
            ])
            ->where('government_inspection_visits_id', $visitId)
            ->orderBy('id')
            ->get();
    }

    public function findingBelongsToVisit(int $findingId, int $visitId): bool
    {
        return GovernmentInspectionVisitFinding::query()
            ->whereKey($findingId)
            ->where('government_inspection_visits_id', $visitId)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createVisitNumber(array $attributes): GovernmentInspectionVisitNumber
    {
        return GovernmentInspectionVisitNumber::query()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createVisit(array $attributes): GovernmentInspectionVisit
    {
        return GovernmentInspectionVisit::query()->create($attributes);
    }

    /**
     * @param  list<array{type:int,abuse_note_title:string}>  $findings
     */
    public function createFindings(int $visitId, array $findings): void
    {
        $now = now();

        foreach ($findings as $finding) {
            GovernmentInspectionVisitFinding::query()->create([
                'government_inspection_visits_id' => $visitId,
                'date' => $now,
                'type' => (int) $finding['type'],
                'abuse_note_title' => $finding['abuse_note_title'],
                'created_by_type' => 1,
            ]);
        }
    }

    public function createTimelineEntry(int $visitId, int $statusId, int $sectionId, int $createdBy): void
    {
        GovernmentInspectionVisitTimeline::query()->create([
            'government_inspection_visits_id' => $visitId,
            'status_id' => $statusId,
            'date' => (string) time(),
            'created_by' => $createdBy,
            'created_by_type' => 1,
            'branch_id' => $sectionId,
        ]);
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
            ->orderBy('id')
            ->get(['id', 'government_circulars_sections_id']);
    }

    /**
     * @param  list<int>  $administratorIds
     */
    public function createReceiptReports(int $visitId, array $administratorIds): int
    {
        if ($administratorIds === []) {
            return 0;
        }

        $now = now();
        $rows = [];

        foreach (array_values(array_unique($administratorIds)) as $administratorId) {
            $rows[] = [
                'government_inspection_visits_id' => $visitId,
                'government_circulars_sections_administrators_id' => $administratorId,
                'created_at' => $now,
                'seen_by_sms_at' => null,
                'seen_by_email_at' => null,
            ];
        }

        return DB::table('government_inspection_visits_receipt_reports')->insert($rows) ? count($rows) : 0;
    }

    /**
     * @return Collection<int, GovernmentInspectionVisitType>
     */
    public function visitTypeOptions(): Collection
    {
        return GovernmentInspectionVisitType::query()
            ->where('publish', 1)
            ->orderBy('id')
            ->get(['id', 'name_en', 'name_ar']);
    }

    /**
     * @return Collection<int, GovernmentCircularIssuingAuthority>
     */
    public function authorityOptions(): Collection
    {
        return GovernmentCircularIssuingAuthority::query()
            ->where('publish', 1)
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_ar']);
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
     * @return Collection<int, GovernmentInspectionStatus>
     */
    public function statusOptions(): Collection
    {
        return GovernmentInspectionStatus::query()
            ->where('publish', 1)
            ->orderBy('ranking')
            ->orderBy('id')
            ->get(['id', 'name_en', 'name_ar', 'info']);
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
