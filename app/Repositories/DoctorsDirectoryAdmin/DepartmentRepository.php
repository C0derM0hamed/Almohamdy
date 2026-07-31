<?php

namespace App\Repositories\DoctorsDirectoryAdmin;

use App\Models\OutpatientClinicSection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DepartmentRepository
{
    /**
     * @var list<string>
     */
    private const LIST_COLUMNS = [
        'id',
        'outpatient_clinics_id',
        'specialized_clinics_id',
        'publish',
    ];

    /**
     * @var list<string>
     */
    private const FORM_COLUMNS = [
        'id',
        'outpatient_clinics_id',
        'specialized_clinics_id',
        'publish',
        'created_by',
        'updated_by',
    ];

    private function baseQuery(): Builder
    {
        return OutpatientClinicSection::query()->select(self::LIST_COLUMNS);
    }

    public function paginateFiltered(
        string $search,
        ?int $specialityId,
        ?string $publish,
        int $perPage,
    ): LengthAwarePaginator {
        return $this->baseQuery()
            ->when($specialityId !== null && $specialityId > 0, fn (Builder $q) => $q->where('specialized_clinics_id', $specialityId))
            ->when($publish !== null && $publish !== '', function (Builder $q) use ($publish) {
                $q->where('publish', $publish === '1' ? 1 : 0);
            })
            ->when($search !== '', function (Builder $q) use ($search) {
                $q->whereHas('outpatientClinic', function (Builder $inner) use ($search) {
                    $inner->where(function (Builder $name) use ($search) {
                        $name->where('name_ar', 'like', '%'.$search.'%')
                            ->orWhere('name_en', 'like', '%'.$search.'%');
                    });
                });
            })
            ->with([
                'speciality:id,subject_ar,subject_en',
                'outpatientClinic:id,name_ar,name_en',
            ])
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForEdit(int $id): ?OutpatientClinicSection
    {
        return OutpatientClinicSection::query()
            ->select(self::FORM_COLUMNS)
            ->whereKey($id)
            ->with([
                'speciality:id,subject_ar,subject_en',
                'outpatientClinic:id,name_ar,name_en',
            ])
            ->first();
    }

    /**
     * @param  array{specialized_clinics_id: int, outpatient_clinics_id: int, publish: int, created_by: int, updated_by: int}  $attributes
     */
    public function create(array $attributes): OutpatientClinicSection
    {
        $section = new OutpatientClinicSection;
        $section->specialized_clinics_id = $attributes['specialized_clinics_id'];
        $section->outpatient_clinics_id = $attributes['outpatient_clinics_id'];
        $section->publish = $attributes['publish'];
        $section->created_by = $attributes['created_by'];
        $section->updated_by = $attributes['updated_by'];
        $section->save();

        return $section;
    }

    /**
     * @param  array{specialized_clinics_id: int, outpatient_clinics_id: int, publish: int, updated_by: int}  $attributes
     */
    public function update(OutpatientClinicSection $section, array $attributes): OutpatientClinicSection
    {
        $section->specialized_clinics_id = $attributes['specialized_clinics_id'];
        $section->outpatient_clinics_id = $attributes['outpatient_clinics_id'];
        $section->publish = $attributes['publish'];
        $section->updated_by = $attributes['updated_by'];
        $section->save();

        return $section;
    }

    public function togglePublish(OutpatientClinicSection $section, int $updatedBy): OutpatientClinicSection
    {
        $section->publish = ((int) $section->publish) === 1 ? 0 : 1;
        $section->updated_by = $updatedBy;
        $section->save();

        return $section;
    }

    public function existsDuplicate(int $specialityId, int $outpatientClinicId, ?int $ignoreId = null): bool
    {
        return DB::table('outpatient_clinics_sections')
            ->where('specialized_clinics_id', $specialityId)
            ->where('outpatient_clinics_id', $outpatientClinicId)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }
}

