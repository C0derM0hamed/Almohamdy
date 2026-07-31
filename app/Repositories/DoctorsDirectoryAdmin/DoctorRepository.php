<?php

namespace App\Repositories\DoctorsDirectoryAdmin;

use App\Models\Clinician;
use App\Models\ClinicianHospital;
use App\Models\OutpatientClinic;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DoctorRepository
{
    /**
     * @var list<string>
     */
    private const LIST_COLUMNS = [
        'id',
        'specialized_clinics_id',
        'code',
        'name_ar',
        'name_en',
        'specialization_ar',
        'specialization_en',
        'publish',
        'ranking',
        'status',
    ];

    /**
     * @var list<string>
     */
    private const FORM_COLUMNS = [
        'id',
        'specialized_clinics_id',
        'code',
        'name_ar',
        'name_en',
        'specialization_ar',
        'specialization_en',
        'uploaded_file',
        'price',
        'age',
        'holds_ar',
        'holds_en',
        'cases_ar',
        'cases_en',
        'publish',
        'country_id',
        'mobile',
        'email',
        'ranking',
        'status',
        'created_at',
        'updated_at',
    ];

    private function baseQuery(): Builder
    {
        return Clinician::query()->select(self::LIST_COLUMNS);
    }

    public function paginateFiltered(
        string $search,
        ?int $specialityId,
        ?int $departmentId,
        ?string $publish,
        int $perPage,
    ): LengthAwarePaginator {
        $nameColumn = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';

        return $this->baseQuery()
            ->when($specialityId !== null && $specialityId > 0, fn (Builder $q) => $q->where('specialized_clinics_id', $specialityId))
            ->when($publish !== null && $publish !== '', fn (Builder $q) => $q->where('publish', $publish === '1' ? '1' : '0'))
            ->when($departmentId !== null && $departmentId > 0, function (Builder $q) use ($departmentId) {
                $q->whereHas('hospitals', fn (Builder $inner) => $inner->where('clinics_id', $departmentId));
            })
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->where(function (Builder $inner) use ($search) {
                    $inner->where('name_ar', 'like', '%'.$search.'%')
                        ->orWhere('name_en', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhere('specialization_ar', 'like', '%'.$search.'%')
                        ->orWhere('specialization_en', 'like', '%'.$search.'%');
                });
            })
            ->with(['speciality:id,subject_ar,subject_en'])
            ->withCount('hospitals as departments_count')
            ->orderBy('ranking')
            ->orderBy($nameColumn)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForEdit(int $id): ?Clinician
    {
        return Clinician::query()
            ->select(self::FORM_COLUMNS)
            ->whereKey($id)
            ->with([
                'speciality:id,subject_ar,subject_en',
                'country:id,country_nationality_ar,country_nationality_en',
                'hospitals' => fn ($query) => $query
                    ->select(['id', 'clinicians_id', 'clinics_id', 'hospital_id'])
                    ->with(['outpatientClinic:id,name_ar,name_en'])
                    ->orderBy('id'),
            ])
            ->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Clinician
    {
        $clinician = new Clinician;

        foreach ($attributes as $key => $value) {
            $clinician->{$key} = $value;
        }

        $clinician->save();

        return $clinician;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Clinician $clinician, array $attributes): Clinician
    {
        foreach ($attributes as $key => $value) {
            $clinician->{$key} = $value;
        }

        $clinician->save();

        return $clinician;
    }

    public function togglePublish(Clinician $clinician): Clinician
    {
        $clinician->publish = $clinician->publish === '1' ? '0' : '1';
        $clinician->save();

        return $clinician;
    }

    public function codeExists(string $code, ?int $ignoreId = null): bool
    {
        return DB::table('clinicians')
            ->where('code', $code)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    /**
     * @return Collection<int, ClinicianHospital>
     */
    public function assignmentsForClinician(int $clinicianId): Collection
    {
        return ClinicianHospital::query()
            ->select(['id', 'clinicians_id', 'clinics_id', 'hospital_id'])
            ->where('clinicians_id', $clinicianId)
            ->with(['outpatientClinic:id,name_ar,name_en'])
            ->orderBy('id')
            ->get();
    }

    public function createAssignment(int $clinicianId, int $departmentId, int $hospitalId): ClinicianHospital
    {
        $assignment = new ClinicianHospital;
        $assignment->clinicians_id = $clinicianId;
        $assignment->clinics_id = $departmentId;
        $assignment->hospital_id = $hospitalId;
        $assignment->save();

        return $assignment;
    }

    public function findAssignment(int $clinicianId, int $assignmentId): ?ClinicianHospital
    {
        return ClinicianHospital::query()
            ->whereKey($assignmentId)
            ->where('clinicians_id', $clinicianId)
            ->first();
    }

    public function deleteAssignment(ClinicianHospital $assignment): void
    {
        $assignment->delete();
    }

    public function assignmentExists(int $clinicianId, int $departmentId, int $hospitalId, ?int $ignoreId = null): bool
    {
        return DB::table('clinician_hospitals')
            ->where('clinicians_id', $clinicianId)
            ->where('clinics_id', $departmentId)
            ->where('hospital_id', $hospitalId)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    public function isDepartmentLinkedToSpeciality(int $specialityId, int $departmentId): bool
    {
        return DB::table('outpatient_clinics_sections')
            ->where('specialized_clinics_id', $specialityId)
            ->where('outpatient_clinics_id', $departmentId)
            ->exists();
    }

    /**
     * Departments assigned to a speciality via outpatient_clinics_sections.
     *
     * @return array<int, string>
     */
    public function departmentOptionsForSpeciality(int $specialityId): array
    {
        $nameColumn = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';
        $options = [];

        OutpatientClinic::query()
            ->select(['outpatient_clinics.id', 'outpatient_clinics.name_ar', 'outpatient_clinics.name_en'])
            ->join('outpatient_clinics_sections as sections', 'sections.outpatient_clinics_id', '=', 'outpatient_clinics.id')
            ->where('sections.specialized_clinics_id', $specialityId)
            ->orderBy('outpatient_clinics.'.$nameColumn)
            ->distinct()
            ->each(function (OutpatientClinic $clinic) use (&$options) {
                $options[(int) $clinic->id] = $clinic->localizedName();
            });

        return $options;
    }
}
