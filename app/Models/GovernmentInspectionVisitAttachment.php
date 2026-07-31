<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernmentInspectionVisitAttachment extends Model
{
    protected $table = 'government_inspection_visits_attachments';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'government_inspection_visits_id',
        'file_name',
        'name',
        'created_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(GovernmentInspectionVisit::class, 'government_inspection_visits_id');
    }

    public function displayLabel(): string
    {
        return trim((string) ($this->name ?: $this->file_name)) ?: '#'.$this->id;
    }
}
