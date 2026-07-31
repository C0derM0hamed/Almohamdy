<?php

namespace App\Services\CorporateCommunications;

use App\Http\Requests\CorporateCommunications\UpdateCorporateCommunicationOutgoingLetterStatusRequest;
use App\Models\CorporateCommunicationOutgoingLetter;
use App\Repositories\CorporateCommunications\CorporateCommunicationOutgoingLetterRepository;
use App\Services\CorporateCommunications\DepartmentNotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CorporateCommunicationOutgoingLetterService
{
    public function __construct(
        private readonly CorporateCommunicationOutgoingLetterRepository $repository,
    ) {}

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
    public function listPaginated(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateFiltered(
            $filters,
            (int) config('hm.outgoing_correspondence.per_page', 15),
        );
    }

    public function statusCounters(): Collection
    {
        return $this->repository->statusCounters();
    }

    public function findForDetail(int $id): ?CorporateCommunicationOutgoingLetter
    {
        return $this->repository->findForDetail($id);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<array{file:UploadedFile,name:string}>  $attachments
     */
    public function store(array $payload, array $attachments = []): CorporateCommunicationOutgoingLetter
    {
        return DB::transaction(function () use ($payload, $attachments) {
            $userId = (int) session('hr_user_id', 0);
            $groupId = (int) session('companies_groups_id', 0);
            $year = (int) now()->format('Y');
            $registrationNumber = $this->repository->nextRegistrationNumber($groupId, $year);
            $firstAttachmentType = null;

            $letter = $this->repository->create([
                'date' => (string) time(),
                'branch_id' => (int) $payload['branch_id'],
                'sectors_id' => (int) $payload['sector_id'],
                'government_circulars_issuing_authority_id' => (int) $payload['authority_id'],
                'corporate_communications_senderTitle_id' => $payload['sender_title_id'] ?: 0,
                'issue_date' => $payload['issue_date'],
                'letter_content' => $payload['letter_content'],
                'government_circulars_receiving_mechanism_id' => $payload['receiving_mechanism_id'] ?: null,
                'sender_gender' => $payload['sender_gender'] ?: null,
                'sender' => $payload['recipient_name'],
                'job_title' => $payload['job_title'] ?: null,
                'type' => $payload['subject'],
                'receiving_response_date' => $payload['response_deadline'],
                'created_by' => $userId,
                'created_at' => now(),
                'companies_groups_id' => $groupId,
                'status' => 1,
                'replied_status' => '0',
                'sms_tocken' => md5(Str::uuid()->toString()),
                'document_status' => 1,
                'attachment_type' => null,
                'registration_number' => $registrationNumber,
                'year' => $year,
            ]);

            foreach ($attachments as $attachment) {
                $path = $attachment['file']->store('outgoing-correspondence/'.date('Y/m'), 'public');
                $this->repository->createAttachment(
                    (int) $letter->id,
                    $path,
                    $attachment['name'],
                );

                if ($firstAttachmentType === null) {
                    $firstAttachmentType = $attachment['file']->getClientOriginalExtension();
                }
            }

            if ($firstAttachmentType !== null) {
                $letter->forceFill(['attachment_type' => $firstAttachmentType])->save();
            }

            $this->repository->createTimelineEntry((int) $letter->id, 1, $userId);

            return $letter->fresh([
                'sector',
                'authority',
                'branch',
                'currentStatus',
                'attachments',
            ]) ?? $letter;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateStatus(
        CorporateCommunicationOutgoingLetter $letter,
        array $payload,
        ?UploadedFile $statusFile = null,
    ): CorporateCommunicationOutgoingLetter {
        $statusId = (int) $payload['status_id'];

        if (! in_array($statusId, UpdateCorporateCommunicationOutgoingLetterStatusRequest::updatableStatusIds(), true)) {
            throw new InvalidArgumentException('Invalid status');
        }

        if ((int) $letter->status === $statusId
            && $statusId !== UpdateCorporateCommunicationOutgoingLetterStatusRequest::STATUS_SUPPLEMENTARY) {
            throw new InvalidArgumentException(__('outgoing_correspondence.validation.status_unchanged'));
        }

        return DB::transaction(function () use ($letter, $payload, $statusFile, $statusId) {
            $userId = (int) session('hr_user_id', 0);
            $branchId = (int) ($letter->branch_id ?: 0) ?: null;
            $reason = filled($payload['reason'] ?? null) ? (string) $payload['reason'] : null;
            if ($statusId === UpdateCorporateCommunicationOutgoingLetterStatusRequest::STATUS_SUPPLEMENTARY) {
                $reason = Str::limit(
                    (string) ($payload['supplementary_content'] ?? ''),
                    200,
                    ''
                );
            }
            $storedFile = null;

            if ($statusFile !== null) {
                $storedFile = $statusFile->store('outgoing-correspondence/status/'.date('Y/m'), 'public');
            }

            $attributes = [
                'status' => $statusId,
                // Legacy schema marks issue_date ON UPDATE CURRENT_TIMESTAMP.
                'issue_date' => $letter->getRawOriginal('issue_date') ?? $letter->issue_date,
            ];

            if ($statusId === UpdateCorporateCommunicationOutgoingLetterStatusRequest::STATUS_APPROVED) {
                $attributes['replied_status'] = '1';
            }

            if ($statusId === UpdateCorporateCommunicationOutgoingLetterStatusRequest::STATUS_RETURNED_TO_DEPARTMENT) {
                $attributes['replied_status'] = '3';
            }

            $this->repository->updateStatusFields((int) $letter->id, $attributes);
            $this->repository->createAction(
                (int) $letter->id,
                $statusId,
                $userId,
                $branchId,
                $reason,
            );
            $this->repository->createTimelineEntry(
                (int) $letter->id,
                $statusId,
                $userId,
                $statusId === UpdateCorporateCommunicationOutgoingLetterStatusRequest::STATUS_SUPPLEMENTARY
                    ? null
                    : $reason,
            );

            match ($statusId) {
                UpdateCorporateCommunicationOutgoingLetterStatusRequest::STATUS_SHIPMENT_REQUESTED => $this->repository->createShipmentRequestDocument(
                    (int) $letter->id,
                    (string) $payload['shipment_number'],
                    (string) $storedFile,
                    $userId,
                    $statusId,
                    $branchId,
                ),
                UpdateCorporateCommunicationOutgoingLetterStatusRequest::STATUS_DELIVERED_TO_POSTAL => $this->repository->createDeliverToPostal(
                    (int) $letter->id,
                    (string) $payload['date_time_receipt'],
                    (string) $payload['postal_employee_name'],
                    (string) $storedFile,
                    $userId,
                    $branchId,
                ),
                UpdateCorporateCommunicationOutgoingLetterStatusRequest::STATUS_DELIVERED_TO_ENTITY => $this->repository->createDeliverToAgency(
                    (int) $letter->id,
                    (string) $payload['date_time_receipt'],
                    (string) $storedFile,
                    $userId,
                    $branchId,
                ),
                UpdateCorporateCommunicationOutgoingLetterStatusRequest::STATUS_RETURNED_BY_ENTITY => $this->repository->createReturnShipment(
                    (int) $letter->id,
                    (string) $payload['return_date'],
                    (string) $payload['reason'],
                    (string) $storedFile,
                    $userId,
                    $branchId,
                ),
                UpdateCorporateCommunicationOutgoingLetterStatusRequest::STATUS_DELIVERED_BY_SPECIALIST => $this->repository->createSpecialistDelivery(
                    (int) $letter->id,
                    (string) $payload['delivery_date'],
                    (string) $payload['registration_number'],
                    (string) $payload['delivered_by'],
                    $userId,
                    $branchId,
                ),
                UpdateCorporateCommunicationOutgoingLetterStatusRequest::STATUS_ENTITY_REPLIED => $this->repository->createShipmentRequestDocument(
                    (int) $letter->id,
                    (string) ($payload['shipment_number'] ?: ''),
                    (string) $storedFile,
                    $userId,
                    $statusId,
                    $branchId,
                ),
                UpdateCorporateCommunicationOutgoingLetterStatusRequest::STATUS_SUPPLEMENTARY => $this->repository->createSupplementary(
                    (int) $letter->id,
                    (string) $payload['supplementary_content'],
                    $this->repository->nextSupplementarySerial((int) $letter->id),
                    $userId,
                    $branchId,
                ),
                default => null,
            };

            if ($statusId === UpdateCorporateCommunicationOutgoingLetterStatusRequest::STATUS_RETURNED_TO_DEPARTMENT) {
                $fresh = $letter->fresh() ?? $letter;
                $this->notifyReviseRecipients($fresh);
            }

            return $letter->fresh([
                'sector',
                'authority',
                'senderTitle',
                'receivingMechanism',
                'branch',
                'currentStatus',
                'attachments',
                'timelineEntries.status',
                'supplementaryLetters',
            ]) ?? $letter;
        });
    }

    private function notifyReviseRecipients(CorporateCommunicationOutgoingLetter $letter): void
    {
        $admin = \App\Models\GovernmentCircularSectionAdministrator::query()
            ->whereKey((int) $letter->created_by)
            ->first(['id', 'administrator', 'email', 'mobile']);

        if ($admin === null) {
            $admin = \App\Models\GovernmentCircularSectionAdministrator::query()
                ->where('publish', 1)
                ->where('companies_groups_id', (int) $letter->companies_groups_id)
                ->orderBy('id')
                ->first(['id', 'administrator', 'email', 'mobile']);
        }

        if ($admin === null) {
            return;
        }

        $notifier = app(DepartmentNotificationService::class);
        $notifier->notifyAdministrator(
            $admin,
            __('outgoing_correspondence.department_revise.title').' '.$letter->displayNumber(),
            __('outgoing_correspondence.department_revise.subtitle'),
            $this->departmentReviseUrl($letter, (int) $admin->id),
            'outgoing_correspondence_returned',
        );
    }

    public function updatableStatusOptions(?int $currentStatusId = null): Collection
    {
        return $this->repository->statusOptions()
            ->filter(fn ($status) => in_array(
                (int) $status->id,
                UpdateCorporateCommunicationOutgoingLetterStatusRequest::updatableStatusIds(),
                true
            ))
            ->filter(function ($status) use ($currentStatusId) {
                $id = (int) $status->id;

                if ($id === UpdateCorporateCommunicationOutgoingLetterStatusRequest::STATUS_SUPPLEMENTARY) {
                    return true;
                }

                return $currentStatusId === null || $id !== $currentStatusId;
            })
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

    public function templateOptions(): Collection
    {
        return $this->repository->templateOptions();
    }

    public function branchOptions(): Collection
    {
        return $this->repository->branchOptions();
    }

    public function statusLabel(?CorporateCommunicationOutgoingLetter $letter): string
    {
        return $letter?->currentStatus?->localizedName()
            ?: __('outgoing_correspondence.status_unknown');
    }

    public function statusColor(?CorporateCommunicationOutgoingLetter $letter): string
    {
        return $letter?->currentStatus?->badgeColor() ?: '#64748b';
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

    public const STATUS_RETURNED_TO_DEPT = 5;

    public const STATUS_REVISED_BY_DEPT = 3;

    public function resolveByReplyToken(string $rawToken): array
    {
        $parsed = \App\Support\CorporateCommunications\DepartmentReplyToken::parse($rawToken);
        $item = $this->repository->findBySmsToken($parsed->token);

        if ($item === null) {
            throw new InvalidArgumentException(__('outgoing_correspondence.department_revise.invalid_link'));
        }

        return [$item, $parsed];
    }

    public function openDepartmentRevise(string $rawToken): array
    {
        [$item, $parsed] = $this->resolveByReplyToken($rawToken);

        if ((int) $item->status !== self::STATUS_RETURNED_TO_DEPT) {
            throw new InvalidArgumentException(__('outgoing_correspondence.department_revise.not_returned'));
        }

        return [$item, $parsed];
    }

    public function submitDepartmentRevise(
        CorporateCommunicationOutgoingLetter $letter,
        int $administratorId,
        string $subject,
        string $letterContent,
    ): void {
        if ((int) $letter->status !== self::STATUS_RETURNED_TO_DEPT) {
            throw new InvalidArgumentException(__('outgoing_correspondence.department_revise.not_returned'));
        }

        DB::transaction(function () use ($letter, $administratorId, $subject, $letterContent) {
            $attributes = [
                'type' => $subject,
                'letter_content' => $letterContent,
                'status' => self::STATUS_REVISED_BY_DEPT,
                'replied_status' => '1',
                'issue_date' => $letter->getRawOriginal('issue_date')
                    ?? optional($letter->issue_date)?->format('Y-m-d H:i:s'),
            ];

            $this->repository->updateStatusFields((int) $letter->id, $attributes);
            $this->repository->createAction(
                (int) $letter->id,
                self::STATUS_REVISED_BY_DEPT,
                $administratorId,
                (int) ($letter->branch_id ?: 0) ?: null,
                'Department revised returned letter',
            );
            $this->repository->createTimelineEntry(
                (int) $letter->id,
                self::STATUS_REVISED_BY_DEPT,
                $administratorId,
                'Department revised returned letter',
            );
        });
    }

    public function departmentReviseUrl(CorporateCommunicationOutgoingLetter $letter, int $administratorId = 1): string
    {
        $raw = \App\Support\CorporateCommunications\DepartmentReplyToken::build(
            (string) $letter->sms_tocken,
            $administratorId,
            1,
            2,
        );

        return route('public.outgoing-correspondence.revise.show', ['token' => $raw]);
    }
}
