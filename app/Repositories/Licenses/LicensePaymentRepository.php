<?php

namespace App\Repositories\Licenses;

use App\Models\LicensePaymentEvent;
use App\Models\LicensePaymentRequest;
use App\Models\LicensePaymentRequestStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LicensePaymentRepository
{
    public function __construct(private readonly LicenseRepository $licenses) {}

    /** @return Builder<LicensePaymentRequest> */
    public function scopedQuery(): Builder
    {
        return LicensePaymentRequest::query()
            ->whereIn('license_id', $this->licenses->scopedQuery(true)->select('licenses.id'));
    }

    /** @param array<string,mixed> $filters */
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return $this->scopedQuery()
            ->with(['license.authority', 'license.type', 'license.responsibleUser', 'status', 'requester'])
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $nested) use ($search): void {
                $nested->where('invoice_number', 'like', '%'.$search.'%')
                    ->orWhereHas('license', fn (Builder $license) => $license
                        ->where('license_number', 'like', '%'.$search.'%')
                        ->orWhere('title', 'like', '%'.$search.'%'));
            }))
            ->when(isset($filters['status']), function (Builder $query) use ($filters): void {
                $status = $filters['status'];
                if (is_numeric($status)) {
                    $query->where('status_id', (int) $status);
                } else {
                    $query->whereHas('status', fn (Builder $relation) => $relation->where('code', $status));
                }
            })
            ->when(isset($filters['branch_id']), fn (Builder $query) => $query->whereHas('license.branches', fn (Builder $branches) => $branches->where('branches.id', (int) $filters['branch_id'])))
            ->when(isset($filters['license_id']), fn (Builder $query) => $query->where('license_id', (int) $filters['license_id']))
            ->when(isset($filters['from_date']), fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['from_date']))
            ->when(isset($filters['to_date']), fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['to_date']))
            ->orderByDesc('created_at')->paginate($perPage)->withQueryString();
    }

    /** @return array<string,int> */
    public function statusCounters(): array
    {
        return $this->scopedQuery()
            ->join('license_payment_request_statuses as payment_statuses', 'payment_statuses.id', '=', 'license_payment_requests.status_id')
            ->selectRaw('payment_statuses.code, COUNT(*) as aggregate')
            ->groupBy('payment_statuses.code')
            ->pluck('aggregate', 'payment_statuses.code')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    public function findForDetail(int $id): ?LicensePaymentRequest
    {
        return $this->scopedQuery()->with([
            'license.authority', 'license.type', 'license.branches', 'license.responsibleUser',
            'status', 'requester', 'renewal', 'events.status', 'events.creator', 'attachments.uploader',
        ])->whereKey($id)->first();
    }

    public function findOrFail(int $id): LicensePaymentRequest
    {
        return $this->findForDetail($id) ?? abort(404);
    }

    public function statusId(string $code): int
    {
        return (int) LicensePaymentRequestStatus::query()->where('code', $code)->value('id');
    }

    public function statusCode(int $id): ?string
    {
        return LicensePaymentRequestStatus::query()->whereKey($id)->value('code');
    }

    /** @return Collection<int,LicensePaymentRequestStatus> */
    public function statusOptions(): Collection
    {
        return LicensePaymentRequestStatus::query()->where('publish', true)->orderBy('ranking')->get();
    }

    /** @param array<string,mixed> $attributes */
    public function create(array $attributes): LicensePaymentRequest
    {
        return LicensePaymentRequest::query()->create($attributes);
    }

    /** @param array<string,mixed> $attributes */
    public function event(array $attributes): LicensePaymentEvent
    {
        return LicensePaymentEvent::query()->create($attributes + [
            'created_by' => (int) session('hr_user_id', 0) ?: null,
            'created_at' => now(),
        ]);
    }
}
