<?php

namespace App\Repositories\GovernmentCirculars;

use App\Models\Branch;
use App\Models\GovernmentCircular;
use App\Models\GovernmentCircularIssuingAuthority;
use App\Models\GovernmentCircularIssuingAuthorityClassification;
use App\Models\GovernmentCircularNotificationType;
use App\Models\GovernmentCircularReceiptReport;
use App\Models\GovernmentCircularReceivingMechanism;
use App\Models\GovernmentCircularSection;
use App\Models\GovernmentCircularSectionAdministrator;
use App\Models\GovernmentCircularStatus;
use App\Support\BranchScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GovernmentCircularRepository
{
    /**
     * @var list<string>
     */
    private const LIST_COLUMNS = [
        'id',
        'date',
        'branch_id',
        'government_circulars_issuing_authority_id',
        'government_circulars_issuing_authority_classification_id',
        'issue_date',
        'received_date',
        'government_circulars_receiving_mechanism_id',
        'subject',
        'government_circulars_sections_id',
        'circulars_file',
        'created_by',
        'created_at',
        'companies_groups_id',
        'status',
        'sms_tocken',
        'notification_type',
        'attachment_type',
    ];

    public function scopedQuery(): Builder
    {
        $query = GovernmentCircular::query()
            ->select(self::LIST_COLUMNS)
            ->where('companies_groups_id', (int) session('companies_groups_id', 0));

        return BranchScope::apply($query);
    }

    public function paginateFiltered(
        string $subject,
        ?int $authorityId,
        ?int $sectionId,
        int $perPage,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?int $branchId = null,
    ): LengthAwarePaginator {
        return $this->scopedQuery()
            ->with([
                'authority:id,name_en,name_ar',
                'classification:id,name_en,name_ar',
                'section:id,name_en,name_ar',
                'currentStatus:id,name_en,name_ar,info',
                'branch:id,name_en,name_ar',
            ])
            ->withCount('receiptReports as recipients_count')
            ->when($subject !== '', function (Builder $query) use ($subject) {
                $query->where('subject', 'like', '%'.$subject.'%');
            })
            ->when($authorityId !== null, function (Builder $query) use ($authorityId) {
                $query->where('government_circulars_issuing_authority_id', $authorityId);
            })
            ->when($sectionId !== null, function (Builder $query) use ($sectionId) {
                $query->where(function (Builder $inner) use ($sectionId) {
                    $inner->where('government_circulars_sections_id', (string) $sectionId)
                        ->orWhere('government_circulars_sections_id', 'like', $sectionId.',%')
                        ->orWhere('government_circulars_sections_id', 'like', '%,'.$sectionId.',%')
                        ->orWhere('government_circulars_sections_id', 'like', '%,'.$sectionId);
                });
            })
            ->when($branchId !== null, function (Builder $query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->when($fromDate !== null, function (Builder $query) use ($fromDate) {
                $query->whereDate('issue_date', '>=', $fromDate);
            })
            ->when($toDate !== null, function (Builder $query) use ($toDate) {
                $query->whereDate('issue_date', '<=', $toDate);
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForDetail(int $id): ?GovernmentCircular
    {
        return $this->scopedQuery()
            ->with([
                'authority:id,name_en,name_ar',
                'classification:id,name_en,name_ar',
                'receivingMechanism:id,name_en,name_ar',
                'section:id,name_en,name_ar',
                'currentStatus:id,name_en,name_ar,info',
                'notificationType:id,name_en,name_ar',
                'branch:id,name_en,name_ar',
                'attachments',
            ])
            ->whereKey($id)
            ->first();
    }

    public function create(array $attributes): GovernmentCircular
    {
        return GovernmentCircular::query()->create($attributes);
    }

    public function updateStatus(int $circularId, array $attributes): void
    {
        GovernmentCircular::query()
            ->whereKey($circularId)
            ->update($attributes);
    }

    public function addAttachment(int $circularId, string $path): void
    {
        DB::table('government_circulars_attachments')->insert([
            'government_circulars_id' => $circularId,
            'circulars_file' => $path,
        ]);
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
     * @return Collection<int, GovernmentCircularIssuingAuthorityClassification>
     */
    public function classificationOptions(?int $authorityId = null): Collection
    {
        return GovernmentCircularIssuingAuthorityClassification::query()
            ->where('publish', 1)
            ->when($authorityId !== null, fn (Builder $q) => $q->where('government_circulars_issuing_authority_id', $authorityId))
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_ar', 'government_circulars_issuing_authority_id']);
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
     * @return Collection<int, GovernmentCircularReceivingMechanism>
     */
    public function receivingMechanismOptions(): Collection
    {
        return GovernmentCircularReceivingMechanism::query()
            ->where('publish', 1)
            ->orderBy('id')
            ->get(['id', 'name_en', 'name_ar']);
    }

    /**
     * @return Collection<int, GovernmentCircularNotificationType>
     */
    public function notificationTypeOptions(): Collection
    {
        return GovernmentCircularNotificationType::query()
            ->where('publish', 1)
            ->orderBy('id')
            ->get(['id', 'name_en', 'name_ar']);
    }

    /**
     * @return Collection<int, GovernmentCircularStatus>
     */
    public function statusOptions(): Collection
    {
        return GovernmentCircularStatus::query()
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
            ->with(['section:id,name_en,name_ar', 'type:id,name_en,name_ar'])
            ->where('publish', 1)
            ->where('companies_groups_id', (int) session('companies_groups_id', 0))
            ->whereIn('government_circulars_sections_id', $sectionIds)
            ->orderBy('government_circulars_sections_id')
            ->orderBy('administrator')
            ->get();
    }

    /**
     * @param  list<int>  $administratorIds
     */
    public function createReceiptReports(int $circularId, array $administratorIds): int
    {
        if ($administratorIds === []) {
            return 0;
        }

        $now = now();
        $rows = [];

        foreach (array_values(array_unique($administratorIds)) as $administratorId) {
            $rows[] = [
                'government_circulars_id' => $circularId,
                'government_circulars_sections_administrators_id' => $administratorId,
                'created_at' => $now,
                'seen_by_sms_at' => null,
                'seen_by_email_at' => null,
            ];
        }

        return DB::table('government_circulars_receipt_reports')->insert($rows) ? count($rows) : 0;
    }

    /**
     * @return Collection<int, GovernmentCircularReceiptReport>
     */
    public function receiptReportsForCircular(int $circularId): Collection
    {
        return GovernmentCircularReceiptReport::query()
            ->with([
                'administrator:id,government_circulars_sections_id,administrator,mobile,email,government_circulars_sections_administrators_types_id',
                'administrator.section:id,name_en,name_ar',
                'administrator.type:id,name_en,name_ar',
            ])
            ->where('government_circulars_id', $circularId)
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, object{section_id:int,section_name:string,recipients_count:int}>
     */
    public function departmentRecipientSummary(int $circularId): Collection
    {
        $locale = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';

        return DB::table('government_circulars_receipt_reports as r')
            ->join(
                'government_circulars_sections_administrators as a',
                'a.id',
                '=',
                'r.government_circulars_sections_administrators_id'
            )
            ->join(
                'government_circulars_sections as s',
                's.id',
                '=',
                'a.government_circulars_sections_id'
            )
            ->where('r.government_circulars_id', $circularId)
            ->groupBy('s.id', 's.name_en', 's.name_ar')
            ->orderBy('s.id')
            ->get([
                's.id as section_id',
                DB::raw("COALESCE(NULLIF(s.{$locale}, ''), s.name_en, s.name_ar) as section_name"),
                DB::raw('COUNT(*) as recipients_count'),
            ]);
    }

    public function markEmailSeen(int $circularId, int $administratorId): void
    {
        GovernmentCircularReceiptReport::query()
            ->where('government_circulars_id', $circularId)
            ->where('government_circulars_sections_administrators_id', $administratorId)
            ->whereNull('seen_by_email_at')
            ->update(['seen_by_email_at' => now()]);
    }

    public function markSmsSeen(int $circularId, int $administratorId): void
    {
        GovernmentCircularReceiptReport::query()
            ->where('government_circulars_id', $circularId)
            ->where('government_circulars_sections_administrators_id', $administratorId)
            ->whereNull('seen_by_sms_at')
            ->update(['seen_by_sms_at' => now()]);
    }

    public function findBySmsToken(string $token): ?GovernmentCircular
    {
        return GovernmentCircular::query()
            ->with([
                'authority:id,name_en,name_ar',
                'classification:id,name_en,name_ar',
                'receivingMechanism:id,name_en,name_ar',
                'notificationType:id,name_en,name_ar',
                'section:id,name_en,name_ar',
                'branch:id,name_en,name_ar',
                'attachments',
            ])
            ->where('sms_tocken', $token)
            ->first();
    }
}
