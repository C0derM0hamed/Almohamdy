<?php

namespace App\Services\SystemAdministration;

use App\Models\ServicePackage;
use App\Repositories\SystemAdministration\ServicePackageAdminRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ServicePackageAdminService
{
    public function __construct(
        private readonly ServicePackageAdminRepository $repository,
    ) {}

    /**
     * @return array{total: int, published: int, unpublished: int}
     */
    public function dashboardSummary(): array
    {
        return $this->repository->dashboardCounts($this->companyGroupId());
    }

    public function listPaginated(string $search, ?int $sectionId, ?string $publish): LengthAwarePaginator
    {
        $perPage = (int) config('hm.system_administration.per_page', 15);

        return $this->repository->paginateFiltered(
            $search,
            $sectionId,
            $publish,
            $this->companyGroupId(),
            $perPage,
        );
    }

    public function findForEdit(int $id): ?ServicePackage
    {
        return $this->repository->findForEdit($id, $this->companyGroupId());
    }

    /**
     * @return array<int, string>
     */
    public function sectionOptions(): array
    {
        $options = [];
        $labels = config('hm.hospital_services.section_nav_labels', []);

        if (! is_array($labels) || $labels === []) {
            return $options;
        }

        $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
        $order = config('hm.hospital_services.section_nav_order', []);
        $children = config('hm.hospital_services.section_nav_children', []);

        $ids = [];

        if (is_array($order)) {
            foreach ($order as $id) {
                $ids[] = (int) $id;

                if (is_array($children) && isset($children[$id]) && is_array($children[$id])) {
                    foreach ($children[$id] as $childId) {
                        $ids[] = (int) $childId;
                    }
                }
            }
        }

        foreach (array_keys($labels) as $id) {
            $ids[] = (int) $id;
        }

        foreach (array_unique($ids) as $id) {
            $entry = $labels[$id] ?? null;

            if (! is_array($entry)) {
                continue;
            }

            $label = trim((string) ($entry[$locale] ?? $entry['ar'] ?? $entry['en'] ?? ''));

            if ($label !== '') {
                $options[$id] = $label;
            }
        }

        return $options;
    }

    public function update(
        ServicePackage $package,
        string $code,
        string $nameAr,
        string $nameEn,
        string $price,
        string $publish,
    ): ServicePackage {
        return $this->repository->update($package, [
            'code1' => $code,
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            'price' => $price,
            'publish' => $publish,
            'updated_by' => (int) session('hr_user_id', 0),
        ]);
    }

    public function togglePublish(ServicePackage $package): ServicePackage
    {
        return $this->repository->togglePublish(
            $package,
            (int) session('hr_user_id', 0),
        );
    }

    public function delete(ServicePackage $package): void
    {
        $this->repository->delete($package);
    }

    private function companyGroupId(): ?int
    {
        $groupId = (int) session('companies_groups_id', 0);

        return $groupId > 0 ? $groupId : null;
    }
}
