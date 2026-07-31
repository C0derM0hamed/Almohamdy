<?php

namespace App\Repositories\DoctorsDirectoryAdmin;

use App\Models\Speciality;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

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
        'created_at',
        'updated_at',
    ];

    /**
     * @var list<string>
     */
    private const FORM_COLUMNS = [
        'id',
        'clinics_id',
        'subject_ar',
        'subject_en',
        'publish',
        'companies_groups_id',
        'created_at',
        'updated_at',
    ];

    public function baseQuery(): Builder
    {
        return Speciality::query()->select(self::LIST_COLUMNS);
    }

    /**
     * @return array{total: int, published: int, unpublished: int}
     */
    public function dashboardCounts(): array
    {
        $total = Speciality::query()->count();
        $published = Speciality::query()->where('publish', '1')->count();

        return [
            'total' => $total,
            'published' => $published,
            'unpublished' => max(0, $total - $published),
        ];
    }

    public function paginateFiltered(string $search, ?string $publish, int $perPage): LengthAwarePaginator
    {
        $nameColumn = app()->getLocale() === 'ar' ? 'subject_ar' : 'subject_en';

        return $this->baseQuery()
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('subject_ar', 'like', '%'.$search.'%')
                        ->orWhere('subject_en', 'like', '%'.$search.'%');
                });
            })
            ->when($publish !== null && $publish !== '', function (Builder $query) use ($publish) {
                $query->where('publish', $publish === '1' ? '1' : '0');
            })
            ->with(['clinic:id,name_ar,name_en'])
            ->withCount([
                'outpatientSections as departments_count',
                'clinicians as doctors_count',
            ])
            ->orderBy($nameColumn)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForEdit(int $id): ?Speciality
    {
        return Speciality::query()
            ->select(self::FORM_COLUMNS)
            ->with(['clinic:id,name_ar,name_en'])
            ->whereKey($id)
            ->first();
    }

    /**
     * @param  array{clinics_id: int, subject_ar: string, subject_en: string, publish: string, companies_groups_id: int, created_by: int, updated_by: int}  $attributes
     */
    public function create(array $attributes): Speciality
    {
        $speciality = new Speciality;
        $speciality->clinics_id = $attributes['clinics_id'];
        $speciality->subject_ar = $attributes['subject_ar'];
        $speciality->subject_en = $attributes['subject_en'];
        $speciality->publish = $attributes['publish'];
        $speciality->companies_groups_id = $attributes['companies_groups_id'];
        $speciality->created_by = $attributes['created_by'];
        $speciality->updated_by = $attributes['updated_by'];
        $speciality->save();

        return $speciality;
    }

    /**
     * @param  array{clinics_id: int, subject_ar: string, subject_en: string, publish: string, updated_by: int}  $attributes
     */
    public function update(Speciality $speciality, array $attributes): Speciality
    {
        $speciality->clinics_id = $attributes['clinics_id'];
        $speciality->subject_ar = $attributes['subject_ar'];
        $speciality->subject_en = $attributes['subject_en'];
        $speciality->publish = $attributes['publish'];
        $speciality->updated_by = $attributes['updated_by'];
        $speciality->save();

        return $speciality;
    }

    public function togglePublish(Speciality $speciality): Speciality
    {
        $speciality->publish = $speciality->publish === '1' ? '0' : '1';
        $speciality->updated_by = (int) session('hr_user_id', 0);
        $speciality->save();

        return $speciality;
    }
}
