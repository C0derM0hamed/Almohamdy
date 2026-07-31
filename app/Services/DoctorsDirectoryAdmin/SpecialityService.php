<?php

namespace App\Services\DoctorsDirectoryAdmin;

use App\Models\Clinic;
use App\Models\Speciality;
use App\Repositories\DoctorsDirectoryAdmin\SpecialityRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SpecialityService
{
    public function __construct(
        private readonly SpecialityRepository $repository,
    ) {}

    /**
     * @return array{total: int, published: int, unpublished: int}
     */
    public function dashboardSummary(): array
    {
        return $this->repository->dashboardCounts();
    }

    public function listPaginated(string $search, ?string $publish): LengthAwarePaginator
    {
        $perPage = (int) config('hm.doctors_directory_admin.per_page', 15);

        return $this->repository->paginateFiltered($search, $publish, $perPage);
    }

    public function findForEdit(int $id): ?Speciality
    {
        return $this->repository->findForEdit($id);
    }

    /**
     * @return array<int, string>
     */
    public function clinicOptions(): array
    {
        $locale = app()->getLocale();
        $options = [
            0 => __('doctors_directory_admin.no_clinic'),
        ];

        Clinic::query()
            ->select(['id', 'name_ar', 'name_en'])
            ->orderBy($locale === 'ar' ? 'name_ar' : 'name_en')
            ->each(function (Clinic $clinic) use (&$options) {
                $options[(int) $clinic->id] = $clinic->localizedName();
            });

        return $options;
    }

    public function create(
        int $clinicId,
        string $subjectAr,
        string $subjectEn,
        string $publish,
    ): Speciality {
        $userId = (int) session('hr_user_id', 0);

        return $this->repository->create([
            'clinics_id' => $clinicId,
            'subject_ar' => $subjectAr,
            'subject_en' => $subjectEn,
            'publish' => $publish,
            'companies_groups_id' => (int) session('companies_groups_id', 0),
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    public function update(
        Speciality $speciality,
        int $clinicId,
        string $subjectAr,
        string $subjectEn,
        string $publish,
    ): Speciality {
        return $this->repository->update($speciality, [
            'clinics_id' => $clinicId,
            'subject_ar' => $subjectAr,
            'subject_en' => $subjectEn,
            'publish' => $publish,
            'updated_by' => (int) session('hr_user_id', 0),
        ]);
    }

    public function togglePublish(Speciality $speciality): Speciality
    {
        return $this->repository->togglePublish($speciality);
    }
}
