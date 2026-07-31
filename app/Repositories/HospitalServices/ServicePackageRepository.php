<?php

namespace App\Repositories\HospitalServices;

use App\Models\ServicePackage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ServicePackageRepository
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
        'consultation_discount',
        'lab_x_rays_discount',
        'operations_hypnosis_discount',
        'delivery_discount',
    ];

    public function paginatePublishedBySection(int $sectionId, string $search, int $perPage): LengthAwarePaginator
    {
        $nameColumn = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';
        $companyGroupId = (int) session('companies_groups_id', 0);

        return ServicePackage::query()
            ->select(self::LIST_COLUMNS)
            ->where('publish', '1')
            ->where('service_id', $sectionId)
            ->when($companyGroupId > 0, fn (Builder $query) => $query->where('companies_groups_id', $companyGroupId))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('code1', 'like', '%'.$search.'%')
                        ->orWhere('name_ar', 'like', '%'.$search.'%')
                        ->orWhere('name_en', 'like', '%'.$search.'%');
                });
            })
            ->orderBy($nameColumn)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function countPublishedBySection(int $sectionId): int
    {
        $companyGroupId = (int) session('companies_groups_id', 0);

        return ServicePackage::query()
            ->where('publish', '1')
            ->where('service_id', $sectionId)
            ->when($companyGroupId > 0, fn (Builder $query) => $query->where('companies_groups_id', $companyGroupId))
            ->count();
    }
}
