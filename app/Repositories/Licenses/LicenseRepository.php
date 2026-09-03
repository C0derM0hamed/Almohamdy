<?php

namespace App\Repositories\Licenses;

use App\Models\Branch;
use App\Models\License;
use App\Models\LicenseAttachment;
use App\Models\LicenseAuthority;
use App\Models\LicenseComment;
use App\Models\LicenseNotification;
use App\Models\LicenseRenewal;
use App\Models\LicenseRenewalStage;
use App\Models\LicenseStatus;
use App\Models\LicenseTimeline;
use App\Models\LicenseType;
use App\Models\LicenseUndertaking;
use App\Models\User;
use App\Services\Auth\PermissionService;
use App\Support\Licenses\LicensePermissions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LicenseRepository
{
    public function __construct(private readonly PermissionService $permissions) {}

    /** @return Builder<License> */
    public function scopedQuery(bool $financeContext = false): Builder
    {
        $query = License::query()
            ->where('companies_groups_id', (int) session('companies_groups_id', 0))
            ->where('publish', true);

        $branchId = (int) session('hr_branch_id', 0);
        if (! $this->permissions->isAdmin()
            && ! LicensePermissions::isAdministrator($this->permissions)
            && $branchId > 0) {
            $query->whereHas('branches', fn (Builder $branchQuery) => $branchQuery->where('branches.id', $branchId));
        }

        if (! LicensePermissions::isAdministrator($this->permissions)
            && ! ($financeContext && LicensePermissions::isFinance($this->permissions))) {
            $query->where('responsible_user_id', (int) session('hr_user_id', 0));
        }

        return $query;
    }

    /** @param array<string,mixed> $filters */
    public function filteredQuery(array $filters, bool $financeContext = false): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return $this->scopedQuery($financeContext)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('license_number', 'like', '%'.$search.'%')
                        ->orWhere('title', 'like', '%'.$search.'%')
                        ->orWhereHas('authority', fn (Builder $relation) => $relation
                            ->where('name_ar', 'like', '%'.$search.'%')
                            ->orWhere('name_en', 'like', '%'.$search.'%'))
                        ->orWhereHas('type', fn (Builder $relation) => $relation
                            ->where('name_ar', 'like', '%'.$search.'%')
                            ->orWhere('name_en', 'like', '%'.$search.'%'));
                });
            })
            ->when(isset($filters['branch_id']), fn (Builder $query) => $query
                ->whereHas('branches', fn (Builder $relation) => $relation->where('branches.id', (int) $filters['branch_id'])))
            ->when(isset($filters['authority_id']), fn (Builder $query) => $query->where('license_authority_id', (int) $filters['authority_id']))
            ->when(isset($filters['type_id']), fn (Builder $query) => $query->where('license_type_id', (int) $filters['type_id']))
            ->when(isset($filters['responsible_user_id']), fn (Builder $query) => $query->where('responsible_user_id', (int) $filters['responsible_user_id']))
            ->when(isset($filters['status_id']), fn (Builder $query) => $query->where('status_id', (int) $filters['status_id']))
            ->when(isset($filters['expiry_from']), fn (Builder $query) => $query->whereDate('expiry_date', '>=', $filters['expiry_from']))
            ->when(isset($filters['expiry_to']), fn (Builder $query) => $query->whereDate('expiry_date', '<=', $filters['expiry_to']))
            ->when(isset($filters['expiry_window']), function (Builder $query) use ($filters): void {
                $window = (string) $filters['expiry_window'];
                if ($window === 'expired') {
                    $query->whereDate('expiry_date', '<', today());
                } else {
                    $query->whereDate('expiry_date', '>=', today())
                        ->whereDate('expiry_date', '<=', today()->addDays((int) $window));
                }
            });
    }

    /** @param array<string,mixed> $filters */
    public function paginateFiltered(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = $this->filteredQuery($filters)->with([
            'authority', 'type', 'status', 'renewalStage', 'responsibleUser', 'branches',
        ]);

        match ((string) ($filters['sort'] ?? 'expiry_asc')) {
            'expiry_desc' => $query->orderByDesc('expiry_date'),
            'created_asc' => $query->orderBy('created_at'),
            'created_desc' => $query->orderByDesc('created_at'),
            default => $query->orderBy('expiry_date'),
        };

        return $query->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function findForDetail(int $id, bool $financeContext = false): ?License
    {
        return $this->scopedQuery($financeContext)
            ->with([
                'authority', 'type', 'status', 'renewalStage', 'responsibleUser', 'branches',
                'undertakings.user', 'renewals', 'comments.user', 'attachments.uploader',
                'timelineEntries.creator', 'paymentRequests.status', 'paymentRequests.requester',
                'paymentRequests.events.creator', 'notifications',
            ])
            ->whereKey($id)
            ->first();
    }

    public function findOrFailForDetail(int $id, bool $financeContext = false): License
    {
        return $this->findForDetail($id, $financeContext) ?? abort(404);
    }

    /** @param array<string,mixed> $attributes */
    public function create(array $attributes): License
    {
        return License::query()->create($attributes);
    }

    /** @param array<string,mixed> $attributes */
    public function update(License $license, array $attributes): License
    {
        $license->fill($attributes)->save();

        return $license;
    }

    /** @param list<int> $branchIds */
    public function syncBranches(License $license, array $branchIds): void
    {
        $license->branches()->sync($branchIds);
    }

    public function statusId(string $code): int
    {
        return (int) LicenseStatus::query()->where('code', $code)->value('id');
    }

    public function stageId(string $code): ?int
    {
        $id = LicenseRenewalStage::query()->where('code', $code)->value('id');

        return $id !== null ? (int) $id : null;
    }

    /** @param array<string,mixed> $attributes */
    public function createUndertaking(array $attributes): LicenseUndertaking
    {
        return LicenseUndertaking::query()->create($attributes);
    }

    public function pendingUndertaking(License $license, int $userId): ?LicenseUndertaking
    {
        return LicenseUndertaking::query()
            ->where('license_id', $license->getKey())
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'escalated'])
            ->latest('requested_at')
            ->first();
    }

    public function latestUndertakingForUser(License $license, int $userId): ?LicenseUndertaking
    {
        return LicenseUndertaking::query()
            ->where('license_id', $license->getKey())
            ->where('user_id', $userId)
            ->latest('requested_at')
            ->first();
    }

    public function openRenewal(License $license): ?LicenseRenewal
    {
        return LicenseRenewal::query()->where('license_id', $license->getKey())->whereNull('completed_at')->latest('id')->first();
    }

    /** @param array<string,mixed> $attributes */
    public function createRenewal(array $attributes): LicenseRenewal
    {
        return LicenseRenewal::query()->create($attributes);
    }

    /** @param array<string,mixed> $attributes */
    public function createComment(array $attributes): LicenseComment
    {
        return LicenseComment::query()->create($attributes);
    }

    /** @param array<string,mixed> $attributes */
    public function createAttachment(array $attributes): LicenseAttachment
    {
        return LicenseAttachment::query()->create($attributes);
    }

    /** @param array<string,mixed> $attributes */
    public function timeline(array $attributes): LicenseTimeline
    {
        $attributes += [
            'created_by' => (int) session('hr_user_id', 0) ?: null,
            'created_by_type' => 'user',
            'date' => now(),
        ];

        return LicenseTimeline::query()->create($attributes);
    }

    public function attachmentForLicense(License $license, int $attachmentId): ?LicenseAttachment
    {
        return LicenseAttachment::query()
            ->where('license_id', $license->getKey())
            ->whereKey($attachmentId)
            ->first();
    }

    public function notificationWasLogged(int $licenseId, string $eventType, string $expiryDate, ?int $recipientUserId = null): bool
    {
        return LicenseNotification::query()
            ->where('license_id', $licenseId)
            ->where('event_type', $eventType)
            ->where('channel', 'inapp')
            ->when($recipientUserId !== null, fn (Builder $query) => $query->where('recipient_user_id', $recipientUserId))
            ->where('meta->expiry_date', $expiryDate)
            ->exists();
    }

    /** @return Collection<int,LicenseAuthority> */
    public function authorityOptions(bool $publishedOnly = true): Collection
    {
        return LicenseAuthority::query()->where('companies_groups_id', (int) session('companies_groups_id', 0))
            ->when($publishedOnly, fn (Builder $query) => $query->where('publish', true))->orderBy('ranking')->orderBy('id')->get();
    }

    /** @return Collection<int,LicenseType> */
    public function typeOptions(bool $publishedOnly = true): Collection
    {
        return LicenseType::query()->where('companies_groups_id', (int) session('companies_groups_id', 0))
            ->when($publishedOnly, fn (Builder $query) => $query->where('publish', true))->orderBy('ranking')->orderBy('id')->get();
    }

    public function statusOptions(): Collection
    {
        return LicenseStatus::query()->where('publish', true)->orderBy('ranking')->get();
    }

    public function stageOptions(bool $publishedOnly = true): Collection
    {
        return LicenseRenewalStage::query()->when($publishedOnly, fn (Builder $query) => $query->where('publish', true))->orderBy('ranking')->get();
    }

    public function branchOptions(): Collection
    {
        return Branch::query()->where('companies_groups_id', (int) session('companies_groups_id', 0))
            ->when((int) session('hr_user_level', 0) !== 3 && (int) session('hr_branch_id', 0) > 0,
                fn (Builder $query) => $query->whereKey((int) session('hr_branch_id')))
            ->orderBy('id')->get();
    }

    public function responsibleUserOptions(): Collection
    {
        return User::query()->where('companies_groups_id', (int) session('companies_groups_id', 0))
            ->activated()->orderBy('hr_first_name')->orderBy('hr_last_name')->get();
    }
}
