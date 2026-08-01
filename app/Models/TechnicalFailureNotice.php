<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicalFailureNotice extends Model
{
    protected $table = 'technical_failure_notice';

    public $timestamps = false;

    protected $fillable = [
        'branch_id',
        'companies_groups_id',
        'user_id',
        'date_time',
        'technical_failure_notice_sections',
        'technical_failure_notice_type',
        'technical_failure_notice_platform',
        'technical_failure_notice_service_type',
        'other',
        'notice',
        'status',
        'file_name',
    ];

    protected $casts = [
        'id' => 'integer',
        'branch_id' => 'integer',
        'companies_groups_id' => 'integer',
        'user_id' => 'integer',
        'status' => 'integer',
    ];
}
