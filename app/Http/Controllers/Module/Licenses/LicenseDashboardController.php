<?php

namespace App\Http\Controllers\Module\Licenses;

use App\Http\Controllers\Controller;
use App\Http\Requests\Licenses\LicenseIndexRequest;
use App\Services\Licenses\LicenseDashboardService;
use App\Services\Licenses\LicenseService;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class LicenseDashboardController extends Controller
{
    public function __construct(
        private readonly LicenseDashboardService $dashboard,
        private readonly LicenseService $licenses,
    ) {}

    public function index(LicenseIndexRequest $request): View
    {
        $filters = $request->filters();
        $metrics = $this->dashboard->metrics($filters);
        $critical = $metrics['topCritical'];
        foreach ($critical as $license) {
            $license->setAttribute('days_remaining', today()->diffInDays($license->expiry_date, false));
        }

        $finance = $metrics['finance'];
        $finance['average_close_days'] = round(((float) ($finance['average_close_hours'] ?? 0)) / 24, 1);

        return view('licenses.dashboard', $this->licenses->options() + [
            'filters' => $filters,
            'kpis' => $metrics['kpis'],
            'topRisks' => $critical,
            'financeKpis' => $finance,
            'charts' => [
                'by_department' => $this->chart($metrics['byDepartment']),
                'by_branch' => $this->chart($metrics['byDepartment']),
                'by_authority' => $this->chart($metrics['byAuthority']),
                'by_type' => $this->chart($metrics['byType']),
                'expiry_windows' => [
                    'labels' => ['30', '60', '90'],
                    'data' => array_values($metrics['expiryBuckets']),
                ],
            ],
        ]);
    }

    /** @return array{labels:list<string>,data:list<int>} */
    private function chart(Collection $rows): array
    {
        $field = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';

        return [
            'labels' => $rows->map(fn ($row) => (string) ($row->{$field} ?: $row->name_ar ?: $row->name_en ?: '—'))->values()->all(),
            'data' => $rows->map(fn ($row) => (int) $row->total)->values()->all(),
        ];
    }
}
