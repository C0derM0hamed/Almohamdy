<?php

namespace App\Repositories\DoctorsDirectory;

use App\Models\OutpatientClinic;
use App\Models\OutpatientClinicSection;
use App\Models\Speciality;

class DepartmentRepository
{
    /**
     * @var list<string>
     */
    private const COLUMNS = [
        'id',
        'outpatient_clinics_id',
        'specialized_clinics_id',
        'publish',
    ];

    public function findPublishedForSpeciality(int $departmentId, int $specialityId): ?OutpatientClinicSection
    {
        return OutpatientClinicSection::query()
            ->select(self::COLUMNS)
            ->where('publish', 1)
            ->whereKey($departmentId)
            ->where('specialized_clinics_id', $specialityId)
            ->with([
                'speciality:id,subject_ar,subject_en,clinics_id',
                'outpatientClinic:id,name_ar,name_en',
            ])
            ->first();
    }

    /**
     * @return array{opd: OutpatientClinic, speciality: Speciality}|null
     */
    public function findPublishedOpdSpeciality(int $opdId, int $specialityId): ?array
    {
        $opd = OutpatientClinic::query()
            ->select(['id', 'name_ar', 'name_en'])
            ->whereKey($opdId)
            ->where('publish', 1)
            ->first();

        $speciality = Speciality::query()
            ->select(['id', 'subject_ar', 'subject_en', 'clinics_id'])
            ->whereKey($specialityId)
            ->where('publish', '1')
            ->first();

        if ($opd === null || $speciality === null) {
            return null;
        }

        return [
            'opd' => $opd,
            'speciality' => $speciality,
        ];
    }
}
