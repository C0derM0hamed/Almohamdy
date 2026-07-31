<?php

namespace App\Services\GovernmentCirculars;

use App\Http\Requests\GovernmentCirculars\UpdateGovernmentCircularStatusRequest;
use App\Models\GovernmentCircular;
use App\Models\GovernmentCircularSection;
use App\Repositories\GovernmentCirculars\GovernmentCircularRepository;
use App\Support\CorporateCommunications\DepartmentReplyToken;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class GovernmentCircularService
{
    public function __construct(
        private readonly GovernmentCircularRepository $repository,
    ) {}

    public function listPaginated(
        string $subject,
        ?int $authorityId,
        ?int $sectionId,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?int $branchId = null,
    ): LengthAwarePaginator {
        return $this->repository->paginateFiltered(
            $subject,
            $authorityId,
            $sectionId,
            (int) config('hm.government_circulars.per_page', 15),
            $fromDate,
            $toDate,
            $branchId,
        );
    }

    public function findForDetail(int $id): ?GovernmentCircular
    {
        return $this->repository->findForDetail($id);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<UploadedFile>  $files
     */
    public function store(array $payload, array $files = []): GovernmentCircular
    {
        $paths = [];

        foreach ($files as $file) {
            $paths[] = $file->store('government-circulars/'.date('Y/m'), 'public');
        }

        $primaryPath = $paths[0] ?? null;
        $sectionIds = array_values(array_unique(array_map(
            static fn ($id) => (int) $id,
            $payload['section_ids'] ?? [(int) ($payload['section_id'] ?? 0)]
        )));
        $sectionIds = array_values(array_filter($sectionIds, static fn ($id) => $id > 0));

        $circular = $this->repository->create([
            'date' => (string) time(),
            'branch_id' => (int) $payload['branch_id'],
            'government_circulars_issuing_authority_id' => (int) $payload['authority_id'],
            'government_circulars_issuing_authority_classification_id' => (int) $payload['classification_id'],
            'issue_date' => $payload['issue_date'],
            'received_date' => $payload['received_date'],
            'government_circulars_receiving_mechanism_id' => (int) $payload['receiving_mechanism_id'],
            'subject' => $payload['subject'],
            'government_circulars_sections_id' => implode(',', $sectionIds),
            'circulars_file' => $primaryPath,
            'created_by' => (int) session('hr_user_id', 0),
            'created_at' => now(),
            'companies_groups_id' => (int) session('companies_groups_id', 0),
            'status' => 0,
            'sms_tocken' => md5(Str::uuid()->toString()),
            'attachment_type' => $primaryPath !== null ? ($files[0]?->getClientOriginalExtension()) : null,
            'companies_groups_id_recipients' => (string) ((int) session('companies_groups_id', 1) ?: 1),
            'notification_type' => (int) $payload['notification_type'],
        ]);

        foreach ($paths as $path) {
            $circular->attachments()->create([
                'circulars_file' => $path,
            ]);
        }

        $this->distributeToSectionAdministrators($circular);

        $this->notifyFormalPageRecipients($circular);

        return $circular->fresh(['authority', 'section', 'currentStatus']) ?? $circular;
    }

    private function notifyFormalPageRecipients(GovernmentCircular $circular): void
    {
        $admins = $this->repository->publishedAdministratorsForSections($circular->sectionIds());

        if ($admins->isEmpty()) {
            return;
        }

        $fullAdmins = \App\Models\GovernmentCircularSectionAdministrator::query()
            ->whereIn('id', $admins->pluck('id')->map(fn ($id) => (int) $id)->all())
            ->get(['id', 'administrator', 'email', 'mobile']);

        $notifier = app(\App\Services\CorporateCommunications\DepartmentNotificationService::class);

        foreach ($fullAdmins as $admin) {
            $notifier->notifyAdministrator(
                $admin,
                __('government_circulars.formal.title').' '.$circular->displayNumber(),
                __('government_circulars.formal.subtitle'),
                $this->formalPageUrl($circular, (int) $admin->id, self::CHANNEL_EMAIL),
                'government_circulars',
            );
        }
    }

    public function distributeToSectionAdministrators(GovernmentCircular $circular): int
    {
        $admins = $this->repository->publishedAdministratorsForSections($circular->sectionIds());

        if ($admins->isEmpty()) {
            return 0;
        }

        return $this->repository->createReceiptReports(
            (int) $circular->id,
            $admins->pluck('id')->map(fn ($id) => (int) $id)->all(),
        );
    }

    public function receiptReport(int $circularId): Collection
    {
        return $this->repository->receiptReportsForCircular($circularId);
    }

    public function departmentSummary(int $circularId): Collection
    {
        return $this->repository->departmentRecipientSummary($circularId);
    }

    public function authorityOptions(): Collection
    {
        return $this->repository->authorityOptions();
    }

    public function classificationOptions(?int $authorityId = null): Collection
    {
        return $this->repository->classificationOptions($authorityId);
    }

    public function sectionOptions(): Collection
    {
        return $this->repository->sectionOptions();
    }

    public function receivingMechanismOptions(): Collection
    {
        return $this->repository->receivingMechanismOptions();
    }

    public function notificationTypeOptions(): Collection
    {
        return $this->repository->notificationTypeOptions();
    }

    public function statusOptions(): Collection
    {
        return $this->repository->statusOptions();
    }

    public function updatableStatusOptions(?int $currentStatusId = null): Collection
    {
        return $this->statusOptions()
            ->when(
                $currentStatusId !== null && $currentStatusId > 0,
                fn (Collection $statuses) => $statuses->reject(
                    fn ($status) => (int) $status->id === $currentStatusId
                )->values()
            );
    }

    /**
     * @param  array{status_id:int,details?:string}  $payload
     */
    public function updateStatus(
        GovernmentCircular $circular,
        array $payload,
        ?UploadedFile $attachment = null,
    ): GovernmentCircular {
        $statusId = (int) $payload['status_id'];
        $allowedIds = $this->statusOptions()->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (! in_array($statusId, $allowedIds, true)) {
            throw new InvalidArgumentException(__('government_circulars.validation.status_invalid'));
        }

        $path = null;

        if ($attachment !== null) {
            $path = $attachment->store('government-circulars/'.date('Y/m'), 'public');
            $this->repository->addAttachment((int) $circular->id, $path);
        }

        $classificationId = (int) $circular->government_circulars_issuing_authority_classification_id;
        $needsAttachment = $statusId === UpdateGovernmentCircularStatusRequest::STATUS_NEW
            && $classificationId !== UpdateGovernmentCircularStatusRequest::CLASSIFICATION_EXEMPT_FROM_ATTACHMENT;

        if ($needsAttachment && $path === null && ! $this->hasAnyAttachment($circular)) {
            throw new InvalidArgumentException(__('government_circulars.validation.attachment_required_for_status'));
        }

        // issue_date has ON UPDATE CURRENT_TIMESTAMP — include it so status changes keep the original date.
        $attributes = [
            'status' => $statusId,
            'issue_date' => $circular->getRawOriginal('issue_date')
                ?? optional($circular->issue_date)?->format('Y-m-d H:i:s'),
        ];

        if ($path !== null && ! filled($circular->circulars_file)) {
            $attributes['circulars_file'] = $path;
            $attributes['attachment_type'] = $attachment?->getClientOriginalExtension();
        }

        $this->repository->updateStatus((int) $circular->id, $attributes);

        return $this->repository->findForDetail((int) $circular->id) ?? $circular->fresh(['currentStatus']) ?? $circular;
    }

    public function hasAnyAttachment(GovernmentCircular $circular): bool
    {
        if (filled($circular->circulars_file)) {
            return true;
        }

        if ($circular->relationLoaded('attachments')) {
            return $circular->attachments->isNotEmpty();
        }

        return $circular->attachments()->exists();
    }

    public function branchOptions(): Collection
    {
        return $this->repository->branchOptions();
    }

    public function statusLabel(?GovernmentCircular $circular): string
    {
        return $circular?->currentStatus?->localizedName() ?: __('government_circulars.status_unknown');
    }

    public function statusColor(?GovernmentCircular $circular): string
    {
        return $circular?->currentStatus?->badgeColor() ?: '#64748b';
    }

    public function attachmentUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        $normalized = ltrim(str_replace('\\', '/', $path), './');

        if (Storage::disk('public')->exists($normalized)) {
            return Storage::disk('public')->url($normalized);
        }

        if (is_file(public_path($normalized))) {
            return asset($normalized);
        }

        return asset(ltrim($normalized, '/'));
    }

    public const CHANNEL_SMS = 1;

    public const CHANNEL_EMAIL = 2;

    /**
     * @return array{0:GovernmentCircular,1:DepartmentReplyToken,2:list<string>,3:list<string>}
     */
    public function openFormalPage(string $rawToken): array
    {
        $parsed = DepartmentReplyToken::parse($rawToken);
        $circular = $this->repository->findBySmsToken($parsed->token);

        if ($circular === null) {
            throw new InvalidArgumentException(__('government_circulars.formal.invalid_link'));
        }

        $this->markReceiptSeen(
            (int) $circular->id,
            $parsed->administratorId,
            $parsed->channel ?? self::CHANNEL_EMAIL,
        );

        $attachmentUrls = [];

        if (filled($circular->circulars_file)) {
            $url = $this->attachmentUrl($circular->circulars_file);
            if ($url !== null) {
                $attachmentUrls[] = $url;
            }
        }

        foreach ($circular->attachments as $attachment) {
            $url = $this->attachmentUrl($attachment->circulars_file ?? null);
            if ($url !== null && ! in_array($url, $attachmentUrls, true)) {
                $attachmentUrls[] = $url;
            }
        }

        $sectionNames = GovernmentCircularSection::query()
            ->whereIn('id', $circular->sectionIds())
            ->orderBy('id')
            ->get(['id', 'name_en', 'name_ar'])
            ->map(fn ($section) => $section->localizedName())
            ->filter()
            ->values()
            ->all();

        return [$circular, $parsed, $attachmentUrls, $sectionNames];
    }

    public function markReceiptSeen(int $circularId, int $administratorId, int $channel): void
    {
        if ($administratorId < 1) {
            return;
        }

        if ($channel === self::CHANNEL_SMS) {
            $this->repository->markSmsSeen($circularId, $administratorId);

            return;
        }

        $this->repository->markEmailSeen($circularId, $administratorId);
    }

    public function formalPageUrl(
        GovernmentCircular $circular,
        int $administratorId = 1,
        int $channel = self::CHANNEL_EMAIL,
    ): string {
        $raw = DepartmentReplyToken::build(
            (string) $circular->sms_tocken,
            $administratorId,
            1,
            $channel,
        );

        return route('public.government-circulars.formal.show', ['token' => $raw]);
    }
}
