<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernmentCircularAttachment extends Model
{
    protected $table = 'government_circulars_attachments';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'government_circulars_id',
        'circulars_file',
    ];

    public function circular(): BelongsTo
    {
        return $this->belongsTo(GovernmentCircular::class, 'government_circulars_id');
    }
}
