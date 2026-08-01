<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingConfirmationAction extends Model
{
    protected $table = 'training_confirmation_actions';

    public $timestamps = false;

    protected $fillable = ['training_confirmation_id', 'status_id', 'branch_id', 'details', 'created_by', 'created_at'];

    public function status(): BelongsTo
    {
        return $this->belongsTo(TrainingConfirmationStatus::class, 'status_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'hr_id');
    }
}
