<?php

namespace App\Services\DoctorsDirectory;

use App\Data\SearchableSelectOption;
use App\Models\Clinician;
use App\Models\OutpatientClinicSection;
use App\Repositories\DoctorsDirectory\DepartmentRepository;
use App\Repositories\DoctorsDirectory\DoctorRepository;
use App\Repositories\DoctorsDirectory\SpecialityRepository;
use App\Support\LocaleText;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DoctorService
{
    public function __construct(
        private readonly DepartmentRepository $departments,
        private readonly DoctorRepository $doctors,
        private readonly SpecialityRepository $specialities,
    ) {}

    /**
     * @return array{speciality: \App\Models\Speciality, hospital: object, branchLabel: string}|null
     */
    public function findBranchDoctorsContext(int $specialityId, int $hospitalId): ?array
    {
        $hospital = $this->specialities->findPublishedHospital($hospitalId);

        if ($hospital === null) {
            return null;
        }

        $speciality = $this->specialities->findPublishedWithDescription($specialityId);

        if ($speciality === null) {
            return null;
        }

        $branchLabel = LocaleText::localizedField(
            isset($hospital->name_ar) ? (string) $hospital->name_ar : null,
            isset($hospital->name_en) ? (string) $hospital->name_en : null,
        );

        return [
            'speciality' => $speciality,
            'hospital' => $hospital,
            'branchLabel' => $branchLabel,
        ];
    }

    public function listBySpecialityAndHospital(
        int $specialityId,
        ?int $hospitalId,
        string $name,
        string $code,
        bool $allBranches = false,
    ): LengthAwarePaginator {
        $perPage = (int) config('hm.doctors_directory.per_page', 12);

        return $this->doctors->paginateBySpecialityAndHospital(
            specialityId: $specialityId,
            hospitalId: $hospitalId,
            name: $name,
            code: $code,
            perPage: $perPage,
            allBranches: $allBranches,
        );
    }

    /**
     * @return array{
     *     clinics: list<SearchableSelectOption>,
     *     branches: list<SearchableSelectOption>
     * }
     */
    public function branchDoctorsFilterOptions(int $specialityId, int $hospitalId, bool $allBranches): array
    {
        $clinicOptions = [];
        $branchOptions = [];

        foreach ($this->specialities->listAllPublished() as $speciality) {
            $clinicOptions[] = new SearchableSelectOption(
                value: (string) $speciality->id,
                label: $speciality->localizedName(),
                url: route('modules.doctors.branches.doctors.index', [
                    'speciality' => $speciality->id,
                    'hospital' => $hospitalId,
                ]).($allBranches ? '?all=1' : ''),
            );
        }

        foreach ($this->specialities->publishedHospitalsForSpeciality($specialityId) as $hospital) {
            $branchOptions[] = new SearchableSelectOption(
                value: (string) $hospital->id,
                label: LocaleText::localizedField(
                    isset($hospital->name_ar) ? (string) $hospital->name_ar : null,
                    isset($hospital->name_en) ? (string) $hospital->name_en : null,
                ),
                url: route('modules.doctors.branches.doctors.index', [
                    'speciality' => $specialityId,
                    'hospital' => $hospital->id,
                ]).($allBranches ? '?all=1' : ''),
            );
        }

        return [
            'clinics' => $clinicOptions,
            'branches' => $branchOptions,
        ];
    }

    public function findDepartment(int $departmentId, int $specialityId): ?OutpatientClinicSection
    {
        return $this->departments->findPublishedForSpeciality($departmentId, $specialityId);
    }

    /**
     * @return array{opd: \App\Models\OutpatientClinic, speciality: \App\Models\Speciality}|null
     */
    public function findOpdSpeciality(int $opdId, int $specialityId): ?array
    {
        return $this->departments->findPublishedOpdSpeciality($opdId, $specialityId);
    }

    public function listByDepartment(
        int $specialityId,
        int $departmentId,
        string $name,
        string $code,
        string $specialization,
        ?int $hospitalId = null,
        bool $scopedToHospital = false,
    ): LengthAwarePaginator {
        $perPage = (int) config('hm.doctors_directory.per_page', 12);
        $resolvedHospitalId = $hospitalId ?? $this->resolveHospitalId();

        $paginator = $this->doctors->paginateByDepartment(
            specialityId: $specialityId,
            departmentId: $departmentId,
            name: $name,
            code: $code,
            specialization: $specialization,
            perPage: $perPage,
            hospitalId: $resolvedHospitalId,
        );

        if ($this->shouldRetryWithoutHospitalFilter($paginator, $resolvedHospitalId, $name, $code, $specialization, $scopedToHospital)) {
            return $this->doctors->paginateByDepartment(
                specialityId: $specialityId,
                departmentId: $departmentId,
                name: $name,
                code: $code,
                specialization: $specialization,
                perPage: $perPage,
                hospitalId: null,
            );
        }

        return $paginator;
    }

    public function listByOpdSpeciality(
        int $specialityId,
        int $opdId,
        string $name,
        string $code,
        string $specialization,
        ?int $hospitalId = null,
        bool $scopedToHospital = false,
    ): LengthAwarePaginator {
        $perPage = (int) config('hm.doctors_directory.per_page', 12);
        $resolvedHospitalId = $hospitalId ?? $this->resolveHospitalId();

        $paginator = $this->doctors->paginateByOpdSpeciality(
            specialityId: $specialityId,
            opdId: $opdId,
            name: $name,
            code: $code,
            specialization: $specialization,
            perPage: $perPage,
            hospitalId: $resolvedHospitalId,
        );

        if ($this->shouldRetryWithoutHospitalFilter($paginator, $resolvedHospitalId, $name, $code, $specialization, $scopedToHospital)) {
            return $this->doctors->paginateByOpdSpeciality(
                specialityId: $specialityId,
                opdId: $opdId,
                name: $name,
                code: $code,
                specialization: $specialization,
                perPage: $perPage,
                hospitalId: null,
            );
        }

        return $paginator;
    }

    private function resolveHospitalId(): ?int
    {
        $hospitalId = session('companies_groups_id');

        return is_numeric($hospitalId) && (int) $hospitalId > 0 ? (int) $hospitalId : null;
    }

    /**
     * @return array{
     *     clinics: list<SearchableSelectOption>,
     *     branches: list<SearchableSelectOption>
     * }
     */
    public function departmentDoctorsFilterOptions(
        int $specialityId,
        int $departmentId,
        int $hospitalId,
        bool $allBranches,
    ): array {
        $querySuffix = $allBranches ? '?all=1' : '';

        $clinicOptions = [];
        foreach ($this->specialities->listAllPublished() as $speciality) {
            $clinicOptions[] = new SearchableSelectOption(
                value: (string) $speciality->id,
                label: $speciality->localizedName(),
                url: route('modules.doctors.departments.doctors.index', [
                    'speciality' => $speciality->id,
                    'department' => $departmentId,
                    'hospital' => $hospitalId,
                ]).$querySuffix,
            );
        }

        $branchOptions = [];
        foreach ($this->specialities->publishedHospitalsForSpeciality($specialityId) as $hospital) {
            $branchOptions[] = new SearchableSelectOption(
                value: (string) $hospital->id,
                label: LocaleText::localizedField(
                    isset($hospital->name_ar) ? (string) $hospital->name_ar : null,
                    isset($hospital->name_en) ? (string) $hospital->name_en : null,
                ),
                url: route('modules.doctors.departments.doctors.index', [
                    'speciality' => $specialityId,
                    'department' => $departmentId,
                    'hospital' => $hospital->id,
                ]).$querySuffix,
            );
        }

        return [
            'clinics' => $clinicOptions,
            'branches' => $branchOptions,
        ];
    }

    private function shouldRetryWithoutHospitalFilter(
        LengthAwarePaginator $paginator,
        ?int $hospitalId,
        string $name,
        string $code,
        string $specialization,
        bool $scopedToHospital = false,
    ): bool {
        if ($scopedToHospital) {
            return false;
        }

        return $hospitalId !== null
            && $paginator->total() === 0
            && $name === ''
            && $code === ''
            && $specialization === '';
    }

    public function findDoctor(int $id): ?Clinician
    {
        return $this->doctors->findPublished($id);
    }

    public function findDoctorDetail(int $id): ?Clinician
    {
        return $this->doctors->findPublishedForDetail($id);
    }
}
