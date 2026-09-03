<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseComment extends Model
{
    protected $table = 'license_comments';

    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = ['license_id', 'user_id', 'body', 'publish'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['publish' => 'boolean', 'created_at' => 'datetime'];
    }

    public function license(): BelongsTo
    {
        return $this->belongsTo(License::class, 'license_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'hr_id');
    }
}
