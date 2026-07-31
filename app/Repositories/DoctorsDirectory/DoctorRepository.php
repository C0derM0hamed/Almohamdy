<?php

namespace App\Repositories\DoctorsDirectory;

use App\Models\Clinician;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DoctorRepository
{
    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Clinician>  $query
     */
    private function applyPublishedFilter($query): void
    {
        $query->where('publish', '1');
    }

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
        'holds_ar',
        'holds_en',
        'cases_ar',
        'cases_en',
        'age',
        'uploaded_file',
        'price',
        'country_id',
        'ranking',
        'publish',
    ];

    public function paginateBySpecialityAndHospital(
        int $specialityId,
        ?int $hospitalId,
        string $name,
        string $code,
        int $perPage,
        bool $allBranches = false,
    ): LengthAwarePaginator {
        $nameColumn = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';

        $query = Clinician::query()
            ->select(self::LIST_COLUMNS);
        $this->applyPublishedFilter($query);

        return $query
            ->where('specialized_clinics_id', $specialityId)
            ->when(! $allBranches && $hospitalId !== null && $hospitalId > 0, function ($query) use ($hospitalId) {
                $query->whereExists(function ($query) use ($hospitalId) {
                    $query->select(DB::raw(1))
                        ->from('clinician_hospitals')
                        ->whereColumn('clinician_hospitals.clinicians_id', 'clinicians.id')
                        ->where('clinician_hospitals.hospital_id', $hospitalId);
                });
            })
            ->when($name !== '', function ($query) use ($name) {
                $query->where(function ($inner) use ($name) {
                    $inner->where('name_ar', 'like', '%'.$name.'%')
                        ->orWhere('name_en', 'like', '%'.$name.'%');
                });
            })
            ->when($code !== '', function ($query) use ($code) {
                $query->where('code', 'like', '%'.$code.'%');
            })
            ->with([
                'country:id,country_nationality_ar,country_nationality_en',
                'speciality:id,subject_ar,subject_en',
                'hospitals' => function ($query) use ($hospitalId, $allBranches) {
                    $query
                        ->select(['id', 'clinicians_id', 'clinics_id', 'hospital_id', 'clinic_number', 'ext_number', 'price'])
                        ->when(! $allBranches && $hospitalId !== null && $hospitalId > 0, function ($inner) use ($hospitalId) {
                            $inner->where('hospital_id', $hospitalId);
                        })
                        ->with([
                            'outpatientClinic:id,name_ar,name_en',
                            'hospital:id,name_ar,name_en',
                        ])
                        ->orderBy('id');
                },
            ])
            ->orderBy('ranking')
            ->orderBy($nameColumn)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateByDepartment(
        int $specialityId,
        int $departmentId,
        string $name,
        string $code,
        string $specialization,
        int $perPage,
        ?int $hospitalId = null,
    ): LengthAwarePaginator {
        $nameColumn = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';

        $query = Clinician::query()
            ->select(self::LIST_COLUMNS);
        $this->applyPublishedFilter($query);

        return $query
            ->where('specialized_clinics_id', $specialityId)
            ->whereExists(function ($query) use ($departmentId, $specialityId, $hospitalId) {
                $query->select(DB::raw(1))
                    ->from('outpatient_clinics_sections as department_sections')
                    ->join('clinician_hospitals as department_hospitals', function ($join) {
                        $join->on('department_hospitals.clinics_id', '=', 'department_sections.outpatient_clinics_id');
                    })
                    ->whereColumn('department_hospitals.clinicians_id', 'clinicians.id')
                    ->where('department_sections.id', $departmentId)
                    ->where('department_sections.specialized_clinics_id', $specialityId)
                    ->where('department_sections.publish', 1)
                    ->when($hospitalId !== null && $hospitalId > 0, function ($inner) use ($hospitalId) {
                        $inner->where('department_hospitals.hospital_id', $hospitalId);
                    });
            })
            ->when($name !== '', function ($query) use ($name) {
                $query->where(function ($inner) use ($name) {
                    $inner->where('name_ar', 'like', '%'.$name.'%')
                        ->orWhere('name_en', 'like', '%'.$name.'%');
                });
            })
            ->when($code !== '', function ($query) use ($code) {
                $query->where('code', 'like', '%'.$code.'%');
            })
            ->when($specialization !== '', function ($query) use ($specialization) {
                $query->where(function ($inner) use ($specialization) {
                    $inner->where('specialization_ar', 'like', '%'.$specialization.'%')
                        ->orWhere('specialization_en', 'like', '%'.$specialization.'%');
                });
            })
            ->with([
                'country:id,country_nationality_ar,country_nationality_en',
                'speciality:id,subject_ar,subject_en',
                'hospitals' => fn ($query) => $query
                    ->select(['id', 'clinicians_id', 'clinics_id', 'hospital_id', 'clinic_number', 'ext_number', 'price'])
                    ->when($hospitalId !== null && $hospitalId > 0, fn ($inner) => $inner->where('hospital_id', $hospitalId))
                    ->with([
                        'outpatientClinic:id,name_ar,name_en',
                        'hospital:id,name_ar,name_en',
                    ])
                    ->orderBy('id'),
            ])
            ->orderBy('ranking')
            ->orderBy($nameColumn)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function paginateByOpdSpeciality(
        int $specialityId,
        int $opdId,
        string $name,
        string $code,
        string $specialization,
        int $perPage,
        ?int $hospitalId = null,
    ): LengthAwarePaginator {
        $nameColumn = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';

        $query = Clinician::query()
            ->select(self::LIST_COLUMNS);
        $this->applyPublishedFilter($query);

        return $query
            ->where('specialized_clinics_id', $specialityId)
            ->where(function ($outer) use ($opdId, $hospitalId) {
                $outer->whereIn('clinicians.id', function ($assignmentQuery) use ($opdId, $hospitalId) {
                    $assignmentQuery->select('clinicians_id')
                        ->from('clinician_hospitals')
                        ->where('clinics_id', $opdId)
                        ->when($hospitalId !== null && $hospitalId > 0, function ($inner) use ($hospitalId) {
                            $inner->where('hospital_id', $hospitalId);
                        });
                })->orWhere(function ($fallback) use ($opdId) {
                    $fallback->where('clinicians.clinics_id', $opdId)
                        ->whereNotExists(function ($query) {
                            $query->select(DB::raw(1))
                                ->from('clinician_hospitals as assignment_check')
                                ->whereColumn('assignment_check.clinicians_id', 'clinicians.id');
                        });
                });
            })
            ->when($name !== '', function ($query) use ($name) {
                $query->where(function ($inner) use ($name) {
                    $inner->where('name_ar', 'like', '%'.$name.'%')
                        ->orWhere('name_en', 'like', '%'.$name.'%');
                });
            })
            ->when($code !== '', function ($query) use ($code) {
                $query->where('code', 'like', '%'.$code.'%');
            })
            ->when($specialization !== '', function ($query) use ($specialization) {
                $query->where(function ($inner) use ($specialization) {
                    $inner->where('specialization_ar', 'like', '%'.$specialization.'%')
                        ->orWhere('specialization_en', 'like', '%'.$specialization.'%');
                });
            })
            ->with([
                'country:id,country_nationality_ar,country_nationality_en',
                'speciality:id,subject_ar,subject_en',
                'hospitals' => fn ($query) => $query
                    ->select(['id', 'clinicians_id', 'clinics_id', 'hospital_id', 'clinic_number', 'ext_number', 'price'])
                    ->when($hospitalId !== null && $hospitalId > 0, fn ($inner) => $inner->where('hospital_id', $hospitalId))
                    ->when($opdId > 0, fn ($inner) => $inner->where('clinics_id', $opdId))
                    ->with([
                        'outpatientClinic:id,name_ar,name_en',
                        'hospital:id,name_ar,name_en',
                    ])
                    ->orderBy('id'),
            ])
            ->orderBy('ranking')
            ->orderBy($nameColumn)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findPublished(int $id): ?Clinician
    {
        $query = Clinician::query()
            ->select(self::LIST_COLUMNS);
        $this->applyPublishedFilter($query);

        return $query
            ->whereKey($id)
            ->with([
                'country:id,country_nationality_ar,country_nationality_en',
                'speciality:id,subject_ar,subject_en,clinics_id',
            ])
            ->first();
    }

    /**
     * @var list<string>
     */
    private const DETAIL_COLUMNS = [
        'id',
        'specialized_clinics_id',
        'code',
        'name_ar',
        'name_en',
        'specialization_ar',
        'specialization_en',
        'holds_ar',
        'holds_en',
        'cases_ar',
        'cases_en',
        'age',
        'uploaded_file',
        'price',
        'country_id',
        'mobile',
        'email',
        'publish',
    ];

    public function findPublishedForDetail(int $id): ?Clinician
    {
        $query = Clinician::query()
            ->select(self::DETAIL_COLUMNS);
        $this->applyPublishedFilter($query);

        return $query
            ->whereKey($id)
            ->with([
                'country:id,country_nationality_ar,country_nationality_en',
                'speciality:id,subject_ar,subject_en,clinics_id',
                'holidayOffers' => fn ($query) => $query
                    ->select(['id', 'clinicians_id'])
                    ->whereHas('workingDays')
                    ->with(['workingDays' => fn ($workingDayQuery) => $workingDayQuery
                        ->select(['id', 'holidays_offers_id', 'day', 'time_from', 'time_to'])
                        ->orderBy('day')
                        ->orderBy('time_from')]),
            ])
            ->first();
    }
}
