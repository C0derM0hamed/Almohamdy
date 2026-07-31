<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientVacationBranchReply extends Model
{
    protected $table = 'client_vacations_branch_reply';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];

    public function vacation(): BelongsTo
    {
        return $this->belongsTo(EmployeeVacation::class, 'vac_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ApprovalStatus::class, 'status_id');
    }

    public function repliedAt(): ?Carbon
    {
        $timestamp = (int) $this->date;

        return $timestamp > 0 ? Carbon::createFromTimestamp($timestamp) : null;
    }
}
