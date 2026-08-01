<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmergencyNoticeType extends Model
{
    protected $table = 'notice_type';

    public $timestamps = false;

    protected $fillable = ['branch_id', 'name_en', 'name_ar', 'name_ch', 'info', 'publish'];
}
