<?php

namespace App\Services\HospitalServices;

use App\Data\SearchableSelectOption;
use App\Models\ServiceSection;
use App\Repositories\HospitalServices\AdmissionRoomRepository;
use App\Repositories\HospitalServices\ServicePackageRepository;
use App\Repositories\HospitalServices\ServiceSectionRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class HospitalServiceCatalogService
{
    public function __construct(
        private readonly ServicePackageRepository $packageRepository,
        private readonly ServiceSectionRepository $sectionRepository,
        private readonly AdmissionRoomRepository $roomRepository,
    ) {}

    /**
     * @return array{lab: int, radiology: int, rooms: int, agreements: int}
     */
    public function dashboardSummary(): array
    {
        $sections = config('hm.hospital_services.sections', []);
        $labId = (int) ($sections['lab'] ?? 0);
        $radiologyId = (int) ($sections['radiology'] ?? 0);
        $agreementsId = (int) ($sections['agreements'] ?? 0);

        $counts = $this->sectionRepository->countPublishedPackagesGrouped([
            $labId,
            $radiologyId,
            $agreementsId,
        ]);

        return [
            'lab' => $counts[$labId] ?? 0,
            'radiology' => $counts[$radiologyId] ?? 0,
            'rooms' => $this->roomRepository->countPublished(),
            'agreements' => $counts[$agreementsId] ?? 0,
        ];
    }

    public function listLabPackages(string $search): LengthAwarePaginator
    {
        return $this->listPackagesBySection('lab', $search);
    }

    public function listRadiologyPackages(string $search): LengthAwarePaginator
    {
        return $this->listPackagesBySection('radiology', $search);
    }

    public function listAgreementPackages(string $search): LengthAwarePaginator
    {
        return $this->listPackagesBySection('agreements', $search);
    }

    public function listPrivateRooms(string $search): LengthAwarePaginator
    {
        $perPage = (int) config('hm.hospital_services.per_page', 15);

        return $this->roomRepository->paginatePublished($search, $perPage);
    }

    /**
     * @return list<array{section: ServiceSection, count: int, children?: list<array{section: ServiceSection, count: int}>}>
     */
    public function navigationSectionCards(): array
    {
        $tree = $this->sectionRepository->navigationTree();

        if ($tree['top']->isEmpty()) {
            return [];
        }

        $countsByPackageSection = $this->sectionRepository->countPublishedPackagesGrouped(
            $this->collectPackageSectionIds($tree['top'])
        );

        $simpleCards = [];
        $groupCards = [];

        foreach ($tree['top'] as $section) {
            $children = $this->childNavigationCards((int) $section->id, $tree['children'], $countsByPackageSection);

            if ($children !== []) {
                $groupCards[] = [
                    'section' => $section,
                    'count' => 0,
                    'children' => $children,
                ];

                continue;
            }

            $packageSectionId = $this->sectionRepository->resolvePackageSectionId((int) $section->id);

            $simpleCards[] = [
                'section' => $section,
                'count' => $countsByPackageSection[$packageSectionId] ?? 0,
            ];
        }

        return [...$simpleCards, ...$groupCards];
    }

    public function findSection(int $id): ?ServiceSection
    {
        return $this->sectionRepository->findForNavigation($id);
    }

    public function listSectionPackages(int $sectionId, string $search): LengthAwarePaginator
    {
        $perPage = (int) config('hm.hospital_services.per_page', 12);
        $packageSectionId = $this->sectionRepository->resolvePackageSectionId($sectionId);

        return $this->sectionRepository->paginatePublishedPackages(
            $packageSectionId,
            $search,
            $perPage,
            $this->isRoomSection($sectionId),
        );
    }

    public function isRoomSection(int $sectionId): bool
    {
        $roomSections = array_map('intval', (array) config('hm.hospital_services.room_sections', [7]));
        $packageSectionId = $this->sectionRepository->resolvePackageSectionId($sectionId);

        return in_array($sectionId, $roomSections, true)
            || in_array($packageSectionId, $roomSections, true);
    }

    public function isAgreementSection(int $sectionId): bool
    {
        return $sectionId === (int) config('hm.hospital_services.sections.agreements', 0);
    }

    public function sectionCardLayout(int $sectionId): string
    {
        if ($this->isAgreementSection($sectionId)) {
            return 'agreements';
        }

        $diagnosticsSections = [
            (int) config('hm.hospital_services.sections.lab', 0),
            (int) config('hm.hospital_services.sections.radiology', 0),
        ];

        if (in_array($sectionId, $diagnosticsSections, true)) {
            return 'diagnostics';
        }

        if ($this->isRoomSection($sectionId)) {
            return 'rooms';
        }

        return 'standard';
    }

    /**
     * @return list<SearchableSelectOption>
     */
    public function sectionFilterOptions(int $selectedSectionId): array
    {
        $tree = $this->sectionRepository->navigationTree();
        $options = [];

        foreach ($tree['top'] as $section) {
            $children = $this->childFilterOptions((int) $section->id, $tree['children']);

            $options[] = new SearchableSelectOption(
                value: (string) $section->id,
                label: $section->legacyNavName(),
                url: route('modules.services.sections.show', $section->id),
                children: $children,
            );
        }

        return $options;
    }

    /**
     * @param  Collection<int, ServiceSection>  $sections
     * @return list<int>
     */
    private function collectPackageSectionIds(Collection $sections): array
    {
        $ids = [];

        foreach ($sections as $section) {
            $childIds = config('hm.hospital_services.section_nav_children.'.(int) $section->id, []);

            if (is_array($childIds) && $childIds !== []) {
                foreach ($childIds as $childId) {
                    $ids[] = $this->sectionRepository->resolvePackageSectionId((int) $childId);
                }

                continue;
            }

            $ids[] = $this->sectionRepository->resolvePackageSectionId((int) $section->id);
        }

        return $ids;
    }

    /**
     * @param  Collection<int, ServiceSection>  $childSections
     * @param  array<int, int>  $countsByPackageSection
     * @return list<array{section: ServiceSection, count: int}>
     */
    private function childNavigationCards(int $parentSectionId, Collection $childSections, array $countsByPackageSection): array
    {
        $childIds = config('hm.hospital_services.section_nav_children.'.$parentSectionId, []);

        if (! is_array($childIds) || $childIds === []) {
            return [];
        }

        $cards = [];

        foreach ($childIds as $childId) {
            $section = $childSections->get((int) $childId);

            if ($section === null || ! $section->isPublished()) {
                continue;
            }

            $packageSectionId = $this->sectionRepository->resolvePackageSectionId((int) $section->id);

            $cards[] = [
                'section' => $section,
                'count' => $countsByPackageSection[$packageSectionId] ?? 0,
            ];
        }

        return $cards;
    }

    /**
     * @param  Collection<int, ServiceSection>  $childSections
     * @return list<SearchableSelectOption>
     */
    private function childFilterOptions(int $parentSectionId, Collection $childSections): array
    {
        $childIds = config('hm.hospital_services.section_nav_children.'.$parentSectionId, []);

        if (! is_array($childIds) || $childIds === []) {
            return [];
        }

        $options = [];

        foreach ($childIds as $childId) {
            $section = $childSections->get((int) $childId);

            if ($section === null || ! $section->isPublished()) {
                continue;
            }

            $options[] = new SearchableSelectOption(
                value: (string) $section->id,
                label: $section->legacyNavName(),
                url: route('modules.services.sections.show', $section->id),
            );
        }

        return $options;
    }

    private function listPackagesBySection(string $sectionKey, string $search): LengthAwarePaginator
    {
        $sectionId = (int) config('hm.hospital_services.sections.'.$sectionKey, 0);
        $perPage = (int) config('hm.hospital_services.per_page', 15);

        return $this->packageRepository->paginatePublishedBySection($sectionId, $search, $perPage);
    }
}
