<?php

namespace App\Services\Licenses;

use App\Repositories\Licenses\LicensePaymentRepository;
use App\Repositories\Licenses\LicenseRepository;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LicenseDashboardService
{
    public function __construct(
        private readonly LicenseRepository $licenses,
        private readonly LicensePaymentRepository $payments,
    ) {}

    /** @param array<string,mixed> $filters @return array<string,mixed> */
    public function metrics(array $filters = []): array
    {
        $base = $this->licenses->filteredQuery($filters);

        return [
            'kpis' => [
                'total' => (clone $base)->count(),
                'active' => $this->statusCount($base, 'active'),
                'near_expiry' => $this->statusCount($base, 'near_expiry'),
                'under_renewal' => $this->statusCount($base, 'under_renewal'),
                'expired' => $this->statusCount($base, 'expired'),
            ],
            'byBranch' => $this->byBranch($base),
            'byAuthority' => $this->byReference($base, 'license_authorities', 'license_authority_id'),
            'byType' => $this->byReference($base, 'license_types', 'license_type_id'),
            'expiryBuckets' => $this->expiryBuckets($base),
            'topCritical' => (clone $base)->with(['authority', 'type', 'status', 'renewalStage', 'responsibleUser', 'branches'])
                ->orderByRaw('CASE WHEN expiry_date < ? THEN 0 WHEN expiry_date <= ? THEN 1 ELSE 2 END', [today()->toDateString(), today()->addDays(30)->toDateString()])
                ->orderBy('expiry_date')->limit(10)->get()
                ->each(fn ($license) => $license->setAttribute('days_remaining', today()->diffInDays($license->expiry_date, false))),
            'finance' => $this->financeMetrics(),
        ];
    }

    private function statusCount(Builder $base, string $code): int
    {
        return (clone $base)->whereHas('status', fn (Builder $status) => $status->where('code', $code))->count();
    }

    private function byBranch(Builder $base): Collection
    {
        return DB::query()->fromSub((clone $base)->select('licenses.id'), 'visible_licenses')
            ->join('license_branches', 'license_branches.license_id', '=', 'visible_licenses.id')
            ->join('branches', 'branches.id', '=', 'license_branches.branch_id')
            ->select(['branches.id', 'branches.name_ar', 'branches.name_en', DB::raw('COUNT(DISTINCT visible_licenses.id) AS total')])
            ->groupBy('branches.id', 'branches.name_ar', 'branches.name_en')->orderByDesc('total')->get();
    }

    private function byReference(Builder $base, string $table, string $foreignKey): Collection
    {
        return DB::query()->fromSub((clone $base)->select(['licenses.id', 'licenses.'.$foreignKey]), 'visible_licenses')
            ->join($table, $table.'.id', '=', 'visible_licenses.'.$foreignKey)
            ->select([$table.'.id', $table.'.name_ar', $table.'.name_en', DB::raw('COUNT(visible_licenses.id) AS total')])
            ->groupBy($table.'.id', $table.'.name_ar', $table.'.name_en')->orderByDesc('total')->get();
    }

    /** @return array<string,int> */
    private function expiryBuckets(Builder $base): array
    {
        $today = today();

        return [
            '30' => (clone $base)->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', $today->copy()->addDays(30))->count(),
            '60' => (clone $base)->whereDate('expiry_date', '>', $today->copy()->addDays(30))->whereDate('expiry_date', '<=', $today->copy()->addDays(60))->count(),
            '90' => (clone $base)->whereDate('expiry_date', '>', $today->copy()->addDays(60))->whereDate('expiry_date', '<=', $today->copy()->addDays(90))->count(),
        ];
    }

    /** @return array<string,int|float> */
    private function financeMetrics(): array
    {
        $base = $this->payments->scopedQuery();
        $counts = (clone $base)->join('license_payment_request_statuses as status', 'status.id', '=', 'license_payment_requests.status_id')
            ->select('status.code', DB::raw('COUNT(*) as total'))->groupBy('status.code')->pluck('total', 'status.code');
        $paid = (clone $base)->whereNotNull('closed_at')->get(['created_at', 'closed_at']);
        $averageHours = $paid->isEmpty() ? 0.0 : round($paid->average(fn ($row) => CarbonImmutable::parse($row->created_at)->diffInMinutes(CarbonImmutable::parse($row->closed_at)) / 60), 1);

        return [
            'open' => (int) ($counts['received'] ?? 0) + (int) ($counts['in_progress'] ?? 0) + (int) ($counts['needs_documents'] ?? 0),
            'in_progress' => (int) ($counts['in_progress'] ?? 0),
            'needs_documents' => (int) ($counts['needs_documents'] ?? 0),
            'paid' => (int) ($counts['paid'] ?? 0),
            'average_close_hours' => $averageHours,
            'average_close_days' => round($averageHours / 24, 1),
        ];
    }
}
