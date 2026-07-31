<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePackageAttachment extends Model
{
    protected $table = 'service_packages_attachments';

    protected $primaryKey = 'id';

    public $timestamps = false;

    const CREATED_AT = 'created_at';

    public function package(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class, 'service_packages_id');
    }

    public function url(): string
    {
        $base = trim((string) config('hm.hospital_services.attachments_path', '/files'), '/');

        return asset($base.'/'.ltrim((string) $this->file_name, '/'));
    }
}
