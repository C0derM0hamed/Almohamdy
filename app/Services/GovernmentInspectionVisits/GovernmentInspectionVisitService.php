<?php

namespace App\Services\GovernmentInspectionVisits;

use App\Http\Requests\GovernmentInspectionVisits\UpdateGovernmentInspectionVisitStatusRequest;
use App\Models\GovernmentInspectionVisit;
use App\Models\GovernmentInspectionVisitAttachment;
use App\Repositories\GovernmentInspectionVisits\GovernmentInspectionVisitRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class GovernmentInspectionVisitService
{
    public function __construct(
        private readonly GovernmentInspectionVisitRepository $repository,
    ) {}

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
    public function listPaginated(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateFiltered(
            $filters,
            (int) config('hm.inspection_visits.per_page', 15),
        );
    }

    public function statusCounters(): Collection
    {
        return $this->repository->statusCounters();
    }

    public function findForDetail(int $id): ?GovernmentInspectionVisit
    {
        return $this->repository->findForDetail($id);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<UploadedFile>  $attachmentFiles
     */
    public function store(array $payload, array $attachmentFiles = []): GovernmentInspectionVisit
    {
        $adminIds = [];

        $created = DB::transaction(function () use ($payload, $attachmentFiles, &$adminIds) {
            $companyId = (int) session('companies_groups_id', 0);
            $userId = (int) session('hr_user_id', 0);
            $sectionId = (int) $payload['section_id'];

            $number = $this->repository->createVisitNumber([
                'companies_groups_id' => $companyId,
                'branch_id' => (int) $payload['branch_id'],
                'abuses_status' => (string) $payload['abuses_status'],
                'notes_status' => (string) $payload['notes_status'],
                'representative_name' => $payload['representative_name'],
                'subject' => $payload['subject'],
                'report' => $payload['report'],
                'token' => md5('n'.time().rand()),
                'created_at' => now(),
                'created_by' => $userId,
                'status' => 0,
            ]);

            $admins = $this->repository->publishedAdministratorsForSections([$sectionId]);
            $adminIds = $admins->pluck('id')->map(fn ($id) => (int) $id)->all();
            $users = $admins->pluck('id')->map(fn ($id) => (int) $id)->implode(',');

            $visit = $this->repository->createVisit([
                'visit_number' => (int) $number->id,
                'date' => (string) time(),
                'branch_id' => (int) $payload['branch_id'],
                'government_circulars_issuing_authority_id' => (int) $payload['authority_id'],
                'visit_type' => 1,
                'visit_date' => $payload['visit_date'],
                'reply_time' => $payload['reply_time'],
                'government_circulars_sections_id' => (string) $sectionId,
                'users' => $users !== '' ? $users : '0',
                'created_by' => $userId,
                'created_at' => now(),
                'companies_groups_id' => $companyId,
                'status' => 1,
                'sms_tocken' => md5(Str::uuid()->toString()),
                'reply' => 0,
                'government_inspection_visits_types_id' => (int) $payload['visit_type_id'],
            ]);

            $this->repository->createFindings((int) $visit->id, $payload['findings']);
            $this->repository->createTimelineEntry((int) $visit->id, 1, $sectionId, $userId);

            if ($adminIds !== []) {
                $this->repository->createReceiptReports((int) $visit->id, $adminIds);
            }

            foreach ($attachmentFiles as $file) {
                $this->storeAttachmentForVisit($visit, $file, pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            }

            return $visit->fresh([
                'authority',
                'section',
                'currentStatus',
                'visitType',
                'branch',
                'visitNumberRecord',
                'attachments',
            ]) ?? $visit;
        });

        if ($adminIds !== []) {
            $this->notifyDepartmentAdmins($created, $adminIds);
        }

        return $created;
    }

    /**
     * @param  list<int>  $administratorIds
     */
    private function notifyDepartmentAdmins(GovernmentInspectionVisit $visit, array $administratorIds, bool $returned = false): void
    {
        $notifier = app(\App\Services\CorporateCommunications\DepartmentNotificationService::class);

        $admins = \App\Models\GovernmentCircularSectionAdministrator::query()
            ->whereIn('id', $administratorIds)
            ->get(['id', 'administrator', 'email', 'mobile']);

        foreach ($admins as $admin) {
            $notifier->notifyAdministrator(
                $admin,
                $returned
                    ? __('inspection_visits.department_reply.returned_title').' '.$visit->displayNumber()
                    : __('inspection_visits.department_reply.title').' '.$visit->displayNumber(),
                __('inspection_visits.department_reply.subtitle'),
                $this->departmentReplyUrl($visit, (int) $admin->id, $returned),
                $returned ? 'inspection_visits_returned' : 'inspection_visits',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateStatus(GovernmentInspectionVisit $visit, array $payload, ?UploadedFile $noticeFile = null): GovernmentInspectionVisit
    {
        $statusId = (int) $payload['status_id'];

        if (! in_array($statusId, UpdateGovernmentInspectionVisitStatusRequest::updatableStatusIds(), true)) {
            throw new InvalidArgumentException('Invalid status');
        }

        if ((int) $visit->status === $statusId) {
            throw new InvalidArgumentException(__('inspection_visits.validation.status_unchanged'));
        }

        return DB::transaction(function () use ($visit, $payload, $noticeFile, $statusId) {
            $userId = (int) session('hr_user_id', 0);
            $sectionId = $visit->sectionIdValue();

            if ($statusId === UpdateGovernmentInspectionVisitStatusRequest::STATUS_ENTITY_NOTIFIED) {
                if ($noticeFile === null) {
                    throw new InvalidArgumentException(__('inspection_visits.validation.notice_file_required'));
                }

                $path = $noticeFile->store('inspection-visits/notices/'.date('Y/m'), 'public');
                $this->repository->createReplySubmission([
                    'government_inspection_visits_id' => (int) $visit->id,
                    'date' => now(),
                    'attachment_type' => (string) $noticeFile->getClientOriginalExtension(),
                    'file_name' => $path,
                    'created_at' => now(),
                    'created_by' => $userId,
                ]);
            }

            if ($statusId === UpdateGovernmentInspectionVisitStatusRequest::STATUS_RETURNED) {
                $returns = $payload['returns'] ?? [];

                if ($returns === []) {
                    throw new InvalidArgumentException(__('inspection_visits.validation.return_reasons_required'));
                }

                foreach ($returns as $item) {
                    if (! $this->repository->findingBelongsToVisit((int) $item['finding_id'], (int) $visit->id)) {
                        throw new InvalidArgumentException(__('inspection_visits.validation.invalid_finding'));
                    }
                }

                $this->repository->createReturnedItems((int) $visit->id, $returns, $userId);

                $adminIds = array_values(array_filter(array_map(
                    static fn ($id) => (int) $id,
                    preg_split('/\s*,\s*/', (string) $visit->users) ?: []
                ), static fn ($id) => $id > 0));

                if ($adminIds !== []) {
                    $this->notifyDepartmentAdmins($visit, $adminIds, true);
                }
            }

            $this->repository->updateStatus((int) $visit->id, $statusId);
            $this->repository->createTimelineEntry((int) $visit->id, $statusId, $sectionId, $userId);

            return $visit->fresh([
                'authority',
                'section',
                'currentStatus',
                'visitType',
                'branch',
                'visitNumberRecord',
                'findings',
                'attachments',
                'returnedItems.finding',
                'replySubmissions',
                'timelineEntries.status',
            ]) ?? $visit;
        });
    }

    public function storeAttachment(GovernmentInspectionVisit $visit, UploadedFile $file, string $name): GovernmentInspectionVisitAttachment
    {
        return $this->storeAttachmentForVisit($visit, $file, $name);
    }

    public function receiptReport(int $visitId): Collection
    {
        return $this->repository->receiptReportsForVisit($visitId);
    }

    public function updatableStatusOptions(?int $currentStatusId = null): Collection
    {
        return $this->repository->statusOptions()
            ->filter(fn ($status) => in_array(
                (int) $status->id,
                UpdateGovernmentInspectionVisitStatusRequest::updatableStatusIds(),
                true
            ))
            ->filter(fn ($status) => $currentStatusId === null || (int) $status->id !== $currentStatusId)
            ->values();
    }

    public function visitTypeOptions(): Collection
    {
        return $this->repository->visitTypeOptions();
    }

    public function authorityOptions(): Collection
    {
        return $this->repository->authorityOptions();
    }

    public function sectionOptions(): Collection
    {
        return $this->repository->sectionOptions();
    }

    public function statusOptions(): Collection
    {
        return $this->repository->statusOptions();
    }

    public function branchOptions(): Collection
    {
        return $this->repository->branchOptions();
    }

    public function statusLabel(?GovernmentInspectionVisit $visit): string
    {
        return $visit?->currentStatus?->localizedName() ?: __('inspection_visits.status_unknown');
    }

    public function statusColor(?GovernmentInspectionVisit $visit): string
    {
        return $visit?->currentStatus?->badgeColor() ?: '#64748b';
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

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        $legacyFiles = public_path('files/'.basename($normalized));
        if (is_file($legacyFiles)) {
            return asset('files/'.basename($normalized));
        }

        $legacyReporting = public_path($normalized);
        if (is_file($legacyReporting)) {
            return asset($normalized);
        }

        return asset(ltrim($normalized, '/'));
    }

    public const STATUS_REPLIED_BY_DEPARTMENT = 2;

    public function resolveByReplyToken(string $rawToken): array
    {
        $parsed = \App\Support\CorporateCommunications\DepartmentReplyToken::parse($rawToken);
        $visit = $this->repository->findBySmsToken($parsed->token);

        if ($visit === null) {
            throw new InvalidArgumentException(__('inspection_visits.department_reply.invalid_link'));
        }

        return [$visit, $parsed];
    }

    public function openDepartmentReply(string $rawToken): array
    {
        [$visit, $parsed] = $this->resolveByReplyToken($rawToken);
        $pending = $this->repository->pendingFindings((int) $visit->id);

        if ($pending->isEmpty()) {
            throw new InvalidArgumentException(__('inspection_visits.department_reply.already_replied'));
        }

        return [$visit, $parsed, $pending];
    }

    public function openReturnedReply(string $rawToken): array
    {
        [$visit, $parsed] = $this->resolveByReplyToken($rawToken);
        $pending = $this->repository->pendingReturnedItems((int) $visit->id);

        if ($pending->isEmpty()) {
            throw new InvalidArgumentException(__('inspection_visits.department_reply.already_replied'));
        }

        return [$visit, $parsed, $pending];
    }

    /**
     * @param  array{items:list<array{id:int,reply:string}>,files:array<int,?UploadedFile>}  $payload
     */
    public function submitDepartmentReply(
        GovernmentInspectionVisit $visit,
        int $administratorId,
        array $payload,
    ): void {
        DB::transaction(function () use ($visit, $administratorId, $payload) {
            $items = [];

            foreach ($payload['items'] as $index => $row) {
                $findingId = (int) $row['id'];

                if (! $this->repository->findingBelongsToVisit($findingId, (int) $visit->id)) {
                    throw new InvalidArgumentException(__('inspection_visits.validation.invalid_finding'));
                }

                $path = null;
                $file = $payload['files'][$index] ?? null;

                if ($file instanceof UploadedFile) {
                    $path = $file->store('inspection-visits/replies/'.date('Y/m'), 'public');
                }

                $items[] = [
                    'id' => $findingId,
                    'reply' => trim((string) $row['reply']),
                    'uploaded_file' => $path,
                ];
            }

            $this->repository->applyFindingReplies($items, $administratorId);
            $this->repository->updateStatus((int) $visit->id, self::STATUS_REPLIED_BY_DEPARTMENT);
            $this->repository->createTimelineEntry(
                (int) $visit->id,
                self::STATUS_REPLIED_BY_DEPARTMENT,
                $visit->sectionIdValue(),
                $administratorId,
            );
        });
    }

    /**
     * @param  array{items:list<array{id:int,reply:string}>,files:array<int,?UploadedFile>}  $payload
     */
    public function submitReturnedReply(
        GovernmentInspectionVisit $visit,
        int $administratorId,
        array $payload,
    ): void {
        DB::transaction(function () use ($visit, $administratorId, $payload) {
            $pendingIds = $this->repository->pendingReturnedItems((int) $visit->id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $items = [];

            foreach ($payload['items'] as $index => $row) {
                $returnId = (int) $row['id'];

                if (! in_array($returnId, $pendingIds, true)) {
                    throw new InvalidArgumentException(__('inspection_visits.department_reply.invalid_return_item'));
                }

                $path = null;
                $file = $payload['files'][$index] ?? null;

                if ($file instanceof UploadedFile) {
                    $path = $file->store('inspection-visits/replies/'.date('Y/m'), 'public');
                }

                $items[] = [
                    'id' => $returnId,
                    'reply' => trim((string) $row['reply']),
                    'uploaded_file' => $path,
                ];
            }

            $this->repository->applyReturnedReplies($items, $administratorId);
            $this->repository->updateStatus((int) $visit->id, self::STATUS_REPLIED_BY_DEPARTMENT);
            $this->repository->createTimelineEntry(
                (int) $visit->id,
                self::STATUS_REPLIED_BY_DEPARTMENT,
                $visit->sectionIdValue(),
                $administratorId,
            );
        });
    }

    public function departmentReplyUrl(GovernmentInspectionVisit $visit, int $administratorId = 1, bool $returned = false): string
    {
        $raw = \App\Support\CorporateCommunications\DepartmentReplyToken::build(
            (string) $visit->sms_tocken,
            $administratorId,
            1,
            2,
        );

        return route(
            $returned ? 'public.inspection-visits.reply-returned.show' : 'public.inspection-visits.reply.show',
            ['token' => $raw],
        );
    }

    private function storeAttachmentForVisit(
        GovernmentInspectionVisit $visit,
        UploadedFile $file,
        string $name,
    ): GovernmentInspectionVisitAttachment {
        $storedName = $file->hashName();
        $dir = 'inspection-visits/'.date('Y/m');
        $path = $file->storeAs($dir, $storedName, 'public');

        return $this->repository->createAttachment([
            'government_inspection_visits_id' => (int) $visit->id,
            'file_name' => $path,
            'name' => $name !== '' ? $name : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'created_at' => now(),
            'created_by' => (int) session('hr_user_id', 0),
        ]);
    }
}
