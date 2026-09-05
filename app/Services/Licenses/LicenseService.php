<?php

namespace App\Services\Licenses;

use App\Models\License;
use App\Models\CompanyGroup;
use App\Models\LicenseAttachment;
use App\Models\LicenseComment;
use App\Models\LicenseRenewal;
use App\Models\LicenseUndertaking;
use App\Models\User;
use App\Repositories\Licenses\LicenseRepository;
use App\Services\Auth\PermissionService;
use App\Support\Licenses\LicensePermissions;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LicenseService
{
    public function __construct(
        private readonly LicenseRepository $repository,
        private readonly LicenseNotificationService $notifications,
        private readonly PermissionService $permissions,
    ) {}

    /** @param array<string,mixed> $filters */
    public function listPaginated(array $filters, ?int $perPage = null): LengthAwarePaginator
    {
        return $this->repository->paginateFiltered($filters, $perPage ?? (int) config('hm.licenses.per_page', 15));
    }

    public function findForDetail(int $id, bool $financeContext = false): ?License
    {
        return $this->repository->findForDetail($id, $financeContext);
    }

    public function findOrFail(int $id, bool $financeContext = false): License
    {
        return $this->repository->findOrFailForDetail($id, $financeContext);
    }

    /** @param array<string,mixed> $payload @param list<UploadedFile> $attachments */
    public function store(array $payload, array $attachments = []): License
    {
        $this->assertPayloadScope($payload);
        $departmentIds = array_values(array_unique(array_map('intval', (array) ($payload['department_ids'] ?? $payload['branch_ids'] ?? []))));
        unset($payload['department_ids'], $payload['branch_ids']);

        $license = DB::transaction(function () use ($payload, $departmentIds, $attachments): License {
            $license = $this->repository->create($payload + [
                'companies_groups_id' => (int) session('companies_groups_id', 0),
                'status_id' => $this->repository->statusId('active'),
                'renewal_stage_id' => $payload['renewal_stage_id'] ?? $this->repository->stageId('not_started'),
                'publish' => true,
                'created_by' => (int) session('hr_user_id', 0),
            ]);
            $this->repository->syncDepartments($license, $departmentIds);
            $this->createPendingUndertaking($license, (int) $license->responsible_user_id);
            $this->recordTimeline($license, 'created', __('licenses.timeline.created'));
            $this->recordTimeline($license, 'assigned', __('licenses.timeline.assigned'), [
                'responsible_user_id' => (int) $license->responsible_user_id,
            ]);

            foreach ($attachments as $file) {
                $this->storeAttachment($license, $file, null, 'license', null, false);
            }

            return $license;
        });

        $this->notifyAssignment($license->fresh(['responsibleUser']) ?? $license);

        return $this->repository->findOrFailForDetail((int) $license->getKey());
    }

    /** @param array<string,mixed> $payload */
    public function update(License $license, array $payload): License
    {
        $this->assertAdministrator();
        $this->assertPayloadScope($payload, (int) $license->companies_groups_id);
        $departmentIds = array_values(array_unique(array_map('intval', (array) ($payload['department_ids'] ?? $payload['branch_ids'] ?? []))));
        unset($payload['department_ids'], $payload['branch_ids']);
        $oldResponsible = (int) $license->responsible_user_id;
        $oldExpiry = $this->dateString($license->expiry_date);

        DB::transaction(function () use ($license, $payload, $departmentIds, $oldResponsible, $oldExpiry): void {
            $this->repository->update($license, $payload);
            $this->repository->syncDepartments($license, $departmentIds);
            $this->recordTimeline($license, 'updated', __('licenses.timeline.updated'), ['changes' => $license->getChanges()]);

            if ($oldResponsible !== (int) $license->responsible_user_id) {
                $this->createPendingUndertaking($license, (int) $license->responsible_user_id);
                $this->recordTimeline($license, 'assigned', __('licenses.timeline.reassigned'), [
                    'old_responsible_user_id' => $oldResponsible,
                    'responsible_user_id' => (int) $license->responsible_user_id,
                ]);
            }
            if ($oldExpiry !== $this->dateString($license->expiry_date)) {
                $this->recordTimeline($license, 'expiry_updated', __('licenses.timeline.expiry_updated'), [
                    'old_expiry_date' => $oldExpiry,
                    'new_expiry_date' => $this->dateString($license->expiry_date),
                ]);
            }
        });

        if ($oldResponsible !== (int) $license->responsible_user_id) {
            $this->notifyAssignment($license->fresh(['responsibleUser']) ?? $license);
        }

        return $this->repository->findOrFailForDetail((int) $license->getKey());
    }

    public function assign(License $license, int $responsibleUserId): License
    {
        $this->assertAdministrator();
        if (! User::query()->whereKey($responsibleUserId)
            ->where('companies_groups_id', (int) $license->companies_groups_id)->exists()) {
            throw ValidationException::withMessages(['responsible_user_id' => __('licenses.validation.invalid_responsible')]);
        }
        if ((int) $license->responsible_user_id === $responsibleUserId) {
            throw ValidationException::withMessages(['responsible_user_id' => __('licenses.validation.responsible_unchanged')]);
        }

        $old = (int) $license->responsible_user_id;
        DB::transaction(function () use ($license, $responsibleUserId, $old): void {
            $license->update(['responsible_user_id' => $responsibleUserId]);
            $this->createPendingUndertaking($license, $responsibleUserId);
            $this->recordTimeline($license, 'assigned', __('licenses.timeline.reassigned'), [
                'old_responsible_user_id' => $old, 'responsible_user_id' => $responsibleUserId,
            ]);
        });
        $this->notifyAssignment($license->fresh(['responsibleUser']) ?? $license);

        return $this->repository->findOrFailForDetail((int) $license->getKey());
    }

    public function requiresUndertaking(License $license, ?int $userId = null): bool
    {
        $userId ??= (int) session('hr_user_id', 0);

        return $userId > 0
            && (int) $license->responsible_user_id === $userId
            && $this->repository->pendingUndertaking($license, $userId) !== null;
    }

    public function pendingUndertaking(License $license, ?int $userId = null): ?LicenseUndertaking
    {
        return $this->repository->pendingUndertaking($license, $userId ?? (int) session('hr_user_id', 0));
    }

    public function acceptUndertaking(License $license, string $ip, ?string $userAgent): LicenseUndertaking
    {
        $userId = (int) session('hr_user_id', 0);
        if ((int) $license->responsible_user_id !== $userId) {
            abort(403);
        }

        $undertaking = $this->repository->pendingUndertaking($license, $userId);
        if ($undertaking === null) {
            throw ValidationException::withMessages(['accept_undertaking' => __('licenses.validation.no_pending_undertaking')]);
        }

        DB::transaction(function () use ($undertaking, $license, $ip, $userAgent): void {
            $undertaking->update([
                'status' => 'accepted', 'accepted_at' => now(), 'ip' => $ip, 'user_agent' => $userAgent,
            ]);
            $this->recordTimeline($license, 'undertaking_accepted', __('licenses.timeline.undertaking_accepted'), [
                'undertaking_id' => (int) $undertaking->getKey(),
            ]);
        });

        return $undertaking->fresh() ?? $undertaking;
    }

    public function rejectUndertaking(License $license, ?string $reason, string $ip, ?string $userAgent): LicenseUndertaking
    {
        $userId = (int) session('hr_user_id', 0);
        if ((int) $license->responsible_user_id !== $userId) {
            abort(403);
        }

        $undertaking = $this->repository->pendingUndertaking($license, $userId);
        if ($undertaking === null) {
            throw ValidationException::withMessages(['undertaking' => __('licenses.validation.no_pending_undertaking')]);
        }

        DB::transaction(function () use ($undertaking, $license, $reason, $ip, $userAgent): void {
            $undertaking->update([
                'status' => LicenseUndertaking::STATUS_REJECTED,
                'ip' => $ip,
                'user_agent' => $userAgent,
            ]);
            $this->recordTimeline($license, 'undertaking_rejected', __('licenses.timeline.undertaking_rejected'), array_filter([
                'undertaking_id' => (int) $undertaking->getKey(),
                'rejection_reason' => $reason,
            ]));
        });

        $fresh = $undertaking->fresh() ?? $undertaking;
        $this->notifyUndertakingRejected($license->fresh(['creator', 'responsibleUser']) ?? $license, $reason);

        return $fresh;
    }

    public function hasRejectedUndertaking(License $license, ?int $userId = null): bool
    {
        $userId ??= (int) session('hr_user_id', 0);
        if ($userId <= 0 || (int) $license->responsible_user_id !== $userId) {
            return false;
        }

        return $this->repository->latestUndertakingForUser($license, $userId)?->status === LicenseUndertaking::STATUS_REJECTED;
    }

    public function updateStage(License $license, int $stageId): License
    {
        $this->assertCanProcess($license);
        if (! $this->repository->stageOptions()->contains('id', $stageId)) {
            throw ValidationException::withMessages(['renewal_stage_id' => __('licenses.validation.invalid_reference')]);
        }
        $old = $license->renewal_stage_id !== null ? (int) $license->renewal_stage_id : null;
        if ($old === $stageId) {
            throw ValidationException::withMessages(['renewal_stage_id' => __('licenses.validation.stage_unchanged')]);
        }

        DB::transaction(function () use ($license, $stageId, $old): void {
            $license->update(['renewal_stage_id' => $stageId]);
            $this->recordTimeline($license, 'stage_changed', __('licenses.timeline.stage_changed'), [
                'old_stage_id' => $old, 'new_stage_id' => $stageId,
            ]);
        });

        return $license->fresh(['renewalStage']) ?? $license;
    }

    public function addComment(License $license, string $body): LicenseComment
    {
        $this->assertCanProcess($license);

        return DB::transaction(function () use ($license, $body): LicenseComment {
            $comment = $this->repository->createComment([
                'license_id' => $license->getKey(), 'user_id' => (int) session('hr_user_id', 0),
                'body' => $body, 'publish' => true, 'created_at' => now(),
            ]);
            $this->recordTimeline($license, 'comment_added', __('licenses.timeline.comment_added'), ['comment_id' => $comment->getKey()]);

            return $comment;
        });
    }

    public function storeAttachment(
        License $license,
        UploadedFile $file,
        ?string $description = null,
        string $context = 'license',
        ?int $paymentRequestId = null,
        bool $enforceAccess = true,
    ): LicenseAttachment {
        if ($enforceAccess) {
            $this->assertCanProcess($license);
        }

        $renewal = $context === 'renewal' ? $this->repository->openRenewal($license) : null;
        $path = $file->store('licenses/'.$license->getKey().'/'.now()->format('Y/m'), 'local');

        try {
            $attachment = $this->repository->createAttachment([
                'license_id' => $license->getKey(),
                'renewal_id' => $renewal?->getKey(),
                'payment_request_id' => $paymentRequestId,
                'context' => $context,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize(),
                'description' => $description,
                'uploaded_by' => (int) session('hr_user_id', 0),
                'uploaded_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        $this->recordTimeline($license, $context === 'payment_proof' ? 'proof_uploaded' : 'attachment_uploaded', __('licenses.timeline.attachment_uploaded'), [
            'attachment_id' => $attachment->getKey(), 'context' => $context,
        ]);

        return $attachment;
    }

    public function downloadAttachment(License|int $license, int $attachmentId): StreamedResponse|BinaryFileResponse
    {
        $licenseId = $license instanceof License ? (int) $license->getKey() : $license;
        $attachment = LicenseAttachment::query()
            ->whereKey($attachmentId)
            ->where('license_id', $licenseId)
            ->first();
        abort_if($attachment === null, 404);

        $record = License::query()
            ->whereKey($licenseId)
            ->where('companies_groups_id', (int) session('companies_groups_id', 0))
            ->where('publish', true)
            ->first();
        abort_if($record === null, 404);
        abort_unless($this->canDownloadAttachment($record, $attachment), 403);

        return $this->streamAttachmentDownload($attachment);
    }

    /** @param array<string,mixed> $payload */
    public function logExternalCommunication(License $license, array $payload, ?UploadedFile $attachment = null): void
    {
        $this->assertCanProcess($license);
        DB::transaction(function () use ($license, $payload, $attachment): void {
            $timeline = $this->recordTimeline($license, 'external_communication', (string) $payload['description'], $payload);
            if ($attachment !== null) {
                $this->storeAttachment($license, $attachment, (string) $payload['description'], 'external', null, false);
            }
        });
    }

    public function startRenewal(License $license, ?string $notes = null): LicenseRenewal
    {
        $this->assertCanProcess($license);
        if ($this->repository->openRenewal($license) !== null) {
            throw ValidationException::withMessages(['renewal' => __('licenses.validation.renewal_already_open')]);
        }

        return DB::transaction(function () use ($license, $notes): LicenseRenewal {
            $renewal = $this->repository->createRenewal([
                'license_id' => $license->getKey(), 'previous_expiry_date' => $this->dateString($license->expiry_date),
                'started_at' => now(), 'notes' => $notes,
            ]);
            $license->update([
                'status_id' => $this->repository->statusId('under_renewal'),
                'renewal_stage_id' => $this->repository->stageId('preparing'),
            ]);
            $this->recordTimeline($license, 'renewal_started', __('licenses.timeline.renewal_started'), ['renewal_id' => $renewal->getKey()]);

            return $renewal;
        });
    }

    public function completeRenewal(License $license, string $newExpiryDate, ?string $notes = null, ?UploadedFile $copy = null): License
    {
        $this->assertCanProcess($license);
        $renewal = $this->repository->openRenewal($license);
        if ($renewal === null) {
            throw ValidationException::withMessages(['renewal' => __('licenses.validation.no_open_renewal')]);
        }
        $oldExpiry = $this->dateString($license->expiry_date);
        if (CarbonImmutable::parse($newExpiryDate)->lessThanOrEqualTo(CarbonImmutable::parse($oldExpiry))) {
            throw ValidationException::withMessages(['new_expiry_date' => __('licenses.validation.new_expiry_after_old')]);
        }

        DB::transaction(function () use ($license, $renewal, $newExpiryDate, $notes, $copy, $oldExpiry): void {
            if ($copy !== null) {
                // Store while the cycle is still open so the attachment keeps its renewal_id.
                $this->storeAttachment($license, $copy, __('licenses.attachments.renewed_copy'), 'renewal', null, false);
            }
            $renewal->update([
                'new_expiry_date' => $newExpiryDate, 'completed_at' => now(),
                'completed_by' => (int) session('hr_user_id', 0), 'notes' => $notes ?? $renewal->notes,
            ]);
            $license->update([
                'expiry_date' => $newExpiryDate,
                'status_id' => $this->repository->statusId('active'),
                'renewal_stage_id' => $this->repository->stageId('completed'),
            ]);
            $this->recordTimeline($license, 'renewal_completed', __('licenses.timeline.renewal_completed'), [
                'renewal_id' => $renewal->getKey(), 'old_expiry_date' => $oldExpiry, 'new_expiry_date' => $newExpiryDate,
            ]);
            $this->recordTimeline($license, 'expiry_updated', __('licenses.timeline.expiry_updated'), [
                'old_expiry_date' => $oldExpiry, 'new_expiry_date' => $newExpiryDate,
            ]);
        });

        $fresh = $license->fresh(['responsibleUser']) ?? $license;
        if ($fresh->responsibleUser !== null) {
            $this->notifications->notifyUser($fresh, $fresh->responsibleUser, 'renewal_completed', __('licenses.notifications.renewal_completed_subject'), __('licenses.notifications.renewal_completed_body'), null, [
                'old_expiry_date' => $oldExpiry, 'new_expiry_date' => $newExpiryDate,
            ]);
        }

        return $this->repository->findOrFailForDetail((int) $license->getKey());
    }

    public function options(): array
    {
        return [
            'hospitalBranch' => CompanyGroup::query()->find((int) session('companies_groups_id', 0)),
            'authorities' => $this->repository->authorityOptions(),
            'types' => $this->repository->typeOptions(),
            'statuses' => $this->repository->statusOptions(),
            'stages' => $this->repository->stageOptions(),
            'departments' => $this->repository->departmentOptions(),
            'branches' => $this->repository->departmentOptions(),
            'responsibleUsers' => $this->repository->responsibleUserOptions(),
        ];
    }

    public function assertCanProcess(License $license): void
    {
        if (! LicensePermissions::isAdministrator($this->permissions)) {
            abort_unless(LicensePermissions::canProcess($this->permissions)
                && (int) $license->responsible_user_id === (int) session('hr_user_id', 0), 403);
        }
        if ($this->requiresUndertaking($license)) {
            throw ValidationException::withMessages(['undertaking' => __('licenses.validation.undertaking_required')]);
        }
        if ($this->hasRejectedUndertaking($license)) {
            throw ValidationException::withMessages(['undertaking' => __('licenses.validation.undertaking_rejected')]);
        }
    }

    private function canDownloadAttachment(License $license, LicenseAttachment $attachment): bool
    {
        $userId = (int) session('hr_user_id', 0);
        if (LicensePermissions::isAdministrator($this->permissions)) {
            return true;
        }
        if (LicensePermissions::isFinance($this->permissions)
            && in_array($attachment->context, [LicenseAttachment::CONTEXT_PAYMENT, LicenseAttachment::CONTEXT_PAYMENT_PROOF], true)) {
            return true;
        }
        if ($userId > 0 && ((int) $license->responsible_user_id === $userId || (int) $license->created_by === $userId)) {
            return true;
        }

        return $this->findForDetail((int) $license->getKey()) !== null;
    }

    private function streamAttachmentDownload(LicenseAttachment $attachment): StreamedResponse|BinaryFileResponse
    {
        $path = $this->normalizeAttachmentPath((string) $attachment->file_path);
        abort_if($path === null, 404);

        $filename = $this->attachmentDownloadName($attachment);
        $asciiName = $this->asciiAttachmentDownloadName($filename);
        $headers = [
            'Content-Type' => $attachment->mime ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => (new ResponseHeaderBag)->makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $filename,
                $asciiName,
            ),
        ];

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->download($path, $asciiName, $headers);
        }

        $legacyPath = storage_path('app/'.$path);
        abort_unless(is_file($legacyPath), 404);

        return response()->download($legacyPath, $asciiName, $headers);
    }

    private function attachmentDownloadName(LicenseAttachment $attachment): string
    {
        $name = str_replace(["\r", "\n", '/', '\\'], '', trim((string) $attachment->original_name));

        return $name !== '' ? $name : 'attachment-'.$attachment->getKey();
    }

    private function asciiAttachmentDownloadName(string $filename): string
    {
        $ascii = preg_replace('/[^\x20-\x7E]/', '-', $filename) ?: '';
        $ascii = trim($ascii, '.- ');

        return $ascii !== '' ? $ascii : 'attachment.bin';
    }

    private function normalizeAttachmentPath(string $filePath): ?string
    {
        $path = str_replace('\\', '/', trim($filePath));
        if ($path === '' || str_starts_with($path, '/') || in_array('..', explode('/', $path), true)) {
            return null;
        }

        return $path;
    }

    private function notifyUndertakingRejected(License $license, ?string $reason): void
    {
        $creator = $license->creator;
        if ($creator === null) {
            return;
        }

        $this->notifications->notifyUser(
            $license,
            $creator,
            'undertaking_rejected',
            __('licenses.notifications.undertaking_rejected_subject'),
            __('licenses.notifications.undertaking_rejected_body'),
            null,
            array_filter(['rejection_reason' => $reason]),
        );
    }

    private function assertAdministrator(): void
    {
        abort_unless(LicensePermissions::isAdministrator($this->permissions), 403);
    }

    /** @param array<string,mixed> $payload */
    private function assertPayloadScope(array $payload, ?int $companyId = null): void
    {
        $companyId ??= (int) session('companies_groups_id', 0);
        $departmentIds = array_values(array_unique(array_map('intval', (array) ($payload['department_ids'] ?? $payload['branch_ids'] ?? []))));
        if ($departmentIds === [] || DB::table('branches')->whereIn('id', $departmentIds)
            ->where('companies_groups_id', $companyId)->count() !== count($departmentIds)) {
            throw ValidationException::withMessages(['department_ids' => __('licenses.validation.invalid_department')]);
        }
        if (! DB::table('license_authorities')->where('id', (int) ($payload['license_authority_id'] ?? 0))
            ->where('companies_groups_id', $companyId)->exists()
            || ! DB::table('license_types')->where('id', (int) ($payload['license_type_id'] ?? 0))
                ->where('companies_groups_id', $companyId)->exists()) {
            throw ValidationException::withMessages(['authority_id' => __('licenses.validation.invalid_reference')]);
        }
        if (! User::query()->whereKey((int) ($payload['responsible_user_id'] ?? 0))
            ->where('companies_groups_id', $companyId)->exists()) {
            throw ValidationException::withMessages(['responsible_user_id' => __('licenses.validation.invalid_responsible')]);
        }
    }

    private function createPendingUndertaking(License $license, int $userId): LicenseUndertaking
    {
        return $this->repository->createUndertaking([
            'license_id' => $license->getKey(), 'user_id' => $userId,
            'undertaking_text' => (string) config('hm.licenses.undertaking_text', 'أتعهد بمتابعة هذا الترخيص وتجديده قبل انتهائه بمدة كافية، واتخاذ الإجراءات اللازمة في وقتها، وتحمل كامل المسؤولية المترتبة على أي تقصير يؤدي إلى انتهاء الترخيص أو تعطل الخدمة أو مخالفة تنظيمية.'),
            'status' => 'pending', 'requested_at' => now(),
        ]);
    }

    private function notifyAssignment(License $license): void
    {
        $user = $license->responsibleUser;
        if ($user !== null) {
            $this->notifications->notifyUser($license, $user, 'undertaking_requested', __('licenses.notifications.assignment_subject'), __('licenses.notifications.assignment_body'));
        }
    }

    /** @param array<string,mixed> $meta */
    private function recordTimeline(License $license, string $eventType, ?string $notice = null, array $meta = []): mixed
    {
        $branchId = $license->relationLoaded('departments')
            ? $license->departments->first()?->getKey()
            : DB::table('license_branches')->where('license_id', $license->getKey())->value('branch_id');

        return $this->repository->timeline([
            'license_id' => $license->getKey(), 'event_type' => $eventType,
            'status_id' => $license->status_id, 'notice' => $notice, 'meta' => $meta ?: null,
            'branch_id' => $branchId,
        ]);
    }

    private function dateString(mixed $date): string
    {
        return $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : CarbonImmutable::parse((string) $date)->toDateString();
    }
}
