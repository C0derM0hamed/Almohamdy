<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class License extends Model
{
    protected $table = 'licenses';

    /** @var list<string> */
    protected $fillable = [
        'companies_groups_id',
        'license_authority_id',
        'license_type_id',
        'license_number',
        'title',
        'responsible_user_id',
        'issue_date',
        'expiry_date',
        'status_id',
        'renewal_stage_id',
        'notes',
        'publish',
        'created_by',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiry_date' => 'date',
            'publish' => 'boolean',
        ];
    }

    public function authority(): BelongsTo
    {
        return $this->belongsTo(LicenseAuthority::class, 'license_authority_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(LicenseType::class, 'license_type_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(LicenseStatus::class, 'status_id');
    }

    public function renewalStage(): BelongsTo
    {
        return $this->belongsTo(LicenseRenewalStage::class, 'renewal_stage_id');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id', 'hr_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'hr_id');
    }

    public function hospitalBranch(): BelongsTo
    {
        return $this->belongsTo(CompanyGroup::class, 'companies_groups_id');
    }

    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'license_branches', 'license_id', 'branch_id')
            ->withTimestamps();
    }

    /** @deprecated Use departments(); retained for legacy integrations. */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'license_branches', 'license_id', 'branch_id')
            ->withTimestamps();
    }

    public function undertakings(): HasMany
    {
        return $this->hasMany(LicenseUndertaking::class, 'license_id');
    }

    public function currentUndertaking(): HasOne
    {
        return $this->hasOne(LicenseUndertaking::class, 'license_id')->latestOfMany();
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(LicenseRenewal::class, 'license_id');
    }

    public function currentRenewal(): HasOne
    {
        return $this->hasOne(LicenseRenewal::class, 'license_id')
            ->ofMany('id', 'max', static fn ($query) => $query->whereNull('completed_at'));
    }

    public function comments(): HasMany
    {
        return $this->hasMany(LicenseComment::class, 'license_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LicenseAttachment::class, 'license_id');
    }

    public function timelineEntries(): HasMany
    {
        return $this->hasMany(LicenseTimeline::class, 'license_id');
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(LicensePaymentRequest::class, 'license_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(LicenseNotification::class, 'license_id');
    }

    public function displayTitle(): string
    {
        $title = trim((string) $this->title);

        return $title !== '' ? $title : ($this->type?->localizedName() ?: '#'.$this->getKey());
    }
}
