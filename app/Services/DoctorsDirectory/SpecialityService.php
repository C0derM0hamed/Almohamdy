<?php

namespace App\Services\DoctorsDirectory;

use App\Data\ClinicDoctorsIndexSummary;
use App\Data\DoctorsDirectoryNavigationCard;
use App\Data\HospitalBranchCard;
use App\Models\Speciality;
use App\Repositories\DoctorsDirectory\SpecialityRepository;
use App\Support\DoctorsDirectory\BranchImage;
use App\Support\DoctorsDirectory\SpecialityDescription;
use App\Support\DoctorsDirectory\SpecialityIcon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SpecialityService
{
    public function __construct(
        private readonly SpecialityRepository $specialities,
    ) {}

    public function listPaginated(string $search): LengthAwarePaginator
    {
        $perPage = (int) config('hm.doctors_directory.per_page', 12);

        return $this->specialities->paginatePublished($search, $perPage);
    }

    /**
     * @return array{cards: list<DoctorsDirectoryNavigationCard>, summary: ClinicDoctorsIndexSummary}
     */
    public function indexPageData(): array
    {
        $availableTodayBySpeciality = $this->specialities->availableTodayDoctorCountsBySpeciality();
        $cards = [];
        $totalDoctors = 0;
        $mostBookedSpeciality = null;
        $mostBookedCount = -1;

        foreach ($this->specialities->listAllPublished() as $speciality) {
            $doctorCount = (int) ($speciality->doctors_count ?? 0);
            $availableTodayCount = (int) ($availableTodayBySpeciality[$speciality->id] ?? 0);
            $totalDoctors += $doctorCount;

            if ($doctorCount > $mostBookedCount) {
                $mostBookedCount = $doctorCount;
                $mostBookedSpeciality = $speciality->localizedName();
            }

            $cards[] = new DoctorsDirectoryNavigationCard(
                id: (int) $speciality->id,
                title: $speciality->localizedName(),
                url: route('modules.doctors.specialities.departments', $speciality->id),
                nameAr: trim((string) ($speciality->subject_ar ?: $speciality->subject_en)),
                nameEn: trim((string) ($speciality->subject_en ?: $speciality->subject_ar)),
                icon: SpecialityIcon::for($speciality),
                doctorCount: $doctorCount,
                availableTodayCount: $availableTodayCount,
                iconSvg: SpecialityIcon::svgFor($speciality),
            );
        }

        return [
            'cards' => $cards,
            'summary' => new ClinicDoctorsIndexSummary(
                totalSpecialities: count($cards),
                totalDoctors: $totalDoctors,
                availableToday: $this->specialities->countPublishedDoctorsAvailableToday(),
                mostBookedSpeciality: $mostBookedCount > 0 ? $mostBookedSpeciality : null,
            ),
        ];
    }

    /**
     * @return array{
     *     speciality: Speciality,
     *     description: ?string,
     *     hospitals: list<HospitalBranchCard>,
     *     selectedHospital: object|null,
     *     showBranchPicker: bool
     * }|null
     */
    public function findForDepartmentsPage(int $id, ?int $hospitalId): ?array
    {
        $hospitals = $this->specialities->publishedHospitalsForSpeciality($id);
        $selectedHospital = ($hospitalId !== null && $hospitalId > 0)
            ? $this->specialities->findPublishedHospital($hospitalId)
            : null;

        if ($selectedHospital === null && $hospitalId !== null && $hospitalId > 0) {
            return null;
        }

        // Show the overview page (description + branches) whenever the user
        // has not picked a specific branch and at least one branch exists,
        // mirroring the legacy clinic_details.php layout.
        $showBranchPicker = $selectedHospital === null && $hospitals->count() >= 1;

        if ($showBranchPicker) {
            $speciality = $this->specialities->findPublishedWithDescription($id);

            if ($speciality === null) {
                return null;
            }

            return [
                'speciality' => $speciality,
                'description' => SpecialityDescription::localized($speciality),
                'overviewContent' => SpecialityDescription::overviewContent($speciality),
                'hospitals' => $this->branchCards($speciality->id, $hospitals),
                'selectedHospital' => null,
                'showBranchPicker' => true,
            ];
        }

        $effectiveHospitalId = $selectedHospital->id ?? ($hospitals->count() === 1 ? (int) $hospitals->first()->id : null);

        $speciality = $this->specialities->findPublishedForDepartments($id, $effectiveHospitalId);

        if ($speciality === null) {
            return null;
        }

        return [
            'speciality' => $speciality,
            'description' => SpecialityDescription::localized($speciality),
            'hospitals' => $this->branchCards($speciality->id, $hospitals),
            'selectedHospital' => $selectedHospital ?? ($hospitals->count() === 1 ? $hospitals->first() : null),
            'showBranchPicker' => false,
        ];
    }

    public function findForDepartments(int $id, ?int $hospitalId = null): ?Speciality
    {
        return $this->specialities->findPublishedForDepartments($id, $hospitalId);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object{id: int, name_ar: string|null, name_en: string|null}>  $hospitals
     * @return list<HospitalBranchCard>
     */
    private function branchCards(int $specialityId, \Illuminate\Support\Collection $hospitals): array
    {
        $doctorCounts = $this->specialities->publishedDoctorCountsByHospital($specialityId);
        $cards = [];

        foreach ($hospitals as $hospital) {
            $hospitalId = (int) $hospital->id;
            $nameAr = trim((string) ($hospital->name_ar ?: $hospital->name_en));
            $nameEn = trim((string) ($hospital->name_en ?: $hospital->name_ar));
            $title = \App\Support\LocaleText::localizedField($nameAr, $nameEn);

            $cards[] = new HospitalBranchCard(
                id: $hospitalId,
                title: $title,
                url: route('modules.doctors.branches.doctors.index', [
                    'speciality' => $specialityId,
                    'hospital' => $hospitalId,
                ]),
                nameAr: $nameAr,
                nameEn: $nameEn,
                doctorCount: (int) ($doctorCounts[$hospitalId] ?? 0),
                imageUrl: BranchImage::urlForHospital($hospitalId),
            );
        }

        return $cards;
    }
}
