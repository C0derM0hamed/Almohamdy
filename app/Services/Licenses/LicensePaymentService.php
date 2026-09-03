<?php

namespace App\Services\Licenses;

use App\Models\License;
use App\Models\LicenseAttachment;
use App\Models\LicensePaymentEvent;
use App\Models\LicensePaymentRequest;
use App\Repositories\Licenses\LicensePaymentRepository;
use App\Repositories\Licenses\LicenseRepository;
use App\Services\Auth\PermissionService;
use App\Support\Licenses\LicensePermissions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LicensePaymentService
{
    private const TRANSITIONS = [
        'received' => ['in_progress', 'needs_documents'],
        'in_progress' => ['needs_documents', 'paid'],
        'needs_documents' => ['received', 'in_progress'],
        'paid' => [],
    ];

    public function __construct(
        private readonly LicensePaymentRepository $repository,
        private readonly LicenseRepository $licenses,
        private readonly LicenseService $licenseService,
        private readonly LicenseNotificationService $notifications,
        private readonly PermissionService $permissions,
    ) {}

    /** @param array<string,mixed> $filters */
    public function listPaginated(array $filters = [], ?int $perPage = null): LengthAwarePaginator
    {
        $this->assertFinance();

        return $this->repository->paginate($filters, $perPage ?? (int) config('hm.licenses.finance_per_page', 20));
    }

    public function findOrFail(int $id): LicensePaymentRequest
    {
        $this->assertFinance();

        return $this->repository->findOrFail($id);
    }

    /** @param array<string,mixed> $payload @param list<UploadedFile> $attachments */
    public function create(License $license, array $payload, array $attachments = []): LicensePaymentRequest
    {
        $this->licenseService->assertCanProcess($license);
        $renewal = $this->licenses->openRenewal($license);

        $payment = DB::transaction(function () use ($license, $renewal, $payload, $attachments): LicensePaymentRequest {
            $payment = $this->repository->create($payload + [
                'license_id' => $license->getKey(), 'renewal_id' => $renewal?->getKey(),
                'status_id' => $this->repository->statusId('received'),
                'requested_by' => (int) session('hr_user_id', 0), 'closed_at' => null,
            ]);
            $this->repository->event([
                'payment_request_id' => $payment->getKey(), 'status_id' => $payment->status_id,
                'event_type' => 'created', 'comment' => $payload['notes'] ?? null,
            ]);
            $this->licenses->timeline([
                'license_id' => $license->getKey(), 'event_type' => 'payment_created',
                'status_id' => $license->status_id, 'notice' => __('licenses.timeline.payment_created'),
                'meta' => ['payment_request_id' => $payment->getKey(), 'amount' => $payment->amount, 'currency' => $payment->currency],
            ]);
            foreach ($attachments as $file) {
                $this->licenseService->storeAttachment($license, $file, null, 'payment', (int) $payment->getKey(), false);
            }

            return $payment;
        });

        $recipients = $this->notifications->financeRecipients((int) $license->companies_groups_id);
        $this->notifications->notifyUsers($license, $recipients, 'payment_created', __('licenses.notifications.payment_created_subject'), __('licenses.notifications.payment_created_body'), $payment);

        return $this->repository->findOrFail((int) $payment->getKey());
    }

    public function updateStatus(
        LicensePaymentRequest $payment,
        string $statusCode,
        ?string $comment = null,
        ?UploadedFile $proof = null,
    ): LicensePaymentRequest {
        $this->assertFinance();
        $payment = $this->repository->findOrFail((int) $payment->getKey());
        $current = (string) ($payment->status?->code ?: $this->repository->statusCode((int) $payment->status_id));

        if (! in_array($statusCode, self::TRANSITIONS[$current] ?? [], true)) {
            throw ValidationException::withMessages(['status' => __('licenses.validation.invalid_payment_transition')]);
        }
        if ($statusCode === 'paid' && $proof === null) {
            throw ValidationException::withMessages(['proof' => __('licenses.validation.payment_proof_required')]);
        }

        $statusId = $this->repository->statusId($statusCode);
        DB::transaction(function () use ($payment, $statusCode, $statusId, $comment, $proof): void {
            $payment->update(['status_id' => $statusId, 'closed_at' => $statusCode === 'paid' ? now() : null]);
            $this->repository->event([
                'payment_request_id' => $payment->getKey(), 'status_id' => $statusId,
                'event_type' => 'status_changed', 'comment' => $comment,
            ]);
            if ($proof !== null) {
                $this->licenseService->storeAttachment($payment->license, $proof, $comment, 'payment_proof', (int) $payment->getKey(), false);
                $this->repository->event([
                    'payment_request_id' => $payment->getKey(), 'status_id' => $statusId,
                    'event_type' => 'proof_uploaded', 'comment' => $comment,
                ]);
            }
            $this->licenses->timeline([
                'license_id' => $payment->license_id, 'event_type' => 'payment_status_changed',
                'status_id' => $payment->license->status_id, 'notice' => $comment ?: __('licenses.timeline.payment_status_changed'),
                'meta' => ['payment_request_id' => $payment->getKey(), 'payment_status' => $statusCode],
            ]);
        });

        $fresh = $this->repository->findOrFail((int) $payment->getKey());
        if ($fresh->license?->responsibleUser !== null) {
            $this->notifications->notifyUser($fresh->license, $fresh->license->responsibleUser, 'payment_status_changed', __('licenses.notifications.payment_status_subject'), __('licenses.notifications.payment_status_body', ['status' => $fresh->status?->localizedName() ?? $statusCode]), $fresh, ['payment_status' => $statusCode]);
        }

        return $fresh;
    }

    public function requestDocuments(LicensePaymentRequest $payment, string $comment): LicensePaymentRequest
    {
        $this->assertFinance();
        $payment = $this->repository->findOrFail((int) $payment->getKey());
        if ((string) $payment->status?->code === 'paid') {
            throw ValidationException::withMessages(['status' => __('licenses.validation.paid_request_closed')]);
        }
        $statusId = $this->repository->statusId('needs_documents');

        DB::transaction(function () use ($payment, $comment, $statusId): void {
            $payment->update(['status_id' => $statusId, 'closed_at' => null]);
            $this->repository->event(['payment_request_id' => $payment->getKey(), 'status_id' => $statusId, 'event_type' => 'docs_requested', 'comment' => $comment]);
            $this->licenses->timeline([
                'license_id' => $payment->license_id, 'event_type' => 'docs_requested',
                'status_id' => $payment->license->status_id, 'notice' => $comment,
                'meta' => ['payment_request_id' => $payment->getKey()],
            ]);
        });

        $fresh = $this->repository->findOrFail((int) $payment->getKey());
        if ($fresh->license?->responsibleUser !== null) {
            $this->notifications->notifyUser($fresh->license, $fresh->license->responsibleUser, 'documents_requested', __('licenses.notifications.documents_requested_subject'), $comment, $fresh);
        }

        return $fresh;
    }

    public function addComment(LicensePaymentRequest $payment, string $comment): LicensePaymentEvent
    {
        $this->assertFinance();
        $payment = $this->repository->findOrFail((int) $payment->getKey());
        $event = $this->repository->event([
            'payment_request_id' => $payment->getKey(), 'status_id' => $payment->status_id,
            'event_type' => 'comment', 'comment' => $comment,
        ]);
        if ($payment->license?->responsibleUser !== null) {
            $this->notifications->notifyUser($payment->license, $payment->license->responsibleUser, 'payment_comment', __('licenses.notifications.payment_comment_subject'), $comment, $payment);
        }

        return $event;
    }

    public function addAttachment(LicensePaymentRequest $payment, UploadedFile $file, ?string $comment = null): LicenseAttachment
    {
        $this->assertFinance();
        $payment = $this->repository->findOrFail((int) $payment->getKey());
        $attachment = $this->licenseService->storeAttachment($payment->license, $file, $comment, 'payment', (int) $payment->getKey(), false);
        $this->repository->event([
            'payment_request_id' => $payment->getKey(), 'status_id' => $payment->status_id,
            'event_type' => 'attachment_uploaded', 'comment' => $comment,
        ]);
        if ($payment->license?->responsibleUser !== null) {
            $this->notifications->notifyUser($payment->license, $payment->license->responsibleUser, 'payment_document_uploaded', __('licenses.notifications.payment_document_subject'), $comment ?: __('licenses.notifications.payment_document_body'), $payment);
        }

        return $attachment;
    }

    public function statusOptions(): mixed
    {
        return $this->repository->statusOptions();
    }

    /** @return array<string,int> */
    public function statusCounters(): array
    {
        $this->assertFinance();

        return $this->repository->statusCounters();
    }

    private function assertFinance(): void
    {
        abort_unless(LicensePermissions::isFinance($this->permissions), 403);
    }
}
