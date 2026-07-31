<?php

namespace App\Repositories\HospitalServices;

use App\Models\ServicePackage;
use App\Models\ServiceSection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ServiceSectionRepository
{
    /**
     * @var list<string>
     */
    private const SECTION_COLUMNS = [
        'id',
        'name_ar',
        'name_en',
        'publish',
    ];

    /**
     * @var list<string>
     */
    private const PACKAGE_COLUMNS = [
        'id',
        'service_id',
        'code1',
        'name_ar',
        'name_en',
        'notice_ar',
        'notice_en',
        'notice1_ar',
        'notice1_en',
        'service_details',
        'price',
        'publish',
        'companies_groups_id',
        'consultation_discount',
        'lab_x_rays_discount',
        'operations_hypnosis_discount',
        'delivery_discount',
    ];

    /**
     * @return Collection<int, ServiceSection>
     */
    public function listForNavigation(): Collection
    {
        $order = config('hm.hospital_services.section_nav_order');

        if (! is_array($order) || $order === []) {
            return $this->listPublished();
        }

        $sections = ServiceSection::query()
            ->select(self::SECTION_COLUMNS)
            ->whereIn('id', $order)
            ->get()
            ->keyBy('id');

        $ordered = [];

        foreach ($order as $id) {
            $section = $sections->get((int) $id);

            if ($section !== null) {
                $ordered[] = $section;
            }
        }

        return new Collection($ordered);
    }

    /**
     * @return Collection<int, ServiceSection>
     */
    public function listPublished(): Collection
    {
        return ServiceSection::query()
            ->select(self::SECTION_COLUMNS)
            ->where('publish', '1')
            ->orderBy('id')
            ->get();
    }

    public function findPublished(int $id): ?ServiceSection
    {
        return ServiceSection::query()
            ->select(self::SECTION_COLUMNS)
            ->where('publish', '1')
            ->whereKey($id)
            ->first();
    }

    public function findForNavigation(int $id): ?ServiceSection
    {
        if ($this->isNavigationSectionId($id)) {
            return ServiceSection::query()
                ->select(self::SECTION_COLUMNS)
                ->whereKey($id)
                ->first();
        }

        return $this->findPublished($id);
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, ServiceSection>
     */
    public function findByIds(array $ids): Collection
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

        if ($ids === []) {
            return new Collection;
        }

        return ServiceSection::query()
            ->select(self::SECTION_COLUMNS)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }

    /**
     * @return list<int>
     */
    public function navigationSectionIds(): array
    {
        $order = config('hm.hospital_services.section_nav_order', []);
        $childIds = collect(config('hm.hospital_services.section_nav_children', []))
            ->flatten()
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique(array_merge(
            is_array($order) ? array_map('intval', $order) : [],
            $childIds,
        )));
    }

    private function isNavigationSectionId(int $id): bool
    {
        return in_array($id, $this->navigationSectionIds(), true);
    }

    /**
     * @return array{top: Collection<int, ServiceSection>, children: Collection<int, ServiceSection>}
     */
    public function navigationTree(): array
    {
        $top = $this->listForNavigation();
        $childIds = collect(config('hm.hospital_services.section_nav_children', []))
            ->flatten()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        return [
            'top' => $top,
            'children' => $this->findByIds($childIds),
        ];
    }

    public function resolvePackageSectionId(int $sectionId): int
    {
        $aliases = config('hm.hospital_services.section_content_aliases', []);

        return (int) ($aliases[$sectionId] ?? $sectionId);
    }

    public function paginatePublishedPackages(int $sectionId, string $search, int $perPage, bool $withAttachments = false): LengthAwarePaginator
    {
        $companyGroupId = (int) session('companies_groups_id', 0);

        return ServicePackage::query()
            ->select(self::PACKAGE_COLUMNS)
            ->when($withAttachments, fn (Builder $query) => $query->with([
                'attachments' => fn ($relation) => $relation->select('id', 'service_packages_id', 'file_name'),
            ]))
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
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function countPublishedPackages(int $sectionId): int
    {
        return $this->countPublishedPackagesGrouped([$sectionId])[$sectionId] ?? 0;
    }

    /**
     * @param  list<int>  $sectionIds
     * @return array<int, int>
     */
    public function countPublishedPackagesGrouped(array $sectionIds): array
    {
        $sectionIds = array_values(array_unique(array_filter(array_map('intval', $sectionIds))));

        if ($sectionIds === []) {
            return [];
        }

        $companyGroupId = (int) session('companies_groups_id', 0);

        return ServicePackage::query()
            ->selectRaw('service_id, COUNT(*) as packages_count')
            ->where('publish', '1')
            ->whereIn('service_id', $sectionIds)
            ->when($companyGroupId > 0, fn (Builder $query) => $query->where('companies_groups_id', $companyGroupId))
            ->groupBy('service_id')
            ->pluck('packages_count', 'service_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }
}
