<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovAccountUndertaking extends Model
{
    protected $table = 'gov_account_undertakings';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['requested_at' => 'datetime', 'accepted_at' => 'datetime'];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(GovAccountRequest::class, 'request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'hr_id');
    }
}
