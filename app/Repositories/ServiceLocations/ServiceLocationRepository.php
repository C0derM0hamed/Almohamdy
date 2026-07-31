<?php

namespace App\Repositories\ServiceLocations;

use App\Models\OutpatientClinic;
use App\Models\OutpatientClinicSection;
use App\Models\Speciality;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class ServiceLocationRepository
{
    /**
     * @return Collection<int, OutpatientClinic>
     */
    public function publishedLocations(): Collection
    {
        return OutpatientClinic::query()
            ->select(['id', 'name_en', 'name_ar', 'floor_id'])
            ->whereIn('publish', [1, '1'])
            ->orderBy('id')
            ->get();
    }

    public function findPublishedLocation(int $id): ?OutpatientClinic
    {
        return OutpatientClinic::query()
            ->select(['id', 'name_en', 'name_ar', 'floor_id'])
            ->whereIn('publish', [1, '1'])
            ->whereKey($id)
            ->first();
    }

    /**
     * @return object{
     *     phone_ext: string|null,
     *     duty_days: string|null,
     *     duty_time: string|null,
     *     floor_id: int|null,
     *     floor_name_en: string|null,
     *     floor_name_ar: string|null
     * }|null
     */
    public function dutyInfoForLocation(int $clinicId): ?object
    {
        $row = DB::table('outpatient_clinics_duty_time as duty')
            ->leftJoin('floors as floor', 'floor.id', '=', 'duty.floor_id')
            ->where('duty.cilinc_id', $clinicId)
            ->where('duty.publish', '1')
            ->orderByRaw("CASE WHEN duty.section_name LIKE '%استقبال%' THEN 0 ELSE 1 END")
            ->orderByDesc('duty.id')
            ->select([
                'duty.phone_ext',
                'duty.duty_days',
                'duty.duty_time',
                'duty.floor_id',
                'floor.name_en as floor_name_en',
                'floor.name_ar as floor_name_ar',
            ])
            ->first();

        return $row ?: null;
    }

    /**
     * Published departments assigned directly to an O.P.D location.
     *
     * Matches legacy outpatient_clinics_sections.php?id={outpatient_clinics_id}.
     *
     * @return Collection<int, OutpatientClinicSection>
     */
    public function publishedSectionsForLocation(int $clinicId): Collection
    {
        return OutpatientClinicSection::query()
            ->select(['id', 'outpatient_clinics_id', 'specialized_clinics_id', 'publish'])
            ->with(['speciality:id,subject_en,subject_ar,publish'])
            ->where('outpatient_clinics_id', $clinicId)
            ->where('publish', 1)
            ->whereHas('speciality', fn ($query) => $query->where('publish', '1'))
            ->orderBy('id')
            ->get();
    }

    public function findPublishedSpeciality(int $specialityId): ?Speciality
    {
        return $this->findPublishedSpecialities([$specialityId])->get($specialityId);
    }

    /**
     * @param  list<int>  $specialityIds
     * @return Collection<int, Speciality>
     */
    public function findPublishedSpecialities(array $specialityIds): Collection
    {
        if ($specialityIds === []) {
            return new Collection();
        }

        return Speciality::query()
            ->select(['id', 'subject_en', 'subject_ar', 'publish'])
            ->whereIn('id', $specialityIds)
            ->whereIn('publish', ['1', 1])
            ->get()
            ->keyBy('id');
    }

    public function resolvePublishedSectionForSpeciality(int $specialityId, int $preferredOpdId): ?OutpatientClinicSection
    {
        return OutpatientClinicSection::query()
            ->select(['id', 'outpatient_clinics_id', 'specialized_clinics_id', 'publish'])
            ->where('specialized_clinics_id', $specialityId)
            ->where('publish', 1)
            ->whereHas('speciality', fn ($query) => $query->where('publish', '1'))
            ->orderByRaw('CASE WHEN outpatient_clinics_id = ? THEN 0 ELSE 1 END', [$preferredOpdId])
            ->orderBy('id')
            ->first();
    }

    /**
     * @return list<array{speciality_id?: int, label_key?: string}>
     */
    public function configuredDepartmentEntries(int $opdId): array
    {
        $entries = config("hm.service_locations.opd_departments.{$opdId}");

        if (is_array($entries) && $entries !== []) {
            return $entries;
        }

        return $this->publishedSectionsForLocation($opdId)
            ->map(fn (OutpatientClinicSection $section) => [
                'speciality_id' => (int) $section->specialized_clinics_id,
            ])
            ->unique('speciality_id')
            ->values()
            ->all();
    }

    public function supportServiceLabelForLocation(int $opdId): ?string
    {
        if (! DB::getSchemaBuilder()->hasTable('clinics_support_services_sectionbk')
            || ! DB::getSchemaBuilder()->hasTable('clinics_support_services_details')) {
            return null;
        }

        $hasDetails = DB::table('clinics_support_services_sectionbk as section')
            ->join('clinics_support_services_details as detail', 'detail.clinics_support_services_section_id', '=', 'section.id')
            ->where('section.id', $opdId)
            ->where('section.publish', '1')
            ->where('detail.publish', '1')
            ->exists();

        if (! $hasDetails) {
            return null;
        }

        return __('service_locations.support_services');
    }

    /**
     * @return SupportCollection<int, object{
     *     id: int,
     *     service_name: string|null,
     *     room_number: string|null,
     *     duty_days: string|null,
     *     phone_ext: string|null,
     *     duty_time: string|null,
     *     notice: string|null
     * }>
     */
    public function publishedSupportServicesForOpd(int $opdId): SupportCollection
    {
        if (! DB::getSchemaBuilder()->hasTable('clinics_support_services_details')) {
            return collect();
        }

        return DB::table('clinics_support_services_details')
            ->select([
                'id',
                'service_name',
                'room_number',
                'duty_days',
                'phone_ext',
                'duty_time',
                'notice',
            ])
            ->where('clinics_support_services_section_id', $opdId)
            ->where('publish', '1')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return SupportCollection<int, object{
     *     id: int,
     *     name_en: string|null,
     *     name_ar: string|null
     * }>
     */
    public function publishedFloors(): SupportCollection
    {
        return DB::table('floors')
            ->select(['id', 'name_en', 'name_ar'])
            ->whereIn('publish', ['1', 1])
            ->orderBy('id')
            ->get();
    }

    /**
     * @return object{id: int, name_en: string|null, name_ar: string|null}|null
     */
    public function findPublishedFloor(int $id): ?object
    {
        return DB::table('floors')
            ->select(['id', 'name_en', 'name_ar'])
            ->whereIn('publish', ['1', 1])
            ->where('id', $id)
            ->first() ?: null;
    }

    /**
     * @return SupportCollection<int, object{
     *     id: int,
     *     section_name: string|null,
     *     duty_days: string|null,
     *     duty_time: string|null,
     *     visit_time: string|null
     * }>
     */
    public function publishedDetailsForFloor(int $floorId): SupportCollection
    {
        if (! DB::getSchemaBuilder()->hasTable('floors_details')) {
            return collect();
        }

        $companyGroupId = (int) session('companies_groups_id', 0);

        $items = $this->floorDetailsQuery($floorId, $companyGroupId)->get();

        if ($items->isEmpty() && $companyGroupId > 0) {
            $items = $this->floorDetailsQuery($floorId, 0)->get();
        }

        return $items;
    }

    /**
     * @return \Illuminate\Database\Query\Builder
     */
    private function floorDetailsQuery(int $floorId, int $companyGroupId)
    {
        $query = DB::table('floors_details')
            ->select([
                'id',
                'section_name',
                'duty_days',
                'duty_time',
                'visit_time',
            ])
            ->where('floor_id', $floorId)
            ->whereIn('publish', ['1', 1])
            ->orderBy('id');

        if ($companyGroupId > 0) {
            $query->where('companies_groups_id', $companyGroupId);
        }

        return $query;
    }
}
