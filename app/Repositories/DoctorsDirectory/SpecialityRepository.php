<?php

namespace App\Repositories\DoctorsDirectory;

use App\Models\Speciality;
use App\Support\DoctorsDirectory\LegacyWeekday;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SpecialityRepository
{
    /**
     * @var list<string>
     */
    private const LIST_COLUMNS = [
        'id',
        'clinics_id',
        'subject_ar',
        'subject_en',
        'publish',
    ];

    /**
     * @var list<string>
     */
    private const DETAIL_COLUMNS = [
        'id',
        'clinics_id',
        'subject_ar',
        'subject_en',
        'post_ar',
        'post_en',
        'publish',
    ];

    public function paginatePublished(string $search, int $perPage): LengthAwarePaginator
    {
        return Speciality::query()
            ->select(self::LIST_COLUMNS)
            ->where('publish', '1')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('subject_ar', 'like', '%'.$search.'%')
                        ->orWhere('subject_en', 'like', '%'.$search.'%');
                });
            })
            ->withCount([
                'outpatientSections as departments_count' => fn ($q) => $q->where('publish', 1),
                'clinicians as doctors_count' => fn ($q) => $q->where('publish', '1'),
            ])
            ->tap(fn ($query) => $this->applyLegacyDisplayOrder($query))
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Single query — no counts needed on the index grid.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Speciality>
     */
    public function listAllPublished(): \Illuminate\Database\Eloquent\Collection
    {
        $query = Speciality::query()
            ->select(self::LIST_COLUMNS)
            ->where('publish', '1')
            ->withCount([
                'clinicians as doctors_count' => fn ($q) => $q->where('publish', '1'),
            ]);

        $this->applyLegacyDisplayOrder($query);

        return $query->get();
    }

    /**
     * @return array<int, int> speciality id => distinct published doctors available today
     */
    public function availableTodayDoctorCountsBySpeciality(): array
    {
        $today = LegacyWeekday::today();

        return DB::table('clinicians as doctor')
            ->join('holidays_offers as offer', 'offer.clinicians_id', '=', 'doctor.id')
            ->join('holidays_offers_clinicians_working_days as working_day', 'working_day.holidays_offers_id', '=', 'offer.id')
            ->where('doctor.publish', '1')
            ->where('working_day.day', $today)
            ->groupBy('doctor.specialized_clinics_id')
            ->select([
                'doctor.specialized_clinics_id',
                DB::raw('count(distinct doctor.id) as available_count'),
            ])
            ->pluck('available_count', 'specialized_clinics_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    public function countPublishedDoctorsAvailableToday(): int
    {
        $today = LegacyWeekday::today();

        return (int) DB::table('clinicians as doctor')
            ->join('holidays_offers as offer', 'offer.clinicians_id', '=', 'doctor.id')
            ->join('holidays_offers_clinicians_working_days as working_day', 'working_day.holidays_offers_id', '=', 'offer.id')
            ->where('doctor.publish', '1')
            ->where('working_day.day', $today)
            ->distinct()
            ->count('doctor.id');
    }

    /**
     * @return array<int, int> hospital id => distinct published doctors
     */
    public function publishedDoctorCountsByHospital(int $specialityId): array
    {
        return DB::table('clinicians as doctor')
            ->join('clinician_hospitals as assignment', 'assignment.clinicians_id', '=', 'doctor.id')
            ->where('doctor.specialized_clinics_id', $specialityId)
            ->where('doctor.publish', '1')
            ->groupBy('assignment.hospital_id')
            ->select([
                'assignment.hospital_id',
                DB::raw('count(distinct doctor.id) as doctors_count'),
            ])
            ->pluck('doctors_count', 'hospital_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /**
     * Legacy clinic-doctors dropdown order (specialized_clinics.id sequence).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Speciality>|\Illuminate\Database\Query\Builder  $query
     */
    private function applyLegacyDisplayOrder($query): void
    {
        $orderedIds = config('hm.doctors_directory.speciality_display_order');

        if (is_array($orderedIds) && $orderedIds !== []) {
            $ids = implode(',', array_map('intval', $orderedIds));
            $query->orderByRaw('FIELD(id, '.$ids.')');

            return;
        }

        $query->orderBy('id');
    }

    public function findPublishedForDepartments(int $id, ?int $hospitalId = null): ?Speciality
    {
        $speciality = Speciality::query()
            ->select(self::DETAIL_COLUMNS)
            ->whereIn('publish', ['1', 1])
            ->whereKey($id)
            ->with([
                'clinic:id,name_ar,name_en',
                'outpatientSections' => function ($query) {
                    $query
                        ->select([
                            'outpatient_clinics_sections.id',
                            'outpatient_clinics_sections.outpatient_clinics_id',
                            'outpatient_clinics_sections.specialized_clinics_id',
                            'outpatient_clinics_sections.publish',
                        ])
                        ->where('outpatient_clinics_sections.publish', 1)
                        ->with(['outpatientClinic:id,name_ar,name_en,companies_groups_id'])
                        ->orderBy('outpatient_clinics_sections.id');
                },
            ])
            ->withCount([
                'outpatientSections as departments_count' => fn ($query) => $query->where('publish', 1),
                'clinicians as doctors_count' => fn ($q) => $q->where('publish', '1'),
            ])
            ->first();

        if ($speciality === null) {
            return null;
        }

        $this->attachDoctorCountsToSections($speciality, $hospitalId);

        return $speciality;
    }

    /**
     * @return \Illuminate\Support\Collection<int, object{id: int, name_ar: string|null, name_en: string|null}>
     */
    public function publishedHospitalsForSpeciality(int $specialityId): \Illuminate\Support\Collection
    {
        $fromSections = DB::table('outpatient_clinics_sections as section')
            ->join('outpatient_clinics as clinic', 'clinic.id', '=', 'section.outpatient_clinics_id')
            ->join('companies_groups as hospital', 'hospital.id', '=', 'clinic.companies_groups_id')
            ->where('section.specialized_clinics_id', $specialityId)
            ->where('section.publish', 1)
            ->select(['hospital.id', 'hospital.name_ar', 'hospital.name_en']);

        $fromDoctors = DB::table('clinicians as doctor')
            ->join('clinician_hospitals as assignment', 'assignment.clinicians_id', '=', 'doctor.id')
            ->join('companies_groups as hospital', 'hospital.id', '=', 'assignment.hospital_id')
            ->where('doctor.specialized_clinics_id', $specialityId)
            ->where('doctor.publish', '1')
            ->select(['hospital.id', 'hospital.name_ar', 'hospital.name_en']);

        $hospitals = DB::query()
            ->fromSub($fromSections->union($fromDoctors), 'hospitals')
            ->select(['id', 'name_ar', 'name_en'])
            ->distinct()
            ->orderBy('id')
            ->get();

        if ($hospitals->isEmpty()) {
            return $hospitals;
        }

        $configuredBranchIds = config('hm.doctors_directory.outpatient_branch_ids', []);

        if (! is_array($configuredBranchIds) || $configuredBranchIds === []) {
            return $hospitals;
        }

        $existingIds = $hospitals->pluck('id')->map(fn ($id) => (int) $id)->all();
        $missingIds = array_values(array_diff(
            array_map('intval', $configuredBranchIds),
            $existingIds,
        ));

        if ($missingIds === []) {
            return $hospitals;
        }

        $extra = DB::table('companies_groups')
            ->select(['id', 'name_ar', 'name_en'])
            ->whereIn('id', $missingIds)
            ->orderBy('id')
            ->get();

        return $hospitals
            ->merge($extra)
            ->unique('id')
            ->sortBy('id')
            ->values();
    }

    public function findPublishedHospital(int $hospitalId): ?object
    {
        return DB::table('companies_groups')
            ->select(['id', 'name_ar', 'name_en'])
            ->where('id', $hospitalId)
            ->first() ?: null;
    }

    public function findPublishedWithDescription(int $id): ?Speciality
    {
        return Speciality::query()
            ->select(self::DETAIL_COLUMNS)
            ->whereIn('publish', ['1', 1])
            ->whereKey($id)
            ->first();
    }

    private function attachDoctorCountsToSections(Speciality $speciality, ?int $hospitalId): void
    {
        $sectionIds = $speciality->outpatientSections->pluck('id')->all();

        if ($sectionIds === []) {
            return;
        }

        $counts = DB::table('outpatient_clinics_sections as section')
            ->join('clinicians as doctor', function ($join) {
                $join->on('doctor.specialized_clinics_id', '=', 'section.specialized_clinics_id');
            })
            ->join('clinician_hospitals as assignment', function ($join) {
                $join->on('assignment.clinicians_id', '=', 'doctor.id')
                    ->on('assignment.clinics_id', '=', 'section.outpatient_clinics_id');
            })
            ->whereIn('section.id', $sectionIds)
            ->where('doctor.publish', '1')
            ->when($hospitalId !== null && $hospitalId > 0, fn ($query) => $query->where('assignment.hospital_id', $hospitalId))
            ->groupBy('section.id')
            ->select(['section.id', DB::raw('count(distinct doctor.id) as doctors_count')])
            ->pluck('doctors_count', 'section.id');

        foreach ($speciality->outpatientSections as $section) {
            $section->doctors_count = (int) ($counts[$section->id] ?? 0);
        }
    }
}
