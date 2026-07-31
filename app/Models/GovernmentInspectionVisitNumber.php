<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GovernmentInspectionVisitNumber extends Model
{
    protected $table = 'government_inspection_visits_numbers';

    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'companies_groups_id',
        'branch_id',
        'abuses_status',
        'notes_status',
        'representative_name',
        'subject',
        'report',
        'token',
        'created_at',
        'created_by',
        'status',
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

    public function visits(): HasMany
    {
        return $this->hasMany(GovernmentInspectionVisit::class, 'visit_number');
    }

    public function hasViolations(): bool
    {
        return (string) $this->abuses_status === '1';
    }

    public function hasNotes(): bool
    {
        return (string) $this->notes_status === '1';
    }
}
