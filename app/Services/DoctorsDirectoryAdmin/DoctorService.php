<?php

namespace App\Services\DoctorsDirectoryAdmin;

use App\Models\Clinician;
use App\Models\ClinicianHospital;
use App\Models\Country;
use App\Models\OutpatientClinic;
use App\Models\Speciality;
use App\Repositories\DoctorsDirectoryAdmin\DoctorRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class DoctorService
{
    public function __construct(
        private readonly DoctorRepository $repository,
    ) {}

    public function listPaginated(
        string $search,
        ?int $specialityId,
        ?int $departmentId,
        ?string $publish,
    ): LengthAwarePaginator {
        $perPage = (int) config('hm.doctors_directory_admin.per_page', 15);

        return $this->repository->paginateFiltered($search, $specialityId, $departmentId, $publish, $perPage);
    }

    public function findForEdit(int $id): ?Clinician
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

    /**
     * @return array<int, string>
     */
    public function departmentOptionsForSpeciality(int $specialityId): array
    {
        return $this->repository->departmentOptionsForSpeciality($specialityId);
    }

    public function isDepartmentLinkedToSpeciality(int $specialityId, int $departmentId): bool
    {
        return $this->repository->isDepartmentLinkedToSpeciality($specialityId, $departmentId);
    }

    /**
     * @return array<int, string>
     */
    public function countryOptions(): array
    {
        $nameColumn = app()->getLocale() === 'ar' ? 'country_nationality_ar' : 'country_nationality_en';
        $options = [];

        Country::query()
            ->select(['id', 'country_nationality_ar', 'country_nationality_en'])
            ->orderBy($nameColumn)
            ->each(function (Country $country) use (&$options) {
                $options[(int) $country->id] = $country->localizedName();
            });

        return $options;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, ?UploadedFile $photo = null): Clinician
    {
        if ($photo !== null) {
            $attributes['uploaded_file'] = $this->storePhoto($photo);
        }

        return $this->repository->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Clinician $clinician, array $attributes, ?UploadedFile $photo = null, bool $removePhoto = false): Clinician
    {
        $previousPhoto = $clinician->uploaded_file;

        if ($photo !== null) {
            $attributes['uploaded_file'] = $this->storePhoto($photo);
            $this->deletePhotoFile($previousPhoto);
        } elseif ($removePhoto) {
            $attributes['uploaded_file'] = null;
            $this->deletePhotoFile($previousPhoto);
        }

        return $this->repository->update($clinician, $attributes);
    }

    public function togglePublish(Clinician $clinician): Clinician
    {
        return $this->repository->togglePublish($clinician);
    }

    public function codeExists(string $code, ?int $ignoreId = null): bool
    {
        return $this->repository->codeExists($code, $ignoreId);
    }

    public function addAssignment(int $clinicianId, int $departmentId): ClinicianHospital
    {
        return $this->repository->createAssignment(
            $clinicianId,
            $departmentId,
            (int) session('companies_groups_id', 0),
        );
    }

    public function removeAssignment(int $clinicianId, int $assignmentId): bool
    {
        $assignment = $this->repository->findAssignment($clinicianId, $assignmentId);

        if ($assignment === null) {
            return false;
        }

        $this->repository->deleteAssignment($assignment);

        return true;
    }

    public function assignmentExists(int $clinicianId, int $departmentId): bool
    {
        return $this->repository->assignmentExists(
            $clinicianId,
            $departmentId,
            (int) session('companies_groups_id', 0),
        );
    }

    public function previewUrl(Clinician $clinician): ?string
    {
        if ($clinician->publish !== '1') {
            return null;
        }

        return route('modules.doctors.doctors.show', $clinician->id);
    }

    private function storePhoto(UploadedFile $photo): string
    {
        $extension = $photo->guessExtension() ?: 'jpg';
        $filename = uniqid('clinician_', true).'.'.$extension;
        $directory = $this->photosDirectory();

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $photo->move($directory, $filename);

        return $filename;
    }

    private function deletePhotoFile(?string $filename): void
    {
        if ($filename === null || $filename === '') {
            return;
        }

        $path = $this->photosDirectory().DIRECTORY_SEPARATOR.$filename;

        if (is_file($path)) {
            unlink($path);
        }
    }

    private function photosDirectory(): string
    {
        $relativePath = trim((string) config('hm.doctors_directory.photos_path', '/files'), '/');

        return public_path($relativePath);
    }
}
