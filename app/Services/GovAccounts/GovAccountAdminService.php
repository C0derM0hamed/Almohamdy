<?php

namespace App\Services\GovAccounts;

use App\Models\BranchDepartment;
use App\Models\GovAccountAuthority;
use App\Models\GovAccountDepartmentHead;
use App\Models\GovAccountRole;
use App\Models\GovAccountService;
use App\Models\User;
use App\Services\Auth\PermissionService;
use App\Support\GovAccounts\GovAccountPermissions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class GovAccountAdminService
{
    private const MODELS = [
        'authorities' => GovAccountAuthority::class,
        'services' => GovAccountService::class,
        'roles' => GovAccountRole::class,
        'department-heads' => GovAccountDepartmentHead::class,
    ];

    public function __construct(private readonly PermissionService $permissions) {}

    public function records(string $reference): LengthAwarePaginator
    {
        $this->authorize();
        $class = self::MODELS[$reference] ?? abort(404);

        return $class::query()->where('companies_groups_id', $this->companyId())
            ->when($reference === 'services', fn ($query) => $query->with('authority'))
            ->when($reference === 'department-heads', fn ($query) => $query->with(['hospitalBranch', 'department.parentDepartment', 'user']))
            ->when($reference !== 'department-heads', fn ($query) => $query->orderBy('ranking'))
            ->orderBy('id')->paginate(25);
    }

    /** @return array<string,int> */
    public function summary(): array
    {
        $this->authorize();
        $companyId = $this->companyId();

        return [
            'authorities' => GovAccountAuthority::query()->where('companies_groups_id', $companyId)->count(),
            'services' => GovAccountService::query()->where('companies_groups_id', $companyId)->count(),
            'roles' => GovAccountRole::query()->where('companies_groups_id', $companyId)->count(),
            'department-heads' => GovAccountDepartmentHead::query()->where('companies_groups_id', $companyId)->count(),
        ];
    }

    public function find(string $reference, int $id): Model
    {
        $this->authorize();
        $class = self::MODELS[$reference] ?? abort(404);

        return $class::query()->where('companies_groups_id', $this->companyId())->findOrFail($id);
    }

    public function save(string $reference, array $payload, ?int $id = null): Model
    {
        $this->authorize();
        $class = self::MODELS[$reference] ?? abort(404);
        $this->assertPayloadScope($reference, $payload, $id);
        $model = $id === null ? new $class : $this->find($reference, $id);
        $model->fill($payload + ['companies_groups_id' => $this->companyId()]);
        $model->save();

        return $model->fresh() ?? $model;
    }

    public function toggle(string $reference, int $id): Model
    {
        $model = $this->find($reference, $id);
        $model->update(['publish' => ! (bool) $model->publish]);

        return $model->fresh() ?? $model;
    }

    public function options(): array
    {
        $this->authorize();
        $departmentIds = DB::table('branches')->where('companies_groups_id', $this->companyId())->pluck('id');
        $departments = BranchDepartment::query()
            ->when(Schema::hasColumn('branches_departments', 'branch_id'), fn ($query) => $query->whereIn('branch_id', $departmentIds))
            ->when(Schema::hasColumn('branches_departments', 'publish'), fn ($query) => $query->where('publish', 1))
            ->with('parentDepartment')->orderBy('name_en')->get();

        return [
            'authorities' => GovAccountAuthority::query()->where('companies_groups_id', $this->companyId())->where('publish', true)->orderBy('ranking')->get(),
            'departments' => $departments,
            'users' => User::query()->where('companies_groups_id', $this->companyId())->activated()->orderBy('hr_first_name')->get(),
        ];
    }

    private function assertPayloadScope(string $reference, array $payload, ?int $id): void
    {
        if ($reference === 'services' && ! GovAccountAuthority::query()->where('companies_groups_id', $this->companyId())->whereKey($payload['authority_id'])->exists()) {
            throw ValidationException::withMessages(['authority_id' => __('gov_accounts.validation.invalid_authority')]);
        }
        if ($reference === 'department-heads') {
            $departmentQuery = BranchDepartment::query()->whereKey($payload['department_id']);
            if (Schema::hasColumn('branches_departments', 'branch_id')) {
                $departmentQuery->whereIn('branch_id', DB::table('branches')->where('companies_groups_id', $this->companyId())->pluck('id'));
            }
            if (! $departmentQuery->exists()
                || ! User::query()->whereKey($payload['user_id'])->where('companies_groups_id', $this->companyId())->exists()) {
                throw ValidationException::withMessages(['department_id' => __('gov_accounts.validation.invalid_scope')]);
            }
            $duplicate = GovAccountDepartmentHead::query()->where('department_id', $payload['department_id'])->where('user_id', $payload['user_id'])
                ->when($id !== null, fn ($query) => $query->whereKeyNot($id))->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['user_id' => __('gov_accounts.validation.duplicate_department_head')]);
            }
        }
    }

    private function authorize(): void
    {
        abort_unless(GovAccountPermissions::isAdministrator($this->permissions), 403);
    }

    private function companyId(): int
    {
        return (int) session('companies_groups_id', 0);
    }
}
