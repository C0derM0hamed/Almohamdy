<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorporateCommunicationOutgoingLetterSupplementary extends Model
{
    protected $table = 'corporate_communications_outgoing_letters_supplementary';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'corporate_communications_outgoing_letters_id',
        'date',
        'branch_id',
        'details',
        'created_by',
        'created_at',
        'serial_no',
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

    public function letter(): BelongsTo
    {
        return $this->belongsTo(
            CorporateCommunicationOutgoingLetter::class,
            'corporate_communications_outgoing_letters_id'
        );
    }
}
