<?php

namespace App\Repositories\Complaints;

use App\Models\ComplaintReply;
use Illuminate\Support\Collection;

class ComplaintReplyRepository
{
    /**
     * @return Collection<int, ComplaintReply>
     */
    public function repliesForComplaint(int $complaintId): Collection
    {
        return ComplaintReply::query()
            ->select([
                'id',
                'complaints_id',
                'complaint_status_id',
                'created_by',
                'created_at',
                'details',
                'defendant',
                'defendant_job',
            ])
            ->where('complaints_id', $complaintId)
            ->with([
                'status:id,name_en,name_ar,info',
                'creator:hr_id,hr_first_name,hr_last_name',
            ])
            ->orderBy('id')
            ->get();
    }
}
