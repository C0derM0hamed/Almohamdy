<?php

namespace App\Services\ServiceLocations;

use App\Data\ServiceLocationCard;
use App\Data\ServiceLocationDepartmentCard;
use App\Models\OutpatientClinic;
use App\Repositories\ServiceLocations\ServiceLocationRepository;
use App\Support\ServiceLocations\LocalizedLegacyText;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class ServiceLocationService
{
    public function __construct(
        private readonly ServiceLocationRepository $repository,
    ) {}

    /**
     * @return list<ServiceLocationCard>
     */
    public function navigationCards(): array
    {
        $cards = [];

        foreach ($this->repository->publishedLocations() as $location) {
            $departmentCards = $this->departmentCardsForLocation($location->id);
            $hasDuty = $this->repository->dutyInfoForLocation($location->id) !== null;

            if ($departmentCards === [] && ! $hasDuty) {
                continue;
            }

            $departmentCount = count($departmentCards);

            $cards[] = new ServiceLocationCard(
                title: $this->locationLabel($location),
                url: route('modules.service-locations.show', $location->id),
                description: __('service_locations.opd_card_description'),
                count: $departmentCount,
                countLabel: $departmentCount > 0
                    ? __('service_locations.departments_count', ['count' => $departmentCount])
                    : '',
                icon: 'bi-building',
            );
        }

        $floorCount = $this->floorsList()->count();

        $cards[] = new ServiceLocationCard(
            title: __('service_locations.floors_and_levels'),
            url: route('modules.service-locations.floors'),
            description: __('service_locations.floors_card_description'),
            count: $floorCount,
            countLabel: $floorCount > 0
                ? __('service_locations.floors_count', ['count' => $floorCount])
                : '',
            icon: 'bi-layers',
        );

        return $cards;
    }

    public function locationLabel(OutpatientClinic $location): string
    {
        if ($location->id <= 6) {
            return __('service_locations.opd_label', ['number' => $location->id]);
        }

        if ($location->id === 7) {
            return __('service_locations.opd_seven');
        }

        return $location->localizedName();
    }

    /**
     * @return array{
     *     location: OutpatientClinic,
     *     label: string,
     *     duty: object|null,
     *     departmentCards: list<ServiceLocationDepartmentCard>
     * }|null
     */
    public function findForShow(int $id): ?array
    {
        $location = $this->repository->findPublishedLocation($id);

        if ($location === null) {
            return null;
        }

        return [
            'location' => $location,
            'label' => $this->locationLabel($location),
            'duty' => $this->repository->dutyInfoForLocation($id),
            'departmentCards' => $this->departmentCardsForLocation($id),
        ];
    }

    /**
     * @return list<ServiceLocationDepartmentCard>
     */
    public function departmentCardsForLocation(int $id): array
    {
        $configuredEntries = $this->repository->configuredDepartmentEntries($id);

        if ($configuredEntries !== []) {
            return $this->departmentCardsFromConfiguredEntries($id, $configuredEntries);
        }

        return $this->departmentCardsFromDirectSections($id);
    }

    /**
     * @param  list<array{speciality_id?: int, label_key?: string}>  $entries
     * @return list<ServiceLocationDepartmentCard>
     */
    private function departmentCardsFromConfiguredEntries(int $opdId, array $entries): array
    {
        $cards = [];
        $specialityIds = [];

        foreach ($entries as $entry) {
            if (isset($entry['speciality_id'])) {
                $specialityIds[] = (int) $entry['speciality_id'];
            }
        }

        $specialities = $this->repository->findPublishedSpecialities($specialityIds);

        foreach ($entries as $entry) {
            if (isset($entry['label_key'])) {
                $cards[] = new ServiceLocationDepartmentCard(
                    title: __('service_locations.'.$entry['label_key']),
                );

                continue;
            }

            $specialityId = (int) ($entry['speciality_id'] ?? 0);
            $speciality = $specialities->get($specialityId);

            if ($speciality === null) {
                continue;
            }

            $cards[] = new ServiceLocationDepartmentCard(
                title: $speciality->localizedName(),
                url: route('modules.service-locations.opd.departments.doctors.index', [$opdId, $speciality->id]),
            );
        }

        $supportLabel = $this->repository->supportServiceLabelForLocation($opdId);

        if ($supportLabel !== null) {
            $cards[] = new ServiceLocationDepartmentCard(
                title: $supportLabel,
                url: route('modules.service-locations.opd.support-services', $opdId),
            );
        }

        return $cards;
    }

    /**
     * @return array{
     *     location: OutpatientClinic,
     *     label: string,
     *     items: SupportCollection<int, object>
     * }|null
     */
    public function findSupportServicesForOpd(int $opdId): ?array
    {
        $location = $this->repository->findPublishedLocation($opdId);

        if ($location === null) {
            return null;
        }

        $items = $this->repository->publishedSupportServicesForOpd($opdId);

        if ($items->isEmpty()) {
            return null;
        }

        return [
            'location' => $location,
            'label' => $this->locationLabel($location),
            'items' => $items,
        ];
    }

    /**
     * @return list<ServiceLocationDepartmentCard>
     */
    private function departmentCardsFromDirectSections(int $id): array
    {
        $cards = [];

        foreach ($this->repository->publishedSectionsForLocation($id) as $section) {
            $speciality = $section->speciality;
            $title = $speciality?->localizedName() ?? __('doctors_directory.department').' #'.$section->id;
            $url = $speciality
                ? route('modules.service-locations.opd.departments.doctors.index', [$id, $speciality->id])
                : null;

            $cards[] = new ServiceLocationDepartmentCard(title: $title, url: $url);
        }

        $supportLabel = $this->repository->supportServiceLabelForLocation($id);

        if ($supportLabel !== null) {
            $cards[] = new ServiceLocationDepartmentCard(
                title: $supportLabel,
                url: route('modules.service-locations.opd.support-services', $id),
            );
        }

        return $cards;
    }

    /**
     * @return SupportCollection<int, object>
     */
    public function floorsList(): SupportCollection
    {
        return $this->repository->publishedFloors();
    }

    public function localizedFloorName(?object $duty): string
    {
        if ($duty === null) {
            return '—';
        }

        return LocalizedLegacyText::floor(
            isset($duty->floor_name_en) ? (string) $duty->floor_name_en : null,
            isset($duty->floor_name_ar) ? (string) $duty->floor_name_ar : null,
        );
    }

    /**
     * @return array{
     *     floor: object,
     *     label: string,
     *     items: list<array{
     *         sectionName: string,
     *         dutyDays: string,
     *         dutyTime: string,
     *         visitTime: string,
     *         opdUrl: string|null
     *     }>
     * }|null
     */
    public function findFloorForShow(int $id): ?array
    {
        $floor = $this->repository->findPublishedFloor($id);

        if ($floor === null) {
            return null;
        }

        $items = [];

        foreach ($this->repository->publishedDetailsForFloor($id) as $detail) {
            $sectionName = trim((string) ($detail->section_name ?? ''));
            $opdId = $this->resolveOpdIdFromSectionName($sectionName);
            $opdUrl = null;

            if ($opdId !== null && $this->repository->findPublishedLocation($opdId) !== null) {
                $opdUrl = route('modules.service-locations.show', $opdId);
            }

            $items[] = [
                'sectionName' => LocalizedLegacyText::sectionName($sectionName),
                'dutyDays' => $this->localizedDutyDays($detail),
                'dutyTime' => $this->localizedDutyTime($detail),
                'visitTime' => LocalizedLegacyText::visitTime(isset($detail->visit_time) ? (string) $detail->visit_time : null),
                'opdUrl' => $opdUrl,
            ];
        }

        return [
            'floor' => $floor,
            'label' => $this->localizedFloorLabel($floor->name_en ?? null, $floor->name_ar ?? null),
            'items' => $items,
        ];
    }

    private function resolveOpdIdFromSectionName(string $sectionName): ?int
    {
        $normalized = trim($sectionName);

        if ($normalized === '') {
            return null;
        }

        if (preg_match('/العيادات\s+الخارجية\s*(\d+)/u', $normalized, $matches) === 1) {
            return (int) $matches[1];
        }

        if (preg_match('/O\.?\s*P\.?\s*D\.?\s*(\d+)/iu', $normalized, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    public function localizedDutyDays(?object $duty): string
    {
        return LocalizedLegacyText::dutyDays(isset($duty?->duty_days) ? (string) $duty->duty_days : null);
    }

    public function localizedDutyTime(?object $duty): string
    {
        return LocalizedLegacyText::dutyTime(isset($duty?->duty_time) ? (string) $duty->duty_time : null);
    }

    public function localizedFloorLabel(?string $nameEn, ?string $nameAr): string
    {
        return LocalizedLegacyText::floor($nameEn, $nameAr);
    }
}
