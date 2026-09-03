<?php

namespace App\Repositories\SystemAdministration;

use App\Models\ServicePackage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

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
        'notice_ar',
        'notice_en',
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
        'notice_ar',
        'notice_en',
        'price',
        'publish',
        'companies_groups_id',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'notice1_ar',
        'notice1_en',
        'service_details',
        'consultation_discount',
        'lab_x_rays_discount',
        'operations_hypnosis_discount',
        'delivery_discount',
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

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): ServicePackage
    {
        $package = new ServicePackage;
        $this->applyAttributes($package, $attributes);
        $package->save();

        return $package;
    }

    /** @param array<string, mixed> $attributes */
    public function update(ServicePackage $package, array $attributes): ServicePackage
    {
        $this->applyAttributes($package, $attributes);
        $package->save();

        return $package;
    }

    /** @param array<string, mixed> $attributes */
    private function applyAttributes(ServicePackage $package, array $attributes): void
    {
        $columns = Schema::getColumnListing('service_packages');
        foreach ($attributes as $column => $value) {
            if (in_array($column, $columns, true)) {
                $package->{$column} = is_string($value) ? trim($value) : $value;
            }
        }
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
