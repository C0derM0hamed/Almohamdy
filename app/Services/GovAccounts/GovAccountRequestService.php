<?php

namespace App\Services\GovAccounts;

use App\Models\BranchDepartment;
use App\Models\GovAccount;
use App\Models\GovAccountAttachment;
use App\Models\GovAccountAuthority;
use App\Models\GovAccountDepartmentHead;
use App\Models\GovAccountRequest;
use App\Models\GovAccountRole;
use App\Models\GovAccountService;
use App\Models\GovAccountUndertaking;
use App\Models\User;
use App\Repositories\GovAccounts\GovAccountRepository;
use App\Services\Auth\PermissionService;
use App\Support\GovAccounts\GovAccountPermissions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GovAccountRequestService
{
    public const CREATE_TRANSITIONS = [
        'draft' => ['awaiting_employee', 'cancelled'],
        'awaiting_employee' => ['under_review', 'cancelled'],
        'under_review' => ['approved', 'rejected', 'cancelled'],
        'rejected' => ['under_review', 'cancelled'],
        'approved' => ['submitted_to_authority'],
        'submitted_to_authority' => ['completed'],
    ];

    public function __construct(
        private readonly GovAccountRepository $repository,
        private readonly PermissionService $permissions,
        private readonly GovAccountNotificationService $notifications,
    ) {}

    public function create(array $payload): GovAccountRequest
    {
        $this->assertCanRequest((int) $payload['department_id']);
        $this->assertReferences($payload);
        $parentDepartmentId = $this->resolveParentDepartmentId((int) $payload['department_id']);

        return DB::transaction(function () use ($payload, $parentDepartmentId): GovAccountRequest {
            $request = GovAccountRequest::query()->create($payload + [
                'companies_groups_id' => $this->companyId(), 'branch_id' => $parentDepartmentId,
                'type' => 'create', 'status' => 'draft', 'origin' => 'department', 'round' => 1,
                'created_by' => $this->userId(),
            ]);
            $this->timeline($request, 'created', __('gov_accounts.timeline.created'));

            return $request;
        });
    }

    public function createLifecycle(GovAccount $account, array $payload, string $origin = 'department'): GovAccountRequest
    {
        $type = (string) $payload['type'];
        abort_unless(in_array($type, ['modify', 'permission_change', 'suspend', 'close'], true), 422);
        $source = GovAccountRequest::query()->findOrFail($account->created_from_request_id);
        if ($origin === 'hr') {
            abort_unless(in_array($type, ['suspend', 'close'], true), 403);
            abort_unless(GovAccountPermissions::isAdministrator($this->permissions) || $this->permissions->can(GovAccountPermissions::HR), 403);
        } elseif (! GovAccountPermissions::isAdministrator($this->permissions) && ! $this->permissions->can(GovAccountPermissions::PROCESS)) {
            $this->assertCanRequest((int) $source->department_id);
        }
        abort_unless((int) $account->companies_groups_id === $this->companyId(), 404);
        if (in_array($account->status, ['modification_requested', 'suspension_requested', 'closure_requested', 'closed'], true)) {
            throw ValidationException::withMessages(['status' => __('gov_accounts.validation.account_request_in_progress')]);
        }
        $serviceId = (int) ($payload['service_id'] ?? $account->service_id);
        $requestedRoleId = isset($payload['requested_role_id']) ? (int) $payload['requested_role_id'] : null;
        if (in_array($type, ['modify', 'permission_change'], true)) {
            $this->assertRole($requestedRoleId ?: (int) $account->role_id);
            if (! GovAccountService::query()->whereKey($serviceId)->where('companies_groups_id', $this->companyId())->where('authority_id', $account->authority_id)->where('publish', true)->exists()) {
                throw ValidationException::withMessages(['service_id' => __('gov_accounts.validation.invalid_reference')]);
            }
        }

        return DB::transaction(function () use ($account, $source, $payload, $type, $origin, $serviceId, $requestedRoleId): GovAccountRequest {
            $request = GovAccountRequest::query()->create([
                'companies_groups_id' => $account->companies_groups_id, 'branch_id' => $account->branch_id,
                'type' => $type, 'status' => 'under_review', 'origin' => $origin,
                'employee_user_id' => $account->employee_user_id, 'department_id' => $source->department_id,
                'authority_id' => $account->authority_id, 'service_id' => $serviceId, 'role_id' => $account->role_id,
                'requested_role_id' => $requestedRoleId, 'account_id' => $account->getKey(),
                'justification' => $payload['justification'], 'notes' => $payload['notes'] ?? null,
                'round' => 1, 'created_by' => $this->userId(),
                'meta' => ['previous_account_status' => $account->status, 'previous_service_id' => $account->service_id, 'previous_role_id' => $account->role_id],
            ]);
            $account->update(['status' => $this->inFlightStatus($type)]);
            $this->timeline($request, 'lifecycle_requested', __('gov_accounts.timeline.lifecycle_requested'), ['type' => $type, 'previous_status' => $request->meta['previous_account_status']], $account->getKey());
            foreach ($this->notifications->processors($this->companyId()) as $processor) {
                $this->notifications->notify($request, $processor, 'lifecycle_requested', __('gov_accounts.notifications.lifecycle_subject'), __('gov_accounts.notifications.lifecycle_body'));
            }
            if ($origin === 'hr') {
                $this->notifyCreator($request, 'lifecycle_requested', __('gov_accounts.notifications.lifecycle_subject'), __('gov_accounts.notifications.lifecycle_body'));
            }

            return $request;
        });
    }

    public function update(GovAccountRequest $request, array $payload): GovAccountRequest
    {
        $this->assertHeadOwns($request);
        if (! in_array($request->status, ['draft', 'rejected'], true)) {
            $this->invalidStatus();
        }
        $this->assertCanRequest((int) $payload['department_id']);
        $this->assertReferences($payload);
        $payload['branch_id'] = $this->resolveParentDepartmentId((int) $payload['department_id']);
        $before = $request->only(['employee_user_id', 'department_id', 'authority_id', 'service_id', 'role_id', 'justification', 'notes']);
        $request->update($payload);
        $this->timeline($request, 'updated', __('gov_accounts.timeline.updated'), ['before' => $before, 'after' => $request->only(array_keys($before))]);

        return $request->fresh() ?? $request;
    }

    public function submit(GovAccountRequest $request, string $managerText, string $employeeText, string $ip, ?string $userAgent): GovAccountRequest
    {
        $this->assertHeadOwns($request);

        return DB::transaction(function () use ($request, $managerText, $employeeText, $ip, $userAgent): GovAccountRequest {
            $this->transition($request, 'awaiting_employee');
            GovAccountUndertaking::query()->updateOrCreate(['request_id' => $request->getKey(), 'kind' => 'manager'], ['user_id' => $this->userId(), 'undertaking_text' => $managerText, 'status' => 'accepted', 'requested_at' => now(), 'accepted_at' => now(), 'ip' => $ip, 'user_agent' => $userAgent]);
            GovAccountUndertaking::query()->updateOrCreate(['request_id' => $request->getKey(), 'kind' => 'employee'], ['user_id' => $request->employee_user_id, 'undertaking_text' => $employeeText, 'status' => 'pending', 'requested_at' => now(), 'accepted_at' => null, 'ip' => null, 'user_agent' => null]);
            $this->timeline($request, 'manager_undertaking_accepted', __('gov_accounts.timeline.manager_accepted'));
            $this->timeline($request, 'submitted', __('gov_accounts.timeline.submitted'), ['status' => 'awaiting_employee']);
            $this->notifications->notify($request, $request->employee_user_id, 'employee_undertaking_requested', __('gov_accounts.notifications.undertaking_subject'), __('gov_accounts.notifications.undertaking_body'), route('modules.gov-accounts.undertakings.show', $request));

            return $request;
        });
    }

    public function acceptEmployeeUndertaking(GovAccountRequest $request, string $ip, ?string $userAgent): GovAccountRequest
    {
        abort_unless((int) $request->employee_user_id === $this->userId(), 403);

        return DB::transaction(function () use ($request, $ip, $userAgent): GovAccountRequest {
            $this->transition($request, 'under_review');
            $undertaking = GovAccountUndertaking::query()->where('request_id', $request->getKey())->where('kind', 'employee')->where('user_id', $this->userId())->firstOrFail();
            $undertaking->update(['status' => 'accepted', 'accepted_at' => now(), 'ip' => $ip, 'user_agent' => $userAgent]);
            $this->timeline($request, 'employee_undertaking_accepted', __('gov_accounts.timeline.employee_accepted'));
            foreach ($this->notifications->processors($this->companyId()) as $processor) {
                $this->notifications->notify($request, $processor, 'under_review', __('gov_accounts.notifications.review_subject'), __('gov_accounts.notifications.review_body'));
            }

            return $request;
        });
    }

    public function reject(GovAccountRequest $request, string $reason): GovAccountRequest
    {
        $this->assertCanProcess();
        $this->transition($request, 'rejected');
        $request->update(['rejection_reason' => $reason, 'reviewed_by' => $this->userId(), 'reviewed_at' => now()]);
        $this->revertLifecycleAccount($request);
        $this->timeline($request, 'rejected', $reason, ['round' => $request->round]);
        $this->notifyCreator($request, 'rejected', __('gov_accounts.notifications.rejected_subject'), $reason);

        return $request;
    }

    public function resubmit(GovAccountRequest $request, string $response): GovAccountRequest
    {
        $this->assertHeadOwns($request);
        $previousReason = $request->rejection_reason;
        $this->transition($request, 'under_review');
        $request->update(['round' => (int) $request->round + 1, 'rejection_reason' => null]);
        if ($request->type !== 'create') {
            $request->account?->update(['status' => $this->inFlightStatus($request->type)]);
        }
        $this->timeline($request, 'resubmitted', $response, ['round' => $request->round, 'previous_rejection_reason' => $previousReason]);
        foreach ($this->notifications->processors($this->companyId()) as $processor) {
            $this->notifications->notify($request, $processor, 'resubmitted', __('gov_accounts.notifications.resubmitted_subject'), $response);
        }

        return $request;
    }

    public function approve(GovAccountRequest $request, ?string $notes): GovAccountRequest
    {
        $this->assertCanProcess();
        $this->transition($request, 'approved');
        $request->update(['reviewed_by' => $this->userId(), 'reviewed_at' => now()]);
        $this->timeline($request, 'approved', $notes ?: __('gov_accounts.timeline.approved'));
        $this->notifyCreator($request, 'approved', __('gov_accounts.notifications.approved_subject'), __('gov_accounts.notifications.approved_body'));

        return $request;
    }

    public function markSubmittedToAuthority(GovAccountRequest $request, array $payload): GovAccountRequest
    {
        $this->assertCanProcess();
        $this->transition($request, 'submitted_to_authority');
        $request->update(['authority_submitted_at' => $payload['authority_submitted_at'], 'authority_submitted_by' => $this->userId(), 'authority_reference' => $payload['authority_reference'] ?? null]);
        $this->timeline($request, 'submitted_to_authority', $payload['notes'] ?? __('gov_accounts.timeline.authority_submitted'), ['authority_reference' => $request->authority_reference]);
        $this->notifyCreator($request, 'submitted_to_authority', __('gov_accounts.notifications.authority_subject'), __('gov_accounts.notifications.authority_body'));

        return $request;
    }

    public function complete(GovAccountRequest $request, array $payload): GovAccount
    {
        $this->assertCanProcess();
        if ($request->type !== 'create') {
            return $this->completeLifecycle($request);
        }
        $this->assertRole((int) $payload['role_id']);

        return DB::transaction(function () use ($request, $payload): GovAccount {
            $this->transition($request, 'completed');
            $account = GovAccount::query()->create([
                'companies_groups_id' => $request->companies_groups_id, 'branch_id' => $request->branch_id,
                'employee_user_id' => $request->employee_user_id, 'authority_id' => $request->authority_id,
                'service_id' => $request->service_id, 'role_id' => $payload['role_id'], 'username' => $payload['username'],
                'login_url' => $payload['login_url'] ?? null, 'reference_no' => $payload['reference_no'] ?? null,
                'status' => 'active', 'created_from_request_id' => $request->getKey(), 'managed_by' => $this->userId(),
                'account_created_at' => $payload['account_created_at'], 'notes' => $payload['notes'] ?? null,
            ]);
            $request->update(['account_id' => $account->getKey()]);
            $this->timeline($request, 'completed', __('gov_accounts.timeline.completed'), ['account_id' => $account->getKey(), 'username' => $account->username], $account->getKey());
            $this->notifications->notify($request, $request->employee_user_id, 'account_created', __('gov_accounts.notifications.completed_employee_subject'), __('gov_accounts.notifications.completed_employee_body', ['username' => $account->username, 'url' => $account->login_url ?: '—']));
            $this->notifyCreator($request, 'completed', __('gov_accounts.notifications.completed_head_subject'), __('gov_accounts.notifications.completed_head_body'));

            return $account;
        });
    }

    public function cancel(GovAccountRequest $request): GovAccountRequest
    {
        if ($request->status === 'under_review') {
            $this->assertCanProcess();
        } else {
            $this->assertHeadOwns($request);
        }

        return DB::transaction(function () use ($request): GovAccountRequest {
            $this->transition($request, 'cancelled');
            $this->revertLifecycleAccount($request);
            $this->timeline($request, 'cancelled', __('gov_accounts.timeline.cancelled'));

            return $request;
        });
    }

    public function storeAttachment(GovAccountRequest $request, UploadedFile $file, string $context, ?string $description): GovAccountAttachment
    {
        $this->assertCanActOn($request);
        $path = $file->store('private/gov-accounts/'.$request->companies_groups_id.'/requests/'.$request->getKey(), 'local');
        $attachment = GovAccountAttachment::query()->create(['request_id' => $request->getKey(), 'context' => $context, 'file_path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime' => $file->getMimeType() ?: 'application/octet-stream', 'size' => $file->getSize(), 'description' => $description, 'uploaded_by' => $this->userId(), 'uploaded_at' => now()]);
        $this->timeline($request, 'attachment_uploaded', __('gov_accounts.timeline.attachment_uploaded'), ['attachment_id' => $attachment->getKey(), 'context' => $context]);

        return $attachment;
    }

    public function downloadAttachment(GovAccountRequest $request, int $attachmentId): StreamedResponse
    {
        $attachment = $this->repository->attachmentForRequest($request, $attachmentId) ?? abort(404);
        abort_if(str_contains($attachment->file_path, '..') || ! Storage::disk('local')->exists($attachment->file_path), 404);

        return Storage::disk('local')->download($attachment->file_path, $attachment->original_name, ['Content-Type' => $attachment->mime]);
    }

    private function transition(GovAccountRequest $request, string $to): void
    {
        if (! in_array($to, self::CREATE_TRANSITIONS[$request->status] ?? [], true)) {
            $this->invalidStatus();
        }
        $request->update(['status' => $to]);
    }

    private function completeLifecycle(GovAccountRequest $request): GovAccount
    {
        return DB::transaction(function () use ($request): GovAccount {
            $this->transition($request, 'completed');
            $account = GovAccount::query()->where('companies_groups_id', $this->companyId())->findOrFail($request->account_id);
            $before = ['status' => $account->status, 'service_id' => $account->service_id, 'role_id' => $account->role_id];
            $changes = match ($request->type) {
                'modify' => ['service_id' => $request->service_id, 'role_id' => $request->requested_role_id ?: $account->role_id, 'status' => 'active'],
                'permission_change' => ['role_id' => $request->requested_role_id, 'status' => 'active'],
                'suspend' => ['status' => 'suspended', 'suspended_at' => now()],
                'close' => ['status' => 'closed', 'closed_at' => now(), 'closed_reason' => $request->justification],
                default => throw ValidationException::withMessages(['type' => __('gov_accounts.validation.invalid_reference')]),
            };
            $account->update($changes);
            $this->timeline($request, 'lifecycle_completed', __('gov_accounts.timeline.lifecycle_completed'), ['type' => $request->type, 'before' => $before, 'after' => $account->only(array_keys($changes))], $account->getKey());
            $this->notifications->notify($request, $request->employee_user_id, 'lifecycle_completed', __('gov_accounts.notifications.lifecycle_completed_subject'), __('gov_accounts.notifications.lifecycle_completed_body'));
            $this->notifyCreator($request, 'lifecycle_completed', __('gov_accounts.notifications.lifecycle_completed_subject'), __('gov_accounts.notifications.lifecycle_completed_body'));

            return $account;
        });
    }

    private function revertLifecycleAccount(GovAccountRequest $request): void
    {
        if ($request->type === 'create' || ! $request->account_id) {
            return;
        }
        $previous = (string) ($request->meta['previous_account_status'] ?? 'active');
        GovAccount::query()->where('companies_groups_id', $this->companyId())->whereKey($request->account_id)->update(['status' => $previous]);
        $this->timeline($request, 'account_status_reverted', __('gov_accounts.timeline.account_status_reverted'), ['status' => $previous], (int) $request->account_id);
    }

    private function inFlightStatus(string $type): string
    {
        return match ($type) {
            'modify', 'permission_change' => 'modification_requested',
            'suspend' => 'suspension_requested',
            'close' => 'closure_requested',
            default => throw ValidationException::withMessages(['type' => __('gov_accounts.validation.invalid_reference')]),
        };
    }

    private function assertReferences(array $payload): void
    {
        $company = $this->companyId();
        if (! User::query()->whereKey($payload['employee_user_id'])->where('companies_groups_id', $company)->activated()->exists()
            || ! GovAccountAuthority::query()->whereKey($payload['authority_id'])->where('companies_groups_id', $company)->where('publish', true)->exists()
            || ! GovAccountService::query()->whereKey($payload['service_id'])->where('companies_groups_id', $company)->where('authority_id', $payload['authority_id'])->where('publish', true)->exists()) {
            throw ValidationException::withMessages(['employee_user_id' => __('gov_accounts.validation.invalid_reference')]);
        }
        $this->assertRole((int) $payload['role_id']);
    }

    private function assertRole(int $roleId): void
    {
        if (! GovAccountRole::query()->whereKey($roleId)->where('companies_groups_id', $this->companyId())->where('publish', true)->exists()) {
            throw ValidationException::withMessages(['role_id' => __('gov_accounts.validation.invalid_reference')]);
        }
    }

    private function assertCanRequest(int $departmentId): void
    {
        if (GovAccountPermissions::isAdministrator($this->permissions)) {
            return;
        }
        abort_unless($this->permissions->can(GovAccountPermissions::REQUEST), 403);
        abort_unless(GovAccountDepartmentHead::query()->where('companies_groups_id', $this->companyId())->where('department_id', $departmentId)->where('user_id', $this->userId())->where('publish', true)->exists(), 403);
    }

    private function resolveParentDepartmentId(int $unitId): int
    {
        if (! Schema::hasColumn('branches_departments', 'branch_id')) {
            return (int) session('hr_branch_id', 0);
        }

        $unit = BranchDepartment::query()->whereKey($unitId)->firstOrFail();
        $parentId = (int) $unit->branch_id;
        $valid = DB::table('branches')->where('id', $parentId)
            ->where('companies_groups_id', $this->companyId())->exists();
        if (! $valid) {
            throw ValidationException::withMessages(['department_id' => __('gov_accounts.validation.invalid_scope')]);
        }

        return $parentId;
    }

    private function assertHeadOwns(GovAccountRequest $request): void
    {
        $this->assertCanRequest((int) $request->department_id);
    }

    private function assertCanProcess(): void
    {
        abort_unless(GovAccountPermissions::isAdministrator($this->permissions) || $this->permissions->can(GovAccountPermissions::PROCESS), 403);
    }

    private function assertCanActOn(GovAccountRequest $request): void
    {
        if (GovAccountPermissions::isAdministrator($this->permissions) || $this->permissions->can(GovAccountPermissions::PROCESS)) {
            return;
        }
        $this->assertHeadOwns($request);
    }

    private function timeline(GovAccountRequest $request, string $event, ?string $notice = null, array $meta = [], ?int $accountId = null): void
    {
        $this->repository->timeline(['request_id' => $request->getKey(), 'account_id' => $accountId, 'event_type' => $event, 'notice' => $notice, 'meta' => $meta ?: null, 'branch_id' => $request->branch_id]);
    }

    private function notifyCreator(GovAccountRequest $request, string $event, string $subject, string $message): void
    {
        $this->notifications->notify($request, $request->created_by, $event, $subject, $message);
    }

    private function invalidStatus(): never
    {
        throw ValidationException::withMessages(['status' => __('gov_accounts.validation.invalid_transition')]);
    }

    private function companyId(): int
    {
        return (int) session('companies_groups_id', 0);
    }

    private function userId(): int
    {
        return (int) session('hr_user_id', 0);
    }
}
