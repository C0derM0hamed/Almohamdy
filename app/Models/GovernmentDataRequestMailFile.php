<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovernmentDataRequestMailFile extends Model
{
    protected $table = 'g_filesmail';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'file',
        'id_data',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(GovernmentDataRequest::class, 'id_data');
    }

    public function displayLabel(): string
    {
        return trim((string) ($this->name ?: $this->file)) ?: '#'.$this->id;
    }
}
