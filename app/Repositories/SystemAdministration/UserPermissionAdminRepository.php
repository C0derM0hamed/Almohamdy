<?php

namespace App\Repositories\SystemAdministration;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserPermissionAdminRepository
{
    public function users(?int $companyId, ?int $branchId, int $perPage = 20): LengthAwarePaginator
    {
        return User::query()
            ->select(['hr_id', 'hr_first_name', 'hr_last_name', 'hr_email_address', 'hr_username', 'hr_user_level', 'branch_id', 'companies_groups_id', 'groupid', 'activated'])
            ->when($companyId !== null, fn ($query) => $query->where('companies_groups_id', $companyId))
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->orderBy('hr_first_name')
            ->orderBy('hr_id')
            ->paginate($perPage);
    }

    public function findScoped(int $id, ?int $companyId, ?int $branchId): ?User
    {
        return User::query()
            ->whereKey($id)
            ->when($companyId !== null, fn ($query) => $query->where('companies_groups_id', $companyId))
            ->when($branchId !== null, fn ($query) => $query->where('branch_id', $branchId))
            ->first();
    }

    public function groups(?int $branchId = null): Collection
    {
        if ($branchId !== null) {
            return DB::table('user_groups')->where('publish', 1)->orderBy('name_ar')->get();
        }

        return DB::table('user_groups')->where('publish', 1)->orderBy('name_ar')->get();
    }

    public function branches(?int $companyId): Collection
    {
        return DB::table('branches')->when($companyId !== null, fn ($query) => $query->where('companies_groups_id', $companyId))->orderBy('name_ar')->get();
    }

    public function companies(): Collection
    {
        return DB::table('companies_groups')->orderBy('name_ar')->get();
    }

    public function permissionCatalog(): Collection
    {
        return DB::query()->fromSub(
            DB::table('user_permission')->select('page')->where('page', '<>', '0')
                ->union(DB::table('user_groups_permission')->select('page')->where('page', '<>', '0')),
            'permission_pages'
        )->select('page')->distinct()->orderBy('page')->get();
    }

    public function directPermissions(int $userId): Collection
    {
        return DB::table('user_permission')->where('userid', $userId)
            ->whereNotIn('permit', ['', '0'])->pluck('page');
    }

    public function inheritedPermissions(int $groupId): Collection
    {
        if ($groupId <= 0) {
            return collect();
        }

        return DB::table('user_groups_permission')->where('groupid', $groupId)
            ->whereNotIn('permit', ['', '0'])->pluck('page');
    }

    public function replaceDirectPermissions(int $userId, array $permissions): void
    {
        $desired = array_values(array_unique($permissions));
        $current = $this->directPermissions($userId)->unique()->values()->all();
        $remove = array_diff($current, $desired);

        if ($remove !== []) {
            DB::table('user_permission')->where('userid', $userId)->whereIn('page', $remove)->delete();
        }

        foreach (array_diff($desired, $current) as $permission) {
            DB::table('user_permission')->insert([
                'userid' => $userId,
                'pageid' => 0,
                'page' => $permission,
                'permit' => '2',
            ]);
        }
    }

    public function activeSuperAdministratorCount(): int
    {
        return User::query()->where('hr_user_level', '3')->where('activated', '1')->count();
    }
}
