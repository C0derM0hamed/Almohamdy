<?php

namespace App\Repositories\CorporateCommunications;

use App\Models\Branch;
use App\Models\CorporateCommunication;
use App\Models\CorporateCommunicationAttachment;
use App\Models\CorporateCommunicationReceiptReport;
use App\Models\CorporateCommunicationSector;
use App\Models\CorporateCommunicationSenderTitle;
use App\Models\CorporateCommunicationStatus;
use App\Models\CorporateCommunicationTimeline;
use App\Models\GovernmentCircularIssuingAuthority;
use App\Models\GovernmentCircularReceivingMechanism;
use App\Models\GovernmentCircularSection;
use App\Models\GovernmentCircularSectionAdministrator;
use App\Support\BranchScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CorporateCommunicationRepository
{
    public function scopedQuery(): Builder
    {
        $groupId = (int) session('companies_groups_id', 0);

        $query = CorporateCommunication::query()
            ->when($groupId > 0, fn (Builder $q) => $q->where('companies_groups_id', $groupId));

        return BranchScope::apply($query);
    }

    /**
     * @param  array{
     *     from_date:?string,
     *     to_date:?string,
     *     sector_id:?int,
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
                'sector:id,name_en,name_ar',
                'authority:id,name_en,name_ar',
                'section:id,name_en,name_ar',
                'branch:id,name_en,name_ar',
                'currentStatus:id,name_en,name_ar,info',
            ])
            ->withCount('receiptReports as recipients_count')
            ->when($filters['from_date'] !== null, function (Builder $query) use ($filters) {
                $query->whereDate('received_date', '>=', $filters['from_date']);
            })
            ->when($filters['to_date'] !== null, function (Builder $query) use ($filters) {
                $query->whereDate('received_date', '<=', $filters['to_date']);
            })
            ->when($filters['sector_id'] !== null, function (Builder $query) use ($filters) {
                $query->where('sectors_id', $filters['sector_id']);
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
        $groupId = (int) session('companies_groups_id', 0);

        $countsQuery = DB::table('corporate_communications')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->when($groupId > 0, fn ($q) => $q->where('companies_groups_id', $groupId))
            ->when((int) session('hr_user_level', 0) !== 3 && (int) session('hr_branch_id', 0) > 0,
                fn ($q) => $q->where('branch_id', (int) session('hr_branch_id')))
            ->groupBy('status');

        $counts = $countsQuery->pluck('total', 'status');

        return CorporateCommunicationStatus::query()
            ->where('publish', 1)
            ->orderBy('ranking')
            ->orderBy('id')
            ->get(['id', 'name_en', 'name_ar', 'info'])
            ->map(function (CorporateCommunicationStatus $status) use ($counts) {
                return (object) [
                    'status_id' => (int) $status->id,
                    'label' => $status->localizedName(),
                    'color' => $status->badgeColor(),
                    'total' => (int) ($counts[$status->id] ?? 0),
                ];
            });
    }

    public function findForDetail(int $id): ?CorporateCommunication
    {
        return $this->scopedQuery()
            ->with([
                'sector:id,name_en,name_ar',
                'authority:id,name_en,name_ar',
                'senderTitle:id,name_en,name_ar',
                'receivingMechanism:id,name_en,name_ar',
                'section:id,name_en,name_ar',
                'branch:id,name_en,name_ar',
                'currentStatus:id,name_en,name_ar,info',
                'attachments',
                'timelineEntries.status:id,name_en,name_ar,info',
                'receiptReports.administrator:id,administrator,email,mobile,government_circulars_sections_id',
                'receiptReports.administrator.section:id,name_en,name_ar',
            ])
            ->withCount('receiptReports as recipients_count')
            ->whereKey($id)
            ->first();
    }

    public function findBySmsToken(string $token): ?CorporateCommunication
    {
        return CorporateCommunication::query()
            ->with([
                'sector:id,name_en,name_ar',
                'authority:id,name_en,name_ar',
                'section:id,name_en,name_ar',
                'branch:id,name_en,name_ar',
                'currentStatus:id,name_en,name_ar,info',
            ])
            ->where('sms_tocken', $token)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): CorporateCommunication
    {
        return CorporateCommunication::query()->create($attributes);
    }

    public function createAttachment(int $communicationId, string $file): void
    {
        CorporateCommunicationAttachment::query()->create([
            'corporate_communications_id' => $communicationId,
            'file' => $file,
        ]);
    }

    public function createTimelineEntry(
        int $communicationId,
        int $statusId,
        int $userId,
        ?string $notice = null,
    ): void {
        CorporateCommunicationTimeline::query()->create([
            'corporate_communications_id' => $communicationId,
            'status_id' => $statusId,
            'date' => (string) time(),
            'notice' => $notice,
            'created_by' => $userId,
            'created_by_type' => null,
            'branch_id' => 0,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function updateStatusFields(int $communicationId, array $attributes): void
    {
        CorporateCommunication::query()
            ->whereKey($communicationId)
            ->update($attributes);
    }

    public function createAction(
        int $communicationId,
        int $statusId,
        int $userId,
        ?int $branchId = null,
        ?string $details = null,
    ): void {
        DB::table('corporate_communications_action')->insert([
            'corporate_communications_id' => $communicationId,
            'status_id' => $statusId,
            'branch_id' => $branchId,
            'details' => $details,
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    public function createShipmentRequestDocument(
        int $communicationId,
        string $shipmentNumber,
        string $file,
        int $userId,
        ?int $branchId = null,
    ): void {
        DB::table('corporate_communications_shipment_request_document')->insert([
            'corporate_communications_id' => $communicationId,
            'shipment_number' => $shipmentNumber,
            'branch_id' => $branchId,
            'shipment_request_document' => $file,
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    public function createDeliverToPostal(
        int $communicationId,
        string $dateTimeReceipt,
        string $postalEmployeeName,
        string $file,
        int $userId,
        ?int $branchId = null,
    ): void {
        DB::table('corporate_communications_deliver_to_postal')->insert([
            'corporate_communications_id' => $communicationId,
            'date_time_receipt' => $dateTimeReceipt,
            'postal_employee_name' => $postalEmployeeName,
            'branch_id' => $branchId,
            'file' => $file,
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    public function createDeliverToAgency(
        int $communicationId,
        string $dateTimeReceipt,
        string $file,
        int $userId,
        ?int $branchId = null,
    ): void {
        DB::table('corporate_communications_deliver_to_agency')->insert([
            'corporate_communications_id' => $communicationId,
            'date_time_receipt' => $dateTimeReceipt,
            'branch_id' => $branchId,
            'file' => $file,
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    public function createReturnShipment(
        int $communicationId,
        string $date,
        string $details,
        string $file,
        int $userId,
        ?int $branchId = null,
    ): void {
        DB::table('corporate_communications_return_shipment')->insert([
            'corporate_communications_id' => $communicationId,
            'date' => $date,
            'branch_id' => $branchId,
            'details' => $details,
            'file' => $file,
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    public function createSpecialistDelivery(
        int $communicationId,
        string $deliveryDate,
        string $registrationNumber,
        string $deliveredBy,
        int $userId,
        ?int $branchId = null,
    ): void {
        DB::table('corporate_communications_return_delivering_letter_by_employee')->insert([
            'corporate_communications_id' => $communicationId,
            'delivery_date' => $deliveryDate,
            'branch_id' => $branchId,
            'registration_number' => $registrationNumber,
            'delivered_by' => $deliveredBy,
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    /**
     * @return Collection<int, CorporateCommunicationReceiptReport>
     */
    public function receiptReportsFor(int $communicationId): Collection
    {
        return CorporateCommunicationReceiptReport::query()
            ->with([
                'administrator:id,administrator,email,mobile,government_circulars_sections_id',
                'administrator.section:id,name_en,name_ar',
            ])
            ->where('corporate_communications_id', $communicationId)
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  list<int>  $administratorIds
     */
    public function createReceiptReports(int $communicationId, array $administratorIds): int
    {
        if ($administratorIds === []) {
            return 0;
        }

        $now = now();
        $rows = [];

        foreach (array_values(array_unique($administratorIds)) as $administratorId) {
            $rows[] = [
                'corporate_communications_id' => $communicationId,
                'government_circulars_sections_administrators_id' => $administratorId,
                'created_at' => $now,
                'seen_by_sms_at' => null,
                'seen_by_email_at' => null,
            ];
        }

        return DB::table('corporate_communications_receipt_reports')->insert($rows) ? count($rows) : 0;
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
     * @return Collection<int, CorporateCommunicationSector>
     */
    public function sectorOptions(): Collection
    {
        return CorporateCommunicationSector::query()
            ->orderBy('id')
            ->get(['id', 'name_en', 'name_ar']);
    }

    /**
     * @return Collection<int, CorporateCommunicationSenderTitle>
     */
    public function senderTitleOptions(): Collection
    {
        return CorporateCommunicationSenderTitle::query()
            ->orderBy('id')
            ->get(['id', 'name_en', 'name_ar']);
    }

    /**
     * @return Collection<int, CorporateCommunicationStatus>
     */
    public function statusOptions(): Collection
    {
        return CorporateCommunicationStatus::query()
            ->where('publish', 1)
            ->orderBy('ranking')
            ->orderBy('id')
            ->get(['id', 'name_en', 'name_ar', 'info']);
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
            ->when(Schema::hasColumn('branches', 'publish'), fn (Builder $query) => $query->where('publish', 1))
            ->where('companies_groups_id', (int) session('companies_groups_id', 0))
            ->when(Schema::hasColumn('branches', 'ranking'), fn (Builder $query) => $query->orderBy('ranking'))
            ->orderBy('id')
            ->get(['id', 'name_en', 'name_ar']);
    }
}
