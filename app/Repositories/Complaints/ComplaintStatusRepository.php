<?php

namespace App\Repositories\Complaints;

use App\Models\ComplaintStatus;
use Illuminate\Support\Collection;

class ComplaintStatusRepository
{
    /**
     * @return Collection<int, ComplaintStatus>
     */
    public function published(): Collection
    {
        return ComplaintStatus::query()
            ->select(['id', 'name_en', 'name_ar', 'info'])
            ->where('publish', 1)
            ->orderBy('id')
            ->get();
    }
}
