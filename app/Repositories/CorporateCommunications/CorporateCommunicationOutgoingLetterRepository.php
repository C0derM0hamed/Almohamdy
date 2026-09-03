<?php

namespace App\Repositories\CorporateCommunications;

use App\Models\Branch;
use App\Models\CorporateCommunicationOutgoingLetter;
use App\Models\CorporateCommunicationOutgoingLetterAttachment;
use App\Models\CorporateCommunicationOutgoingLetterStatus;
use App\Models\CorporateCommunicationOutgoingLetterTemplate;
use App\Models\CorporateCommunicationOutgoingLetterTimeline;
use App\Models\CorporateCommunicationSector;
use App\Models\CorporateCommunicationSenderTitle;
use App\Models\GovernmentCircularIssuingAuthority;
use App\Models\GovernmentCircularReceivingMechanism;
use App\Support\BranchScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CorporateCommunicationOutgoingLetterRepository
{
    public function scopedQuery(): Builder
    {
        $groupId = (int) session('companies_groups_id', 0);

        $query = CorporateCommunicationOutgoingLetter::query()
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
     *     status_id:?int,
     *     subject:?string
     * }  $filters
     */
    public function paginateFiltered(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->scopedQuery()
            ->with([
                'sector:id,name_en,name_ar',
                'authority:id,name_en,name_ar',
                'branch:id,name_en,name_ar',
                'currentStatus:id,name_en,name_ar',
            ])
            ->when($filters['from_date'] !== null, function (Builder $query) use ($filters) {
                $query->whereDate('issue_date', '>=', $filters['from_date']);
            })
            ->when($filters['to_date'] !== null, function (Builder $query) use ($filters) {
                $query->whereDate('issue_date', '<=', $filters['to_date']);
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
            ->when($filters['status_id'] !== null, function (Builder $query) use ($filters) {
                $query->where('status', $filters['status_id']);
            })
            ->when($filters['subject'] !== null && $filters['subject'] !== '', function (Builder $query) use ($filters) {
                $query->where('type', 'like', '%'.$filters['subject'].'%');
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

        $counts = DB::table('corporate_communications_outgoing_letters')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->when($groupId > 0, fn ($q) => $q->where('companies_groups_id', $groupId))
            ->when((int) session('hr_user_level', 0) !== 3 && (int) session('hr_branch_id', 0) > 0,
                fn ($q) => $q->where('branch_id', (int) session('hr_branch_id')))
            ->groupBy('status')
            ->pluck('total', 'status');

        return CorporateCommunicationOutgoingLetterStatus::query()
            ->where('publish', 1)
            ->orderBy('ranking')
            ->orderBy('id')
            ->get(['id', 'name_en', 'name_ar'])
            ->map(function (CorporateCommunicationOutgoingLetterStatus $status) use ($counts) {
                return (object) [
                    'status_id' => (int) $status->id,
                    'label' => $status->localizedName(),
                    'color' => $status->badgeColor(),
                    'total' => (int) ($counts[$status->id] ?? 0),
                ];
            });
    }

    public function findForDetail(int $id): ?CorporateCommunicationOutgoingLetter
    {
        return $this->scopedQuery()
            ->with([
                'sector:id,name_en,name_ar',
                'authority:id,name_en,name_ar',
                'senderTitle:id,name_en,name_ar',
                'receivingMechanism:id,name_en,name_ar',
                'branch:id,name_en,name_ar',
                'currentStatus:id,name_en,name_ar',
                'attachments',
                'timelineEntries.status:id,name_en,name_ar',
                'supplementaryLetters',
            ])
            ->whereKey($id)
            ->first();
    }

    public function findBySmsToken(string $token): ?CorporateCommunicationOutgoingLetter
    {
        return CorporateCommunicationOutgoingLetter::query()
            ->with([
                'sector:id,name_en,name_ar',
                'authority:id,name_en,name_ar',
                'branch:id,name_en,name_ar',
                'currentStatus:id,name_en,name_ar',
            ])
            ->where('sms_tocken', $token)
            ->first();
    }

    public function nextRegistrationNumber(int $groupId, int $year): int
    {
        $max = (int) CorporateCommunicationOutgoingLetter::query()
            ->where('companies_groups_id', $groupId)
            ->where('year', $year)
            ->max('registration_number');

        return $max + 1;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): CorporateCommunicationOutgoingLetter
    {
        return CorporateCommunicationOutgoingLetter::query()->create($attributes);
    }

    public function createAttachment(int $letterId, string $file, string $fileName): void
    {
        CorporateCommunicationOutgoingLetterAttachment::query()->create([
            'corporate_communications_outgoing_letters_id' => $letterId,
            'file' => $file,
            'file_name' => $fileName,
        ]);
    }

    public function createTimelineEntry(
        int $letterId,
        int $statusId,
        int $userId,
        ?string $notice = null,
    ): void {
        CorporateCommunicationOutgoingLetterTimeline::query()->create([
            'corporate_communications_outgoing_letters_id' => $letterId,
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
    public function updateStatusFields(int $letterId, array $attributes): void
    {
        CorporateCommunicationOutgoingLetter::query()
            ->whereKey($letterId)
            ->update($attributes);
    }

    public function createAction(
        int $letterId,
        int $statusId,
        int $userId,
        ?int $branchId = null,
        ?string $details = null,
    ): void {
        DB::table('corporate_communications_outgoing_letters_action')->insert([
            'corporate_communications_outgoing_letters_id' => $letterId,
            'status_id' => $statusId,
            'branch_id' => $branchId,
            'details' => $details,
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    public function createShipmentRequestDocument(
        int $letterId,
        string $shipmentNumber,
        string $file,
        int $userId,
        int $statusId,
        ?int $branchId = null,
    ): void {
        DB::table('corporate_communications_outgoing_letters_shipment_request_docum')->insert([
            'corporate_communications_outgoing_letters_id' => $letterId,
            'shipment_number' => $shipmentNumber,
            'branch_id' => $branchId,
            'shipment_request_document' => $file,
            'created_by' => $userId,
            'created_at' => now(),
            'status_id' => $statusId,
        ]);
    }

    public function createDeliverToPostal(
        int $letterId,
        string $dateTimeReceipt,
        string $postalEmployeeName,
        string $file,
        int $userId,
        ?int $branchId = null,
    ): void {
        DB::table('corporate_communications_outgoing_letters_deliver_to_postal')->insert([
            'corporate_communications_outgoing_letters_id' => $letterId,
            'date_time_receipt' => $dateTimeReceipt,
            'postal_employee_name' => $postalEmployeeName,
            'branch_id' => $branchId,
            'file' => $file,
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    public function createDeliverToAgency(
        int $letterId,
        string $dateTimeReceipt,
        string $file,
        int $userId,
        ?int $branchId = null,
    ): void {
        DB::table('corporate_communications_outgoing_letters_deliver_to_agency')->insert([
            'corporate_communications_outgoing_letters_id' => $letterId,
            'date_time_receipt' => $dateTimeReceipt,
            'branch_id' => $branchId,
            'file' => $file,
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    public function createReturnShipment(
        int $letterId,
        string $date,
        string $details,
        string $file,
        int $userId,
        ?int $branchId = null,
    ): void {
        DB::table('corporate_communications_outgoing_letters_return_shipment')->insert([
            'corporate_communications_outgoing_letters_id' => $letterId,
            'date' => $date,
            'branch_id' => $branchId,
            'details' => $details,
            'file' => $file,
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    public function createSpecialistDelivery(
        int $letterId,
        string $deliveryDate,
        string $registrationNumber,
        string $deliveredBy,
        int $userId,
        ?int $branchId = null,
    ): void {
        DB::table('corporate_communications_outgoing_letters_return_by_employee')->insert([
            'corporate_communications_outgoing_letters_id' => $letterId,
            'delivery_date' => $deliveryDate,
            'branch_id' => $branchId,
            'registration_number' => $registrationNumber,
            'delivered_by' => $deliveredBy,
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    public function nextSupplementarySerial(int $letterId): int
    {
        $max = (int) DB::table('corporate_communications_outgoing_letters_supplementary')
            ->where('corporate_communications_outgoing_letters_id', $letterId)
            ->max('serial_no');

        return $max + 1;
    }

    public function createSupplementary(
        int $letterId,
        string $details,
        int $serialNo,
        int $userId,
        ?int $branchId = null,
        string $date = '',
    ): void {
        DB::table('corporate_communications_outgoing_letters_supplementary')->insert([
            'corporate_communications_outgoing_letters_id' => $letterId,
            'serial_no' => $serialNo,
            'date' => $date,
            'branch_id' => $branchId,
            'details' => $details,
            'created_by' => $userId,
            'created_at' => now(),
        ]);
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
     * @return Collection<int, CorporateCommunicationOutgoingLetterStatus>
     */
    public function statusOptions(): Collection
    {
        return CorporateCommunicationOutgoingLetterStatus::query()
            ->where('publish', 1)
            ->orderBy('ranking')
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
     * @return Collection<int, CorporateCommunicationOutgoingLetterTemplate>
     */
    public function templateOptions(): Collection
    {
        return CorporateCommunicationOutgoingLetterTemplate::query()
            ->where('publish', '1')
            ->where('companies_groups_id', (int) session('companies_groups_id', 0))
            ->orderByDesc('id')
            ->get(['id', 'title', 'letter_content']);
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
