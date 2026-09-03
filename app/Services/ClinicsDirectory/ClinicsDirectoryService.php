<?php

namespace App\Services\ClinicsDirectory;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClinicsDirectoryService
{
    public function authorize(): void
    {
        abort_unless((int) session('hr_user_level', 0) === 3 || in_array((int) session('hr_branch_id', 0), [5, 6, 15, 27, 29, 30, 31, 2, 42], true), 403);
    }

    public function lookups(): array
    {
        $this->authorize();
        return [
            'specializedClinics' => DB::table('specialized_clinics')->where('publish', 1)->orderBy('subject_ar')->get(),
            'hospitals' => DB::table('companies_groups')->orderBy('name_ar')->get(),
        ];
    }

    public function list(array $filters): LengthAwarePaginator
    {
        $this->authorize();
        $clinic = (int) ($filters['clinic_id'] ?? 0);
        $hospital = (int) ($filters['hospital_id'] ?? 0);
        $all = (bool) ($filters['search_all'] ?? false);
        $query = DB::table('clinicians as c')
            ->leftJoin('clinician_hospitals as ch', 'ch.clinicians_id', '=', 'c.id')
            ->leftJoin('companies_groups as hospital', 'hospital.id', '=', 'ch.hospital_id')
            ->leftJoin('clinics as clinic_building', 'clinic_building.id', '=', 'ch.clinics_id')
            ->select(['c.*', 'ch.id as assignment_id', 'ch.hospital_id', 'ch.clinics_id as assigned_clinic_id', 'ch.clinic_number', 'ch.ext_number', 'ch.price as hospital_price', 'hospital.name_ar as hospital_name_ar', 'clinic_building.name_ar as clinic_name_ar']);

        if (! $all) {
            if ($clinic > 0) $query->where('c.specialized_clinics_id', $clinic);
            if ($hospital > 0) $query->where('ch.hospital_id', $hospital);
        }
        if (($filters['name'] ?? '') !== '') $query->where('c.name_ar', 'like', '%'.trim((string) $filters['name']).'%');
        if (($filters['code'] ?? '') !== '') $query->where('c.code', trim((string) $filters['code']));

        return $query->orderBy('c.ranking')->orderBy('c.id')->paginate(16)->withQueryString();
    }

    public function toggle(int $doctor): void
    {
        $this->authorize();
        $record = DB::table('clinicians')->where('id', $doctor)->first();
        abort_if($record === null, 404);
        DB::table('clinicians')->where('id', $doctor)->update(['publish' => (int) ! (int) $record->publish, 'updated_by' => (int) session('hr_user_id', 0), 'updated_at' => now()]);
    }

    public function delete(int $doctor): void
    {
        $this->authorize();
        abort_unless((int) session('hr_user_level', 0) === 3 || in_array((int) session('hr_branch_id', 0), [5, 6, 15], true), 403);
        abort_unless(DB::table('clinicians')->where('id', $doctor)->exists(), 404);
        DB::transaction(function () use ($doctor): void {
            DB::table('clinician_hospitals')->where('clinicians_id', $doctor)->delete();
            DB::table('clinicians')->where('id', $doctor)->delete();
        });
    }
}
