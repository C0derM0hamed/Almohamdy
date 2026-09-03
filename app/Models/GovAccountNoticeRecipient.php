<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GovAccountNoticeRecipient extends Model
{
    protected $table = 'gov_account_notice_recipients';

    protected $guarded = ['id'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'viewed_at' => 'datetime', 'last_viewed_at' => 'datetime', 'view_count' => 'integer'];
    }

    public function notice(): BelongsTo
    {
        return $this->belongsTo(GovAccountNotice::class, 'notice_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'hr_id');
    }
}
