<?php

namespace App\Services\GovAccounts;

use App\Mail\GovAccountNoticeMail;
use App\Models\BranchDepartment;
use App\Models\GovAccount;
use App\Models\GovAccountAttachment;
use App\Models\GovAccountAuthority;
use App\Models\GovAccountNotice;
use App\Models\GovAccountNoticeRecipient;
use App\Models\GovAccountNotification;
use App\Models\GovAccountService;
use App\Models\User;
use App\Repositories\GovAccounts\GovAccountRepository;
use App\Support\GovAccounts\GovAccountPermissions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class GovAccountNoticeService
{
    public function __construct(private readonly GovAccountRepository $repository) {}

    public function notices(): LengthAwarePaginator
    {
        $this->authorize();

        return $this->scoped()->with(['authority', 'service'])->latest('id')->paginate(20)->withQueryString();
    }

    public function noticeOrFail(int $id): GovAccountNotice
    {
        $this->authorize();

        return $this->scoped()->with(['authority', 'service', 'recipients.user', 'attachments', 'timeline'])->findOrFail($id);
    }

    public function options(): array
    {
        $this->authorize();
        $companyId = $this->companyId();
        $branchIds = DB::table('branches')->where('companies_groups_id', $companyId)->pluck('id');
        $departments = BranchDepartment::query()
            ->when(Schema::hasColumn('branches_departments', 'branch_id'), fn (Builder $query) => $query->whereIn('branch_id', $branchIds))
            ->when(Schema::hasColumn('branches_departments', 'publish'), fn (Builder $query) => $query->where('publish', true))
            ->orderBy('name_en')->get();

        return [
            'authorities' => GovAccountAuthority::query()->where('companies_groups_id', $companyId)->where('publish', true)->orderBy('ranking')->get(),
            'services' => GovAccountService::query()->where('companies_groups_id', $companyId)->where('publish', true)->orderBy('ranking')->get(),
            'users' => User::query()->where('companies_groups_id', $companyId)->activated()->orderBy('hr_first_name')->get(),
            'departments' => $departments,
        ];
    }

    public function create(array $payload): GovAccountNotice
    {
        $this->authorize();
        $this->validateReferences($payload);

        $notice = GovAccountNotice::query()->create($payload + [
            'companies_groups_id' => $this->companyId(),
            'created_by' => (int) session('hr_user_id', 0),
            'publish' => true,
        ]);
        $this->audit($notice, 'notice_created', __('gov_accounts.notices.new'));

        return $notice;
    }

    public function update(GovAccountNotice $notice, array $payload): GovAccountNotice
    {
        $notice = $this->noticeOrFail((int) $notice->getKey());
        abort_if($notice->sent_at !== null, 422);
        $this->validateReferences($payload);
        $notice->update($payload);
        $this->audit($notice, 'notice_updated', __('gov_accounts.actions.edit'));

        return $notice->fresh(['authority', 'service', 'attachments', 'timeline']) ?? $notice;
    }

    public function storeAttachment(GovAccountNotice $notice, UploadedFile $file, ?string $description = null): GovAccountAttachment
    {
        $notice = $this->noticeOrFail((int) $notice->getKey());
        $path = $file->store('private/gov-accounts/'.$notice->companies_groups_id.'/notices/'.$notice->getKey(), 'local');
        $attachment = GovAccountAttachment::query()->create(['notice_id' => $notice->getKey(), 'context' => 'notice', 'file_path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime' => $file->getMimeType() ?: 'application/octet-stream', 'size' => $file->getSize(), 'description' => $description, 'uploaded_by' => (int) session('hr_user_id', 0), 'uploaded_at' => now()]);
        $this->audit($notice, 'notice_attachment_uploaded', __('gov_accounts.timeline.attachment_uploaded'), ['attachment_id' => $attachment->getKey()]);

        return $attachment;
    }

    public function downloadAttachment(GovAccountNotice $notice, int $attachmentId): StreamedResponse
    {
        $notice = $this->noticeOrFail((int) $notice->getKey());
        $attachment = GovAccountAttachment::query()->where('notice_id', $notice->getKey())->findOrFail($attachmentId);
        abort_if(str_contains($attachment->file_path, '..') || ! Storage::disk('local')->exists($attachment->file_path), 404);

        return Storage::disk('local')->download($attachment->file_path, $attachment->original_name, ['Content-Type' => $attachment->mime]);
    }

    public function send(GovAccountNotice $notice): int
    {
        $notice = $this->noticeOrFail((int) $notice->getKey());
        $users = $this->resolveRecipients($notice);

        foreach ($users as $user) {
            $this->deliver($notice, $user);
        }

        $notice->update(['sent_at' => now()]);
        $this->audit($notice, 'notice_sent', __('gov_accounts.notices.sent'), ['recipient_count' => $users->count()]);

        return $users->count();
    }

    /** @return Collection<int,User> */
    public function resolveRecipients(GovAccountNotice $notice): Collection
    {
        $targeting = $notice->targeting ?? [];
        $mode = (string) ($targeting['mode'] ?? '');
        $ids = collect($targeting['ids'] ?? [])->map(fn ($id): int => (int) $id)->filter()->unique()->all();
        $users = User::query()->where('companies_groups_id', $notice->companies_groups_id)->activated();

        if ($mode === 'users') {
            $users->whereIn('hr_id', $ids);
        } elseif ($mode === 'departments') {
            $userIds = GovAccount::query()->where('companies_groups_id', $notice->companies_groups_id)
                ->whereHas('sourceRequest', fn (Builder $query) => $query->whereIn('department_id', $ids))
                ->pluck('employee_user_id');
            $users->whereIn('hr_id', $userIds);
        } elseif ($mode === 'service') {
            $userIds = GovAccount::query()->where('companies_groups_id', $notice->companies_groups_id)
                ->where('service_id', $notice->service_id)->pluck('employee_user_id');
            $users->whereIn('hr_id', $userIds);
        } elseif ($mode !== 'all') {
            throw ValidationException::withMessages(['targeting_mode' => __('gov_accounts.validation.invalid_reference')]);
        }

        return $users->whereNotNull('hr_email_address')->get()->unique('hr_id')->values();
    }

    public function recordPublicView(string $token): GovAccountNoticeRecipient
    {
        abort_unless(strlen($token) === 64, 404);
        $candidate = GovAccountNoticeRecipient::query()->where('token', $token)->firstOrFail();
        abort_unless(hash_equals((string) $candidate->token, $token), 404);

        return DB::transaction(function () use ($candidate): GovAccountNoticeRecipient {
            $recipient = GovAccountNoticeRecipient::query()->lockForUpdate()->findOrFail($candidate->getKey());
            $now = now();
            $recipient->update([
                'viewed_at' => $recipient->viewed_at ?? $now,
                'last_viewed_at' => $now,
                'view_count' => $recipient->view_count + 1,
            ]);

            return $recipient->fresh(['notice.authority', 'notice.service', 'user']);
        });
    }

    /** @return Builder<GovAccountNotice> */
    private function scoped(): Builder
    {
        return GovAccountNotice::query()->where('companies_groups_id', $this->companyId());
    }

    private function authorize(): void
    {
        $this->repository->authorizeAny(GovAccountPermissions::PROCESS);
    }

    private function validateReferences(array $payload): void
    {
        $companyId = $this->companyId();
        $authority = GovAccountAuthority::query()->where('companies_groups_id', $companyId)->where('publish', true)->find($payload['authority_id']);
        $service = isset($payload['service_id'])
            ? GovAccountService::query()->where('companies_groups_id', $companyId)->where('publish', true)->find($payload['service_id'])
            : null;
        if (! $authority || ($payload['service_id'] && (! $service || (int) $service->authority_id !== (int) $authority->getKey()))) {
            throw ValidationException::withMessages(['authority_id' => __('gov_accounts.validation.invalid_reference')]);
        }

        $targeting = $payload['targeting'];
        $ids = $targeting['ids'];
        if ($targeting['mode'] === 'users' && User::query()->where('companies_groups_id', $companyId)->activated()->whereIn('hr_id', $ids)->count() !== count($ids)) {
            throw ValidationException::withMessages(['user_ids' => __('gov_accounts.validation.invalid_scope')]);
        }
        if ($targeting['mode'] === 'departments') {
            $departments = BranchDepartment::query()->whereIn('id', $ids);
            if (Schema::hasColumn('branches_departments', 'branch_id')) {
                $branchIds = DB::table('branches')->where('companies_groups_id', $companyId)->pluck('id');
                $departments->whereIn('branch_id', $branchIds);
            }
            if ($departments->count() !== count($ids)) {
                throw ValidationException::withMessages(['department_ids' => __('gov_accounts.validation.invalid_scope')]);
            }
        }
    }

    private function deliver(GovAccountNotice $notice, User $user): void
    {
        $email = trim((string) $user->hr_email_address);
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $recipient = GovAccountNoticeRecipient::query()->firstOrCreate(
            ['notice_id' => $notice->getKey(), 'user_id' => $user->getKey()],
            ['email' => $email, 'token' => $this->uniqueToken()],
        );
        $recipient->update(['email' => $email, 'sent_at' => now()]);
        $base = ['notice_id' => $notice->getKey(), 'event_type' => 'notice_sent', 'recipient_user_id' => $user->getKey(), 'recipient_email' => $email, 'meta' => [], 'created_at' => now(), 'updated_at' => now()];
        GovAccountNotification::query()->create($base + ['channel' => 'inapp', 'status' => 'logged']);

        if (! config('hm.gov_accounts.notifications.mail', false)) {
            GovAccountNotification::query()->create($base + ['channel' => 'mail', 'status' => 'failed', 'error' => 'Mail channel disabled']);

            return;
        }

        try {
            Mail::to($email)->send(new GovAccountNoticeMail($notice, $user->displayName(), route('public.gov-account-notices.view', $recipient->token)));
            GovAccountNotification::query()->create($base + ['channel' => 'mail', 'status' => 'sent']);
        } catch (Throwable $exception) {
            GovAccountNotification::query()->create($base + ['channel' => 'mail', 'status' => 'failed', 'error' => $exception->getMessage()]);
            Log::error('gov_accounts.notice.mail_failed', ['notice_id' => $notice->getKey(), 'user_id' => $user->getKey(), 'error' => $exception->getMessage()]);
        }
    }

    private function uniqueToken(): string
    {
        do {
            $token = bin2hex(random_bytes(32));
        } while (GovAccountNoticeRecipient::query()->where('token', $token)->exists());

        return $token;
    }

    private function audit(GovAccountNotice $notice, string $event, string $message, array $meta = []): void
    {
        $this->repository->timeline(['notice_id' => $notice->getKey(), 'event_type' => $event, 'notice' => $message, 'meta' => $meta ?: null]);
    }

    private function companyId(): int
    {
        return (int) session('companies_groups_id', 0);
    }
}
