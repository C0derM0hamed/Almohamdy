<?php

namespace App\Services\DoctorsDirectoryAdmin;

use App\Models\OutpatientClinic;
use App\Models\OutpatientClinicSection;
use App\Models\Speciality;
use App\Repositories\DoctorsDirectoryAdmin\DepartmentRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DepartmentService
{
    public function __construct(
        private readonly DepartmentRepository $repository,
    ) {}

    public function listPaginated(string $search, ?int $specialityId, ?string $publish): LengthAwarePaginator
    {
        $perPage = (int) config('hm.doctors_directory_admin.per_page', 15);

        return $this->repository->paginateFiltered($search, $specialityId, $publish, $perPage);
    }

    public function findForEdit(int $id): ?OutpatientClinicSection
    {
        return $this->repository->findForEdit($id);
    }

    /**
     * @return array<int, string>
     */
    public function specialityOptions(): array
    {
        $nameColumn = app()->getLocale() === 'ar' ? 'subject_ar' : 'subject_en';
        $options = [];

        Speciality::query()
            ->select(['id', 'subject_ar', 'subject_en'])
            ->orderBy($nameColumn)
            ->each(function (Speciality $speciality) use (&$options) {
                $options[(int) $speciality->id] = $speciality->localizedName();
            });

        return $options;
    }

    /**
     * @return array<int, string>
     */
    public function departmentOptions(): array
    {
        $nameColumn = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';
        $options = [];

        OutpatientClinic::query()
            ->select(['id', 'name_ar', 'name_en'])
            ->orderBy($nameColumn)
            ->each(function (OutpatientClinic $clinic) use (&$options) {
                $options[(int) $clinic->id] = $clinic->localizedName();
            });

        return $options;
    }

    public function create(int $specialityId, int $departmentId, int $publish): OutpatientClinicSection
    {
        $userId = (int) session('hr_user_id', 0);

        return $this->repository->create([
            'specialized_clinics_id' => $specialityId,
            'outpatient_clinics_id' => $departmentId,
            'publish' => $publish,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    public function update(OutpatientClinicSection $section, int $specialityId, int $departmentId, int $publish): OutpatientClinicSection
    {
        return $this->repository->update($section, [
            'specialized_clinics_id' => $specialityId,
            'outpatient_clinics_id' => $departmentId,
            'publish' => $publish,
            'updated_by' => (int) session('hr_user_id', 0),
        ]);
    }

    public function togglePublish(OutpatientClinicSection $section): OutpatientClinicSection
    {
        return $this->repository->togglePublish($section, (int) session('hr_user_id', 0));
    }

    public function existsDuplicate(int $specialityId, int $departmentId, ?int $ignoreId = null): bool
    {
        return $this->repository->existsDuplicate($specialityId, $departmentId, $ignoreId);
    }
}

