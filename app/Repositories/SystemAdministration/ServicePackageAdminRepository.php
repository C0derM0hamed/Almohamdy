<?php

namespace App\Repositories\SystemAdministration;

use App\Models\ServicePackage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ServicePackageAdminRepository
{
    /**
     * @var list<string>
     */
    private const LIST_COLUMNS = [
        'id',
        'service_id',
        'code1',
        'name_ar',
        'name_en',
        'price',
        'publish',
        'companies_groups_id',
        'updated_at',
    ];

    /**
     * @var list<string>
     */
    private const FORM_COLUMNS = [
        'id',
        'service_id',
        'code1',
        'name_ar',
        'name_en',
        'price',
        'publish',
        'companies_groups_id',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];

    public function baseQuery(): Builder
    {
        return ServicePackage::query()->select(self::LIST_COLUMNS);
    }

    /**
     * @return array{total: int, published: int, unpublished: int}
     */
    public function dashboardCounts(?int $companyGroupId): array
    {
        $query = ServicePackage::query()
            ->when($companyGroupId !== null && $companyGroupId > 0, fn (Builder $q) => $q->where('companies_groups_id', $companyGroupId));

        $total = (clone $query)->count();
        $published = (clone $query)->where('publish', '1')->count();

        return [
            'total' => $total,
            'published' => $published,
            'unpublished' => max(0, $total - $published),
        ];
    }

    public function paginateFiltered(
        string $search,
        ?int $sectionId,
        ?string $publish,
        ?int $companyGroupId,
        int $perPage,
    ): LengthAwarePaginator {
        $nameColumn = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';

        return $this->baseQuery()
            ->when($companyGroupId !== null && $companyGroupId > 0, fn (Builder $query) => $query->where('companies_groups_id', $companyGroupId))
            ->when($sectionId !== null && $sectionId > 0, fn (Builder $query) => $query->where('service_id', $sectionId))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('code1', 'like', '%'.$search.'%')
                        ->orWhere('name_ar', 'like', '%'.$search.'%')
                        ->orWhere('name_en', 'like', '%'.$search.'%');
                });
            })
            ->when($publish !== null && $publish !== '', function (Builder $query) use ($publish) {
                $query->where('publish', $publish === '1' ? '1' : '0');
            })
            ->with(['section:id,name_ar,name_en'])
            ->orderBy('service_id')
            ->orderBy($nameColumn)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForEdit(int $id, ?int $companyGroupId): ?ServicePackage
    {
        return ServicePackage::query()
            ->select(self::FORM_COLUMNS)
            ->with(['section:id,name_ar,name_en'])
            ->whereKey($id)
            ->when($companyGroupId !== null && $companyGroupId > 0, fn (Builder $query) => $query->where('companies_groups_id', $companyGroupId))
            ->first();
    }

    /**
     * @param  array{code1: string, name_ar: string, name_en: string, price: string, publish: string, updated_by: int}  $attributes
     */
    public function update(ServicePackage $package, array $attributes): ServicePackage
    {
        $package->code1 = $attributes['code1'];
        $package->name_ar = $attributes['name_ar'];
        $package->name_en = $attributes['name_en'];
        $package->price = $attributes['price'];
        $package->publish = $attributes['publish'];
        $package->updated_by = $attributes['updated_by'];
        $package->save();

        return $package;
    }

    public function togglePublish(ServicePackage $package, int $updatedBy): ServicePackage
    {
        $package->publish = $package->publish === '1' ? '0' : '1';
        $package->updated_by = $updatedBy;
        $package->save();

        return $package;
    }

    public function delete(ServicePackage $package): void
    {
        $package->delete();
    }
}
