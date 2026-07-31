<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsenceNotificationServiceActionType extends Model
{
    protected $table = 'absence_notification_service_action_type';

    protected $primaryKey = 'id';

    public $timestamps = false;

    public function localizedName(): string
    {
        if (app()->getLocale() === 'ar') {
            return trim((string) ($this->name_ar ?: $this->name_en));
        }

        return trim((string) ($this->name_en ?: $this->name_ar));
    }
}
