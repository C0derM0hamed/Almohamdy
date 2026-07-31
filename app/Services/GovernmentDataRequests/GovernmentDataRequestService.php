<?php

namespace App\Services\GovernmentDataRequests;

use App\Http\Requests\GovernmentDataRequests\UpdateGovernmentDataRequestStatusRequest;
use App\Models\GovernmentDataRequest;
use App\Repositories\GovernmentDataRequests\GovernmentDataRequestRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class GovernmentDataRequestService
{
    public function __construct(
        private readonly GovernmentDataRequestRepository $repository,
    ) {}

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
    public function listPaginated(array $filters): LengthAwarePaginator
    {
        return $this->repository->paginateFiltered(
            $filters,
            (int) config('hm.data_requests.per_page', 15),
        );
    }

    public function statusCounters(): Collection
    {
        return $this->repository->statusCounters();
    }

    public function findForDetail(int $id): ?GovernmentDataRequest
    {
        return $this->repository->findForDetail($id);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<array{file:UploadedFile,name:string}>  $attachments
     * @return list<GovernmentDataRequest>
     */
    public function storeMany(array $payload, array $attachments = []): array
    {
        $sectionIds = array_values(array_unique(array_map(
            static fn ($id) => (int) $id,
            $payload['section_ids'] ?? [(int) ($payload['section_id'] ?? 0)]
        )));
        $sectionIds = array_values(array_filter($sectionIds, static fn ($id) => $id > 0));

        if ($sectionIds === []) {
            throw new InvalidArgumentException(__('data_requests.validation.section_required'));
        }

        $created = [];

        foreach ($sectionIds as $sectionId) {
            $rowPayload = $payload;
            $rowPayload['section_id'] = $sectionId;
            $created[] = $this->store($rowPayload, $attachments);
        }

        return $created;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<array{file:UploadedFile,name:string}>  $attachments
     */
    public function store(array $payload, array $attachments = []): GovernmentDataRequest
    {
        return DB::transaction(function () use ($payload, $attachments) {
            $token = Str::random(16);
            $userId = (int) session('hr_user_id', 0);
            $sectionId = (int) $payload['section_id'];

            $request = $this->repository->create([
                'id_siction' => (int) $payload['entity_id'],
                'id_subsiction' => (int) $payload['data_type_id'],
                'companies_groups_id' => (int) session('companies_groups_id', 0),
                'branch_id' => (int) $payload['branch_id'],
                'subjec' => $payload['subject'],
                'date' => $payload['request_date'],
                'Date_receipt' => $payload['receipt_date'],
                'Request_Receipt' => (string) $payload['receiving_method_id'],
                'send_Section' => (string) $sectionId,
                'Data_delivery' => $payload['deadline'],
                'status' => 6,
                'userid' => $userId,
                'create_at' => now()->format('Y-m-d\TH:i'),
                'Reminderـtime' => $payload['reminder_at'],
                'c' => $token,
                'count' => '1',
            ]);

            foreach ($attachments as $attachment) {
                $path = $attachment['file']->store('data-requests/'.date('Y/m'), 'public');
                $this->repository->createMailFile(
                    (int) $request->id,
                    $attachment['name'],
                    $path,
                );
            }

            $admins = $this->repository->publishedAdministratorsForSections([$sectionId]);
            if ($admins->isNotEmpty()) {
                $this->repository->createViews(
                    $token,
                    $admins->pluck('id')->map(fn ($id) => (int) $id)->all(),
                );
            }

            $created = $request->fresh([
                'entity',
                'dataType',
                'section',
                'branch',
                'currentStatus',
                'mailFiles',
            ]) ?? $request;

            $this->notifyDepartmentAdmins($created, $sectionId);

            return $created;
        });
    }

    private function notifyDepartmentAdmins(GovernmentDataRequest $request, int $sectionId): void
    {
        $admins = \App\Models\GovernmentCircularSectionAdministrator::query()
            ->where('publish', 1)
            ->where('government_circulars_sections_id', $sectionId)
            ->where('companies_groups_id', (int) $request->companies_groups_id)
            ->get(['id', 'administrator', 'email', 'mobile']);

        if ($admins->isEmpty()) {
            return;
        }

        $notifier = app(\App\Services\CorporateCommunications\DepartmentNotificationService::class);

        foreach ($admins as $admin) {
            $notifier->notifyAdministrator(
                $admin,
                __('data_requests.department_reply.title').' '.$request->displayNumber(),
                __('data_requests.department_reply.subtitle'),
                $this->departmentReplyUrl($request, (int) $admin->id),
                'data_requests',
            );
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function updateStatus(
        GovernmentDataRequest $request,
        array $payload,
        ?UploadedFile $noticeFile = null,
    ): GovernmentDataRequest {
        $statusId = (int) $payload['status_id'];

        if (! in_array($statusId, UpdateGovernmentDataRequestStatusRequest::updatableStatusIds(), true)) {
            throw new InvalidArgumentException('Invalid status');
        }

        if ((int) $request->status === $statusId) {
            throw new InvalidArgumentException(__('data_requests.validation.status_unchanged'));
        }

        return DB::transaction(function () use ($request, $payload, $noticeFile, $statusId) {
            $userId = (int) session('hr_user_id', 0);
            $attributes = [
                'status' => $statusId,
                'status_order' => $statusId,
                'Date_status' => now()->format('Y-m-d h:i:sa'),
                'userid_status' => $userId,
            ];

            if (filled($payload['reason'] ?? null)) {
                $attributes['becuse'] = $payload['reason'];
            }

            if ($statusId === UpdateGovernmentDataRequestStatusRequest::STATUS_ENTITY_NOTIFIED) {
                if ($noticeFile === null) {
                    throw new InvalidArgumentException(__('data_requests.validation.notice_file_required'));
                }

                $path = $noticeFile->store('data-requests/notices/'.date('Y/m'), 'public');
                $name = trim((string) ($payload['notice_name'] ?? ''));
                if ($name === '') {
                    $name = pathinfo($noticeFile->getClientOriginalName(), PATHINFO_FILENAME);
                }

                $this->repository->createAnswerFile((int) $request->id, $name, $path);
                $attributes['userid_new'] = $userId;
                $attributes['date2'] = now()->format('Y-m-d\TH:i');
            }

            $this->repository->updateStatusFields((int) $request->id, $attributes);
            $this->repository->createTimelineEntry((int) $request->id, $statusId, $userId);

            return $request->fresh([
                'entity',
                'dataType',
                'section',
                'branch',
                'currentStatus',
                'mailFiles',
                'answerFiles',
                'timelineEntries.statusRecord',
                'views.administrator.section',
            ]) ?? $request;
        });
    }

    public function receiptViews(GovernmentDataRequest $request): Collection
    {
        return $this->repository->receiptViewsForRequest($request);
    }

    public function updatableStatusOptions(?int $currentStatusId = null): Collection
    {
        return $this->repository->statusOptions()
            ->filter(fn ($status) => in_array(
                (int) $status->id,
                UpdateGovernmentDataRequestStatusRequest::updatableStatusIds(),
                true
            ))
            ->filter(fn ($status) => $currentStatusId === null || (int) $status->id !== $currentStatusId)
            ->values();
    }

    public function entityOptions(): Collection
    {
        return $this->repository->entityOptions();
    }

    public function dataTypeOptions(?int $entityId = null): Collection
    {
        return $this->repository->dataTypeOptions($entityId);
    }

    public function receivingMethodOptions(): Collection
    {
        return $this->repository->receivingMethodOptions();
    }

    public function statusOptions(): Collection
    {
        return $this->repository->statusOptions();
    }

    public function sectionOptions(): Collection
    {
        return $this->repository->sectionOptions();
    }

    public function branchOptions(): Collection
    {
        return $this->repository->branchOptions();
    }

    public function statusLabel(?GovernmentDataRequest $request): string
    {
        return $request?->currentStatus?->localizedName() ?: __('data_requests.status_unknown');
    }

    public function statusColor(?GovernmentDataRequest $request): string
    {
        return $request?->currentStatus?->badgeColor() ?: '#64748b';
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

        $legacy = public_path('report_file/'.basename($normalized));
        if (is_file($legacy)) {
            return asset('report_file/'.basename($normalized));
        }

        return asset(ltrim($normalized, '/'));
    }

    public const STATUS_SENT_TO_DEPT = 6;

    public const STATUS_RETURNED = 2;

    public const STATUS_DATA_PROVIDED = 8;

    public function resolveByReplyToken(string $rawToken): array
    {
        $parsed = \App\Support\CorporateCommunications\DepartmentReplyToken::parse($rawToken);
        $request = $this->repository->findByToken($parsed->token);

        if ($request === null) {
            throw new InvalidArgumentException(__('data_requests.department_reply.invalid_link'));
        }

        return [$request, $parsed];
    }

    public function openDepartmentReply(string $rawToken): array
    {
        [$request, $parsed] = $this->resolveByReplyToken($rawToken);
        $status = (int) $request->status;

        if (! in_array($status, [self::STATUS_SENT_TO_DEPT, self::STATUS_RETURNED], true)) {
            throw new InvalidArgumentException(__('data_requests.department_reply.already_replied'));
        }

        return [$request, $parsed];
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    public function submitDepartmentReply(
        GovernmentDataRequest $request,
        int $administratorId,
        string $answerText,
        array $files = [],
    ): void {
        $status = (int) $request->status;

        if (! in_array($status, [self::STATUS_SENT_TO_DEPT, self::STATUS_RETURNED], true)) {
            throw new InvalidArgumentException(__('data_requests.department_reply.already_replied'));
        }

        DB::transaction(function () use ($request, $administratorId, $answerText, $files) {
            $this->repository->updateStatusFields((int) $request->id, [
                'Answer' => 1,
                'AnswerText' => $answerText,
                'AnswerDate' => now()->format('Y-m-d H:i:s'),
                'Answer_userid' => $administratorId,
                'status' => self::STATUS_DATA_PROVIDED,
            ]);

            foreach ($files as $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $path = $file->store('data-requests/answers/'.date('Y/m'), 'public');
                $this->repository->createAnswerFile(
                    (int) $request->id,
                    pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    $path,
                );
            }

            $this->repository->createTimelineEntry(
                (int) $request->id,
                self::STATUS_DATA_PROVIDED,
                $administratorId,
            );
        });
    }

    public function departmentReplyUrl(GovernmentDataRequest $request, int $administratorId = 1): string
    {
        $raw = \App\Support\CorporateCommunications\DepartmentReplyToken::build(
            (string) $request->c,
            $administratorId,
            1,
            2,
        );

        return route('public.data-requests.reply.show', ['token' => $raw]);
    }
}
