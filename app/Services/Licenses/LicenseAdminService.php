<?php

namespace App\Services\Licenses;

use App\Models\LicenseAuthority;
use App\Models\LicenseEscalationGroup;
use App\Models\LicenseEscalationGroupMember;
use App\Models\LicenseRenewalStage;
use App\Models\LicenseTimeline;
use App\Models\LicenseType;
use App\Models\User;
use App\Services\Auth\PermissionService;
use App\Support\Licenses\LicensePermissions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class LicenseAdminService
{
    /** @var array<string,class-string<Model>> */
    private const REFERENCE_MODELS = [
        'authorities' => LicenseAuthority::class,
        'types' => LicenseType::class,
        'stages' => LicenseRenewalStage::class,
    ];

    public function __construct(private readonly PermissionService $permissions) {}

    /** @return array<string,mixed> */
    public function overview(): array
    {
        $this->authorize();

        return [
            'authorities' => $this->authorities(),
            'types' => $this->types(),
            'stages' => $this->stages(),
            'escalationGroups' => $this->escalationGroups(),
            'users' => $this->sameCompanyUserOptions(),
        ];
    }

    /** @return array{authorities:int,types:int,stages:int,escalation_groups:int} */
    public function counts(): array
    {
        $this->authorize();

        return [
            'authorities' => LicenseAuthority::query()->where('companies_groups_id', $this->companyId())->count(),
            'types' => LicenseType::query()->where('companies_groups_id', $this->companyId())->count(),
            'stages' => LicenseRenewalStage::query()->count(),
            'escalation_groups' => LicenseEscalationGroup::query()->where('companies_groups_id', $this->companyId())->count(),
        ];
    }

    public function referenceRecords(string $reference, int $perPage = 25): LengthAwarePaginator
    {
        return match ($reference) {
            'authorities' => $this->authorities($perPage),
            'types' => $this->types($perPage),
            'stages' => $this->stages($perPage),
            default => abort(404),
        };
    }

    public function findReference(string $reference, int $id): Model
    {
        $this->authorize();
        $class = self::REFERENCE_MODELS[$reference] ?? abort(404);
        $model = $class::query()->findOrFail($id);
        if ($reference !== 'stages') {
            abort_unless((int) $model->companies_groups_id === $this->companyId(), 404);
        }

        return $model;
    }

    /** @param array<string,mixed> $payload */
    public function createReference(string $reference, array $payload): Model
    {
        return match ($reference) {
            'authorities' => $this->createAuthority($payload),
            'types' => $this->createType($payload),
            'stages' => $this->createStage($payload),
            default => abort(404),
        };
    }

    /** @param array<string,mixed> $payload */
    public function updateReference(string $reference, int $id, array $payload): Model
    {
        return match ($reference) {
            'authorities' => $this->updateAuthority($id, $payload),
            'types' => $this->updateType($id, $payload),
            'stages' => $this->updateStage($id, $payload),
            default => abort(404),
        };
    }

    public function toggleReference(string $reference, int $id): Model
    {
        return match ($reference) {
            'authorities' => $this->toggleAuthority($id),
            'types' => $this->toggleType($id),
            'stages' => $this->toggleStage($id),
            default => abort(404),
        };
    }

    public function authorities(int $perPage = 25): LengthAwarePaginator
    {
        $this->authorize();

        return LicenseAuthority::query()->where('companies_groups_id', $this->companyId())->orderBy('ranking')->orderBy('id')->paginate($perPage);
    }

    /** @param array<string,mixed> $payload */
    public function createAuthority(array $payload): LicenseAuthority
    {
        return $this->createScoped(LicenseAuthority::class, $payload, 'authority_created');
    }

    /** @param array<string,mixed> $payload */
    public function updateAuthority(int|LicenseAuthority $authority, array $payload): LicenseAuthority
    {
        return $this->updateScoped(LicenseAuthority::class, $authority, $payload, 'authority_updated');
    }

    public function toggleAuthority(int|LicenseAuthority $authority): LicenseAuthority
    {
        return $this->toggleScoped(LicenseAuthority::class, $authority, 'authority_toggled');
    }

    public function types(int $perPage = 25): LengthAwarePaginator
    {
        $this->authorize();

        return LicenseType::query()->where('companies_groups_id', $this->companyId())->orderBy('ranking')->orderBy('id')->paginate($perPage);
    }

    /** @param array<string,mixed> $payload */
    public function createType(array $payload): LicenseType
    {
        return $this->createScoped(LicenseType::class, $payload, 'type_created');
    }

    /** @param array<string,mixed> $payload */
    public function updateType(int|LicenseType $type, array $payload): LicenseType
    {
        return $this->updateScoped(LicenseType::class, $type, $payload, 'type_updated');
    }

    public function toggleType(int|LicenseType $type): LicenseType
    {
        return $this->toggleScoped(LicenseType::class, $type, 'type_toggled');
    }

    public function stages(int $perPage = 25): LengthAwarePaginator
    {
        $this->authorize();

        return LicenseRenewalStage::query()->orderBy('ranking')->orderBy('id')->paginate($perPage);
    }

    /** @param array<string,mixed> $payload */
    public function createStage(array $payload): LicenseRenewalStage
    {
        $this->authorize();
        $payload['code'] = $payload['code'] ?? 'custom_'.strtolower((string) str()->ulid());
        $stage = LicenseRenewalStage::query()->create($payload);
        $this->audit('stage_created', ['id' => $stage->getKey(), 'attributes' => $payload]);

        return $stage;
    }

    /** @param array<string,mixed> $payload */
    public function updateStage(int|LicenseRenewalStage $stage, array $payload): LicenseRenewalStage
    {
        $this->authorize();
        $stage = $stage instanceof LicenseRenewalStage ? $stage : LicenseRenewalStage::query()->findOrFail($stage);
        $stage->update($payload);
        $this->audit('stage_updated', ['id' => $stage->getKey(), 'changes' => $stage->getChanges()]);

        return $stage->fresh() ?? $stage;
    }

    public function toggleStage(int|LicenseRenewalStage $stage): LicenseRenewalStage
    {
        $this->authorize();
        $stage = $stage instanceof LicenseRenewalStage ? $stage : LicenseRenewalStage::query()->findOrFail($stage);
        $stage->update(['publish' => ! (bool) $stage->publish]);
        $this->audit('stage_toggled', ['id' => $stage->getKey(), 'publish' => (bool) $stage->publish]);

        return $stage;
    }

    public function escalationGroups(int $perPage = 25): LengthAwarePaginator
    {
        $this->authorize();

        return LicenseEscalationGroup::query()->where('companies_groups_id', $this->companyId())
            ->with(['members.user'])->withCount('members')->orderByDesc('id')->paginate($perPage);
    }

    public function findEscalationGroup(int $id): LicenseEscalationGroup
    {
        return $this->scopedGroup($id)->load(['members.user']);
    }

    /** @param array<string,mixed> $payload */
    public function createEscalationGroup(array $payload): LicenseEscalationGroup
    {
        $this->authorize();
        $group = LicenseEscalationGroup::query()->create($payload + ['companies_groups_id' => $this->companyId()]);
        $this->audit('escalation_group_created', ['id' => $group->getKey(), 'attributes' => $payload]);

        return $group;
    }

    /** @param array<string,mixed> $payload */
    public function updateEscalationGroup(int|LicenseEscalationGroup $group, array $payload): LicenseEscalationGroup
    {
        $group = $this->scopedGroup($group);
        $group->update($payload);
        $this->audit('escalation_group_updated', ['id' => $group->getKey(), 'changes' => $group->getChanges()]);

        return $group->fresh(['members.user']) ?? $group;
    }

    public function toggleEscalationGroup(int|LicenseEscalationGroup $group): LicenseEscalationGroup
    {
        $group = $this->scopedGroup($group);
        $group->update(['publish' => ! (bool) $group->publish]);
        $this->audit('escalation_group_toggled', ['id' => $group->getKey(), 'publish' => (bool) $group->publish]);

        return $group;
    }

    public function addEscalationMember(int|LicenseEscalationGroup $group, int $userId): LicenseEscalationGroupMember
    {
        $group = $this->scopedGroup($group);
        if (! User::query()->whereKey($userId)->where('companies_groups_id', $this->companyId())->exists()) {
            throw ValidationException::withMessages(['user_id' => __('licenses.validation.invalid_member')]);
        }
        $member = LicenseEscalationGroupMember::query()->firstOrCreate(['group_id' => $group->getKey(), 'user_id' => $userId]);
        $this->audit('escalation_member_added', ['group_id' => $group->getKey(), 'user_id' => $userId]);

        return $member;
    }

    public function removeEscalationMember(int|LicenseEscalationGroup $group, int|LicenseEscalationGroupMember $member): void
    {
        $group = $this->scopedGroup($group);
        $member = $member instanceof LicenseEscalationGroupMember ? $member : LicenseEscalationGroupMember::query()->findOrFail($member);
        abort_unless((int) $member->group_id === (int) $group->getKey(), 404);
        $userId = (int) $member->user_id;
        $member->delete();
        $this->audit('escalation_member_removed', ['group_id' => $group->getKey(), 'user_id' => $userId]);
    }

    /** @return Collection<int,User> */
    public function sameCompanyUserOptions(): Collection
    {
        $this->authorize();

        return User::query()->where('companies_groups_id', $this->companyId())->activated()
            ->orderBy('hr_first_name')->orderBy('hr_last_name')->get();
    }

    /** @param class-string<Model> $class @param array<string,mixed> $payload */
    private function createScoped(string $class, array $payload, string $event): Model
    {
        $this->authorize();
        $model = $class::query()->create($payload + ['companies_groups_id' => $this->companyId()]);
        $this->audit($event, ['id' => $model->getKey(), 'attributes' => $payload]);

        return $model;
    }

    /** @param class-string<Model> $class @param array<string,mixed> $payload */
    private function updateScoped(string $class, int|Model $record, array $payload, string $event): Model
    {
        $this->authorize();
        $model = $record instanceof Model ? $record : $class::query()->findOrFail($record);
        abort_unless((int) $model->companies_groups_id === $this->companyId(), 404);
        $model->update($payload);
        $this->audit($event, ['id' => $model->getKey(), 'changes' => $model->getChanges()]);

        return $model->fresh() ?? $model;
    }

    /** @param class-string<Model> $class */
    private function toggleScoped(string $class, int|Model $record, string $event): Model
    {
        $this->authorize();
        $model = $record instanceof Model ? $record : $class::query()->findOrFail($record);
        abort_unless((int) $model->companies_groups_id === $this->companyId(), 404);
        $model->update(['publish' => ! (bool) $model->publish]);
        $this->audit($event, ['id' => $model->getKey(), 'publish' => (bool) $model->publish]);

        return $model;
    }

    private function scopedGroup(int|LicenseEscalationGroup $group): LicenseEscalationGroup
    {
        $this->authorize();
        $group = $group instanceof LicenseEscalationGroup ? $group : LicenseEscalationGroup::query()->findOrFail($group);
        abort_unless((int) $group->companies_groups_id === $this->companyId(), 404);

        return $group;
    }

    /** @param array<string,mixed> $meta */
    private function audit(string $event, array $meta): void
    {
        LicenseTimeline::query()->create([
            'license_id' => null, 'event_type' => 'settings_changed', 'notice' => $event, 'meta' => $meta,
            'created_by' => (int) session('hr_user_id', 0), 'created_by_type' => 'user',
            'branch_id' => null, 'date' => now(),
        ]);
    }

    private function authorize(): void
    {
        abort_unless(LicensePermissions::isAdministrator($this->permissions), 403);
    }

    private function companyId(): int
    {
        return (int) session('companies_groups_id', 0);
    }
}
