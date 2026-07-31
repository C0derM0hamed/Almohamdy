<?php

namespace App\Repositories\HospitalServices;

use App\Models\AdmissionRoom;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdmissionRoomRepository
{
    /**
     * @var list<string>
     */
    private const LIST_COLUMNS = [
        'id',
        'admission_status_id',
        'name_ar',
        'name_en',
        'code',
        'price',
        'publish',
    ];

    public function paginatePublished(string $search, int $perPage): LengthAwarePaginator
    {
        $nameColumn = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';

        return AdmissionRoom::query()
            ->select(self::LIST_COLUMNS)
            ->where('publish', 1)
            ->where('admission_status_id', 2)
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('code', 'like', '%'.$search.'%')
                        ->orWhere('name_ar', 'like', '%'.$search.'%')
                        ->orWhere('name_en', 'like', '%'.$search.'%');
                });
            })
            ->orderBy($nameColumn)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function countPublished(): int
    {
        return AdmissionRoom::query()
            ->where('publish', 1)
            ->where('admission_status_id', 2)
            ->count();
    }
}
