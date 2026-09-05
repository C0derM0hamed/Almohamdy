<?php

namespace App\Repositories\GovAccounts;

use App\Models\BranchDepartment;
use App\Models\GovAccount;
use App\Models\GovAccountAttachment;
use App\Models\GovAccountAuthority;
use App\Models\GovAccountDepartmentHead;
use App\Models\GovAccountRequest;
use App\Models\GovAccountRole;
use App\Models\GovAccountService;
use App\Models\GovAccountTimeline;
use App\Models\User;
use App\Services\Auth\PermissionService;
use App\Support\GovAccounts\GovAccountPermissions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GovAccountRepository
{
    public function __construct(private readonly PermissionService $permissions) {}

    /** @return Builder<GovAccountRequest> */
    public function scopedRequests(): Builder
    {
        $query = GovAccountRequest::query()->where('companies_groups_id', $this->companyId());
        if (! GovAccountPermissions::isAdministrator($this->permissions)) {
            $branchId = (int) session('hr_branch_id', 0);
            if ($branchId > 0) {
                $query->where('branch_id', $branchId);
            }
            if (! $this->permissions->can(GovAccountPermissions::VIEW)
                && ! $this->permissions->can(GovAccountPermissions::PROCESS)
                && ! $this->permissions->can(GovAccountPermissions::EXPORT)) {
                $query->whereIn('department_id', $this->headedDepartmentIds());
            }
        }

        return $query;
    }

    public function requestOrFail(int $id): GovAccountRequest
    {
        return $this->scopedRequests()->with(['hospitalBranch', 'parentDepartment', 'employee', 'department.parentDepartment', 'authority', 'service', 'role', 'requestedRole', 'account', 'undertakings.user', 'timeline', 'attachments'])->findOrFail($id);
    }

    /** @return Builder<GovAccount> */
    public function scopedAccounts(): Builder
    {
        $query = GovAccount::query()->where('companies_groups_id', $this->companyId());
        if (GovAccountPermissions::isAdministrator($this->permissions)) {
            return $query;
        }
        $branchId = (int) session('hr_branch_id', 0);
        if ($branchId > 0) {
            $query->where('branch_id', $branchId);
        }
        if (! $this->permissions->can(GovAccountPermissions::VIEW)
            && ! $this->permissions->can(GovAccountPermissions::PROCESS)
            && ! $this->permissions->can(GovAccountPermissions::HR)
            && ! $this->permissions->can(GovAccountPermissions::EXPORT)) {
            abort_unless($this->permissions->can(GovAccountPermissions::REQUEST), 403);
            $query->whereHas('sourceRequest', fn (Builder $relation) => $relation->whereIn('department_id', $this->headedDepartmentIds()));
        }

        return $query;
    }

    public function accountOrFail(int $id): GovAccount
    {
        return $this->scopedAccounts()->with(['hospitalBranch', 'employee', 'authority', 'service', 'role', 'sourceRequest.department.parentDepartment', 'requests'])->findOrFail($id);
    }

    public function accounts(array $filters = []): LengthAwarePaginator
    {
        return $this->scopedAccounts()->with(['hospitalBranch', 'employee', 'authority', 'service', 'role', 'sourceRequest.department.parentDepartment'])
            ->when($filters['employee_user_id'] ?? null, fn (Builder $query, $value) => $query->where('employee_user_id', $value))
            ->when($filters['department_id'] ?? null, fn (Builder $query, $value) => $query->whereHas('sourceRequest', fn (Builder $source) => $source->where('department_id', $value)))
            ->when($filters['authority_id'] ?? null, fn (Builder $query, $value) => $query->where('authority_id', $value))
            ->when($filters['service_id'] ?? null, fn (Builder $query, $value) => $query->where('service_id', $value))
            ->when($filters['role_id'] ?? null, fn (Builder $query, $value) => $query->where('role_id', $value))
            ->when($filters['status'] ?? null, fn (Builder $query, $value) => $query->where('status', $value))
            ->latest('id')->paginate(20)->withQueryString();
    }

    public function hrAccounts(?string $search = null): LengthAwarePaginator
    {
        $this->authorizeAny(GovAccountPermissions::HR);
        $search = trim((string) $search);

        return $this->scopedAccounts()->whereNot('status', 'closed')->with(['hospitalBranch', 'employee', 'authority', 'service', 'role', 'sourceRequest.department.parentDepartment'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->whereHas('employee', fn (Builder $user) => $user->where('hr_first_name', 'like', '%'.$search.'%')->orWhere('hr_last_name', 'like', '%'.$search.'%')->orWhere('hr_username', 'like', '%'.$search.'%'));
            })->latest('id')->paginate(20)->withQueryString();
    }

    public function employeeRequestOrFail(int $id): GovAccountRequest
    {
        return GovAccountRequest::query()->where('companies_groups_id', $this->companyId())
            ->where('employee_user_id', (int) session('hr_user_id', 0))->with(['hospitalBranch', 'department.parentDepartment', 'authority', 'service', 'role', 'undertakings'])->findOrFail($id);
    }

    public function requests(array $filters = []): LengthAwarePaginator
    {
        return $this->scopedRequests()->with(['hospitalBranch', 'employee', 'department.parentDepartment', 'authority', 'service', 'role'])
            ->when($filters['type'] ?? null, fn (Builder $query, $value) => $query->where('type', $value))
            ->when($filters['status'] ?? null, fn (Builder $query, $value) => $query->where('status', $value))
            ->when($filters['employee_user_id'] ?? null, fn (Builder $query, $value) => $query->where('employee_user_id', $value))
            ->when($filters['created_by'] ?? null, fn (Builder $query, $value) => $query->where('created_by', $value))
            ->when($filters['department_id'] ?? null, fn (Builder $query, $value) => $query->where('department_id', $value))
            ->when($filters['authority_id'] ?? null, fn (Builder $query, $value) => $query->where('authority_id', $value))
            ->when($filters['service_id'] ?? null, fn (Builder $query, $value) => $query->where('service_id', $value))
            ->when($filters['date_from'] ?? null, fn (Builder $query, $value) => $query->whereDate('created_at', '>=', $value))
            ->when($filters['date_to'] ?? null, fn (Builder $query, $value) => $query->whereDate('created_at', '<=', $value))
            ->latest('id')->paginate(20)->withQueryString();
    }

    public function requestAbilities(GovAccountRequest $request): array
    {
        $administrator = GovAccountPermissions::isAdministrator($this->permissions);
        $processor = $administrator || $this->permissions->can(GovAccountPermissions::PROCESS);
        $head = $administrator || ($this->permissions->can(GovAccountPermissions::REQUEST)
            && in_array((int) $request->department_id, $this->headedDepartmentIds(), true));

        return ['head' => $head, 'processor' => $processor, 'attach' => $head || $processor];
    }

    public function canCreateLifecycle(GovAccount $account): bool
    {
        if (GovAccountPermissions::isAdministrator($this->permissions) || $this->permissions->can(GovAccountPermissions::PROCESS)) {
            return true;
        }

        return $this->permissions->can(GovAccountPermissions::REQUEST)
            && in_array((int) $account->sourceRequest?->department_id, $this->headedDepartmentIds(), true);
    }

    /** @return Collection<int,GovAccountRequest> */
    public function pendingUndertakingsForCurrentUser(): Collection
    {
        return GovAccountRequest::query()->where('companies_groups_id', $this->companyId())
            ->where('employee_user_id', (int) session('hr_user_id', 0))->where('status', 'awaiting_employee')
            ->whereHas('undertakings', fn (Builder $query) => $query->where('kind', 'employee')->where('status', 'pending')->where('user_id', (int) session('hr_user_id', 0)))
            ->with(['hospitalBranch', 'department.parentDepartment', 'authority', 'service', 'role'])->latest('id')->get();
    }

    /** @return Collection<int,GovAccount> */
    public function accountsForCurrentUser(): Collection
    {
        return GovAccount::query()->where('companies_groups_id', $this->companyId())->where('employee_user_id', (int) session('hr_user_id', 0))
            ->with(['hospitalBranch', 'authority', 'service', 'role', 'sourceRequest.department.parentDepartment'])->latest('id')->get();
    }

    public function headedDepartmentIds(): array
    {
        return GovAccountDepartmentHead::query()->where('companies_groups_id', $this->companyId())->where('user_id', (int) session('hr_user_id', 0))
            ->where('publish', true)->pluck('department_id')->map(fn ($id): int => (int) $id)->all();
    }

    public function options(): array
    {
        $companyId = $this->companyId();

        $hospitalDepartmentIds = DB::table('branches')->where('companies_groups_id', $companyId)->pluck('id');
        $departments = BranchDepartment::query()
            ->when(! GovAccountPermissions::isAdministrator($this->permissions), fn (Builder $query) => $query->whereIn('id', $this->headedDepartmentIds()))
            ->when(Schema::hasColumn('branches_departments', 'branch_id'), fn (Builder $query) => $query->whereIn('branch_id', $hospitalDepartmentIds))
            ->when(Schema::hasColumn('branches_departments', 'publish'), fn (Builder $query) => $query->where('publish', 1))
            ->with('parentDepartment')->orderBy('name_en')->get();

        return [
            'authorities' => GovAccountAuthority::query()->where('companies_groups_id', $companyId)->where('publish', true)->orderBy('ranking')->get(),
            'services' => GovAccountService::query()->where('companies_groups_id', $companyId)->where('publish', true)->with('authority')->orderBy('ranking')->get(),
            'roles' => GovAccountRole::query()->where('companies_groups_id', $companyId)->where('publish', true)->orderBy('ranking')->get(),
            'employees' => User::query()->where('companies_groups_id', $companyId)->activated()->orderBy('hr_first_name')->get(),
            'departments' => $departments,
        ];
    }

    public function authorizeAny(string ...$permissions): void
    {
        if (GovAccountPermissions::isAdministrator($this->permissions)) {
            return;
        }
        abort_unless(collect($permissions)->contains(fn (string $permission): bool => $this->permissions->can($permission)), 403);
    }

    public function timeline(array $attributes): GovAccountTimeline
    {
        return GovAccountTimeline::query()->create($attributes + ['created_by' => (int) session('hr_user_id', 0) ?: null, 'created_by_type' => 'user', 'date' => now()]);
    }

    public function attachmentForRequest(GovAccountRequest $request, int $id): ?GovAccountAttachment
    {
        return GovAccountAttachment::query()->where('request_id', $request->getKey())->find($id);
    }

    private function companyId(): int
    {
        return (int) session('companies_groups_id', 0);
    }
}
