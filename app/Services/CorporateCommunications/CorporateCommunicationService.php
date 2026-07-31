<?php

namespace App\Services\CorporateCommunications;

use App\Http\Requests\CorporateCommunications\UpdateCorporateCommunicationStatusRequest;
use App\Models\CorporateCommunication;
use App\Repositories\CorporateCommunications\CorporateCommunicationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CorporateCommunicationService
{
    public function __construct(
        private readonly CorporateCommunicationRepository $repository,
    ) {}

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
    public function listPaginated(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateFiltered(
            $filters,
            (int) config('hm.correspondence.per_page', 15),
        );
    }

    public function statusCounters(): Collection
    {
        return $this->repository->statusCounters();
    }

    public function findForDetail(int $id): ?CorporateCommunication
    {
        return $this->repository->findForDetail($id);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<UploadedFile>  $attachments
     */
    public function store(array $payload, array $attachments = []): CorporateCommunication
    {
        return DB::transaction(function () use ($payload, $attachments) {
            $userId = (int) session('hr_user_id', 0);
            $sectionId = (int) $payload['section_id'];
            $firstAttachmentType = null;

            $communication = $this->repository->create([
                'date' => (string) time(),
                'branch_id' => (int) $payload['branch_id'],
                'sectors_id' => (int) $payload['sector_id'],
                'government_circulars_issuing_authority_id' => (int) $payload['authority_id'],
                'corporate_communications_senderTitle_id' => (int) $payload['sender_title_id'],
                'issue_date' => $payload['issue_date'],
                'received_date' => $payload['received_date'],
                'government_circulars_receiving_mechanism_id' => (int) $payload['receiving_mechanism_id'],
                'sender_gender' => (string) $payload['sender_gender'],
                'sender' => $payload['sender'],
                'job_title' => $payload['job_title'],
                'type' => $payload['subject'],
                'government_circulars_sections_id' => (string) $sectionId,
                'receiving_response_date' => $payload['response_deadline'],
                'created_by' => $userId,
                'created_at' => now(),
                'companies_groups_id' => (int) session('companies_groups_id', 0),
                'status' => 1,
                'replied_status' => '0',
                'sms_tocken' => md5(Str::uuid()->toString()),
                'document_status' => 1,
                'attachment_type' => null,
            ]);

            foreach ($attachments as $file) {
                $path = $file->store('correspondence/'.date('Y/m'), 'public');
                $this->repository->createAttachment((int) $communication->id, $path);

                if ($firstAttachmentType === null) {
                    $firstAttachmentType = $file->getClientOriginalExtension();
                }
            }

            if ($firstAttachmentType !== null) {
                $communication->forceFill(['attachment_type' => $firstAttachmentType])->save();
            }

            $this->repository->createTimelineEntry((int) $communication->id, 1, $userId);

            $admins = $this->repository->publishedAdministratorsForSections([$sectionId]);
            if ($admins->isNotEmpty()) {
                $this->repository->createReceiptReports(
                    (int) $communication->id,
                    $admins->pluck('id')->map(fn ($id) => (int) $id)->all(),
                );
            }

            $created = $communication->fresh([
                'sector',
                'authority',
                'section',
                'branch',
                'currentStatus',
                'attachments',
            ]) ?? $communication;

            $this->notifyDepartmentAdmins($created, $sectionId);

            return $created;
        });
    }

    private function notifyDepartmentAdmins(CorporateCommunication $communication, int $sectionId): void
    {
        $admins = \App\Models\GovernmentCircularSectionAdministrator::query()
            ->where('publish', 1)
            ->where('government_circulars_sections_id', $sectionId)
            ->where('companies_groups_id', (int) $communication->companies_groups_id)
            ->get(['id', 'administrator', 'email', 'mobile']);

        if ($admins->isEmpty()) {
            return;
        }

        $notifier = app(\App\Services\CorporateCommunications\DepartmentNotificationService::class);

        foreach ($admins as $admin) {
            $notifier->notifyAdministrator(
                $admin,
                __('correspondence.department_reply.title').' '.$communication->displayNumber(),
                __('correspondence.department_reply.subtitle'),
                $this->departmentReplyUrl($communication, (int) $admin->id),
                'correspondence',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateStatus(
        CorporateCommunication $communication,
        array $payload,
        ?UploadedFile $statusFile = null,
    ): CorporateCommunication {
        $statusId = (int) $payload['status_id'];

        if (! in_array($statusId, UpdateCorporateCommunicationStatusRequest::updatableStatusIds(), true)) {
            throw new InvalidArgumentException('Invalid status');
        }

        if ((int) $communication->status === $statusId) {
            throw new InvalidArgumentException(__('correspondence.validation.status_unchanged'));
        }

        return DB::transaction(function () use ($communication, $payload, $statusFile, $statusId) {
            $userId = (int) session('hr_user_id', 0);
            $branchId = (int) ($communication->branch_id ?: 0) ?: null;
            $reason = filled($payload['reason'] ?? null) ? (string) $payload['reason'] : null;
            $storedFile = null;

            if ($statusFile !== null) {
                $storedFile = $statusFile->store('correspondence/status/'.date('Y/m'), 'public');
            }

            $attributes = ['status' => $statusId];

            if ($statusId === UpdateCorporateCommunicationStatusRequest::STATUS_APPROVED) {
                $attributes['replied_status'] = '1';
            }

            if (in_array($statusId, [
                UpdateCorporateCommunicationStatusRequest::STATUS_RETURNED_TO_DEPARTMENT,
                UpdateCorporateCommunicationStatusRequest::STATUS_ESCALATED,
            ], true)) {
                $attributes['replied_status'] = '3';
            }

            $this->repository->updateStatusFields((int) $communication->id, $attributes);
            $this->repository->createAction(
                (int) $communication->id,
                $statusId,
                $userId,
                $branchId,
                $reason,
            );
            $this->repository->createTimelineEntry(
                (int) $communication->id,
                $statusId,
                $userId,
                $reason,
            );

            match ($statusId) {
                UpdateCorporateCommunicationStatusRequest::STATUS_SHIPMENT_REQUESTED => $this->repository->createShipmentRequestDocument(
                    (int) $communication->id,
                    (string) $payload['shipment_number'],
                    (string) $storedFile,
                    $userId,
                    $branchId,
                ),
                UpdateCorporateCommunicationStatusRequest::STATUS_DELIVERED_TO_POSTAL => $this->repository->createDeliverToPostal(
                    (int) $communication->id,
                    (string) $payload['date_time_receipt'],
                    (string) $payload['postal_employee_name'],
                    (string) $storedFile,
                    $userId,
                    $branchId,
                ),
                UpdateCorporateCommunicationStatusRequest::STATUS_DELIVERED_TO_ENTITY => $this->repository->createDeliverToAgency(
                    (int) $communication->id,
                    (string) $payload['date_time_receipt'],
                    (string) $storedFile,
                    $userId,
                    $branchId,
                ),
                UpdateCorporateCommunicationStatusRequest::STATUS_RETURNED_BY_ENTITY => $this->repository->createReturnShipment(
                    (int) $communication->id,
                    (string) $payload['return_date'],
                    (string) $payload['reason'],
                    (string) $storedFile,
                    $userId,
                    $branchId,
                ),
                UpdateCorporateCommunicationStatusRequest::STATUS_DELIVERED_BY_SPECIALIST => $this->repository->createSpecialistDelivery(
                    (int) $communication->id,
                    (string) $payload['delivery_date'],
                    (string) $payload['registration_number'],
                    (string) $payload['delivered_by'],
                    $userId,
                    $branchId,
                ),
                default => null,
            };

            return $communication->fresh([
                'sector',
                'authority',
                'senderTitle',
                'receivingMechanism',
                'section',
                'branch',
                'currentStatus',
                'attachments',
                'timelineEntries.status',
                'receiptReports.administrator.section',
            ]) ?? $communication;
        });
    }

    public function receiptReports(CorporateCommunication $communication): Collection
    {
        return $this->repository->receiptReportsFor((int) $communication->id);
    }

    public function updatableStatusOptions(?int $currentStatusId = null): Collection
    {
        return $this->repository->statusOptions()
            ->filter(fn ($status) => in_array(
                (int) $status->id,
                UpdateCorporateCommunicationStatusRequest::updatableStatusIds(),
                true
            ))
            ->filter(fn ($status) => $currentStatusId === null || (int) $status->id !== $currentStatusId)
            ->values();
    }

    public function sectorOptions(): Collection
    {
        return $this->repository->sectorOptions();
    }

    public function senderTitleOptions(): Collection
    {
        return $this->repository->senderTitleOptions();
    }

    public function statusOptions(): Collection
    {
        return $this->repository->statusOptions();
    }

    public function authorityOptions(): Collection
    {
        return $this->repository->authorityOptions();
    }

    public function receivingMechanismOptions(): Collection
    {
        return $this->repository->receivingMechanismOptions();
    }

    public function sectionOptions(): Collection
    {
        return $this->repository->sectionOptions();
    }

    public function branchOptions(): Collection
    {
        return $this->repository->branchOptions();
    }

    public function statusLabel(?CorporateCommunication $communication): string
    {
        return $communication?->currentStatus?->localizedName()
            ?: __('correspondence.status_unknown');
    }

    public function statusColor(?CorporateCommunication $communication): string
    {
        return $communication?->currentStatus?->badgeColor() ?: '#64748b';
    }

    public function fileUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        $path = trim($path);

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $normalized = ltrim(str_replace('\\', '/', $path), './');

        if (Storage::disk('public')->exists($normalized)) {
            return Storage::disk('public')->url($normalized);
        }

        $legacyCandidates = [
            public_path($normalized),
            public_path('government_reporting/'.basename($normalized)),
            base_path($normalized),
        ];

        foreach ($legacyCandidates as $candidate) {
            if (is_file($candidate)) {
                $relative = str_replace('\\', '/', str_replace(public_path(), '', $candidate));

                return asset(ltrim($relative, '/'));
            }
        }

        return asset(ltrim($normalized, '/'));
    }

    public const STATUS_SENT = 1;

    public const STATUS_REPLIED_BY_DEPT = 3;

    public const STATUS_RETURNED_TO_DEPT = 5;

    public function resolveByReplyToken(string $rawToken): array
    {
        $parsed = \App\Support\CorporateCommunications\DepartmentReplyToken::parse($rawToken);
        $item = $this->repository->findBySmsToken($parsed->token);

        if ($item === null) {
            throw new InvalidArgumentException(__('correspondence.department_reply.invalid_link'));
        }

        return [$item, $parsed];
    }

    public function openDepartmentReply(string $rawToken): array
    {
        [$item, $parsed] = $this->resolveByReplyToken($rawToken);
        $status = (int) $item->status;

        if (! in_array($status, [self::STATUS_SENT, self::STATUS_RETURNED_TO_DEPT], true)) {
            throw new InvalidArgumentException(__('correspondence.department_reply.already_replied'));
        }

        return [$item, $parsed];
    }

    /**
     * @param  list<\Illuminate\Http\UploadedFile>  $files
     */
    public function submitDepartmentReply(
        CorporateCommunication $communication,
        int $administratorId,
        string $details,
        array $files = [],
    ): void {
        $status = (int) $communication->status;

        if (! in_array($status, [self::STATUS_SENT, self::STATUS_RETURNED_TO_DEPT], true)) {
            throw new InvalidArgumentException(__('correspondence.department_reply.already_replied'));
        }

        DB::transaction(function () use ($communication, $administratorId, $details, $files) {
            $reply = \App\Models\CorporateCommunicationReply::query()->create([
                'corporate_communications_id' => (int) $communication->id,
                'branch_id' => (int) ($communication->branch_id ?: 0) ?: null,
                'details' => $details,
                'created_by' => $administratorId,
                'created_at' => now(),
            ]);

            foreach ($files as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $path = $file->store('correspondence/replies/'.date('Y/m'), 'public');
                \App\Models\CorporateCommunicationReplyAttachment::query()->create([
                    'corporate_communications_replies_id' => (int) $reply->id,
                    'file' => $path,
                    'file_name' => $file->getClientOriginalName(),
                ]);
            }

            $this->repository->updateStatusFields((int) $communication->id, [
                'status' => self::STATUS_REPLIED_BY_DEPT,
                'replied_status' => '1',
            ]);
            $this->repository->createAction(
                (int) $communication->id,
                self::STATUS_REPLIED_BY_DEPT,
                $administratorId,
                (int) ($communication->branch_id ?: 0) ?: null,
                $details,
            );
        });
    }

    public function departmentReplyUrl(CorporateCommunication $communication, int $administratorId = 1): string
    {
        $raw = \App\Support\CorporateCommunications\DepartmentReplyToken::build(
            (string) $communication->sms_tocken,
            $administratorId,
            1,
            2,
        );

        return route('public.correspondence.reply.show', ['token' => $raw]);
    }
}
