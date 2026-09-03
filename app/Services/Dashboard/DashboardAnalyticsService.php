<?php

namespace App\Services\Dashboard;

use App\Services\Auth\LegacyScopeService;
use App\Services\Auth\PermissionService;
use App\Support\CorporateCommunications\CorporateCommunicationPermissions;
use App\Support\EmployeeLeave\EmployeeLeavePermissions;
use App\Support\MedicalAppointments\MedicalAppointmentScope;
use App\Support\Training\TrainingPermissions;
use App\Support\WorkAbsenceNotification\WorkAbsenceNotificationPermissions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Aggregates real per-module analytics for the main dashboard.
 *
 * Every query is scoped exactly like the module listings themselves:
 * companies_groups_id from the session always, branch_id from the session
 * unless the user is an administrator (hr_user_level 3). Modules are only
 * aggregated when the same permission / legacy-scope gate that exposes them
 * in the sidebar passes, so the dashboard can never show data the user
 * cannot open through the menu.
 */
class DashboardAnalyticsService
{
    private const CACHE_TTL_SECONDS = 300;

    private const TREND_DAYS = 90;

    public function __construct(
        private readonly PermissionService $permissions,
        private readonly LegacyScopeService $legacyScopes,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        return Cache::remember($this->cacheKey(), self::CACHE_TTL_SECONDS, fn (): array => $this->build());
    }

    private function cacheKey(): string
    {
        $permissionSignature = md5(implode('|', [
            (int) session('hr_user_level', 0),
            (int) session('hr_branch_id', 0),
            (int) session('companies_groups_id', 0),
            implode(',', array_map(
                fn (array $module): string => $module['key'],
                $this->allowedModules(),
            )),
        ]));

        return 'hm.dashboard.analytics.v2.'.$permissionSignature;
    }

    /**
     * @return array<string, mixed>
     */
    private function build(): array
    {
        $modules = $this->allowedModules();
        $today = Carbon::today();
        $sinceTrend = $today->copy()->subDays(self::TREND_DAYS - 1);
        $since30 = $today->copy()->subDays(29);
        $since7 = $today->copy()->subDays(6);
        $monthStart = $today->copy()->startOfMonth();

        $stats = [];
        $daily = [];

        foreach ($modules as $module) {
            $stats[$module['key']] = $this->moduleStats($module, $since7, $since30, $monthStart);
            $daily[$module['key']] = $this->moduleDailyCounts($module, $sinceTrend);
        }

        return [
            'kpis' => $this->kpis($stats),
            'trend' => $this->trend($modules, $daily, $sinceTrend, $today),
            'moduleTotals' => $this->moduleTotals($modules, $stats),
            'statusByModule' => $this->statusByModule($modules, $stats),
            'branchComparison' => $this->branchComparison(),
            'attention' => $this->attention($modules, $stats),
            'latest' => $this->latestActivity($modules),
            'generatedAt' => Carbon::now(),
        ];
    }

    /**
     * Registry of every module the dashboard can aggregate. Each entry is
     * only used when its gate passes for the current session.
     *
     * @return list<array<string, mixed>>
     */
    private function allowedModules(): array
    {
        $registry = [
            [
                'key' => 'complaints',
                'table' => 'complaints',
                'date' => 'created_at',
                'pending' => [0, 1, 2, 3, 4],
                'completed' => [5, 6],
                'attention' => [4],
                'attention_key' => 'complaints_escalated',
                'route' => 'modules.complaints',
                'company_scoped' => true,
                'gate' => fn (): bool => $this->permissions->can('complaints'),
            ],
            [
                'key' => 'inquiries',
                'table' => 'inquiries_and_services',
                'date' => 'FROM_UNIXTIME(CAST(`date` AS UNSIGNED))',
                'raw_date' => true,
                'pending' => [999999, 1, 0, 3],
                'completed' => [4, 5, 6],
                'attention' => [999999, 1, 0],
                'attention_key' => 'inquiries_new',
                'route' => 'modules.inquiries.incoming.index',
                'company_scoped' => true,
                'gate' => fn (): bool => $this->permissions->can('inquiries_and_services'),
            ],
            [
                'key' => 'correspondence',
                'table' => 'corporate_communications',
                'date' => 'created_at',
                'pending' => [1, 2, 5, 9, 11, 12],
                'completed' => [3, 4, 6, 7, 8, 10],
                'attention' => [11, 12],
                'attention_key' => 'correspondence_escalated',
                'route' => 'modules.correspondence.index',
                'company_scoped' => true,
                'gate' => fn (): bool => $this->permissions->can(CorporateCommunicationPermissions::CORRESPONDENCE),
            ],
            [
                'key' => 'outgoing_letters',
                'table' => 'corporate_communications_outgoing_letters',
                'date' => 'created_at',
                'route' => 'modules.outgoing-correspondence.index',
                'company_scoped' => true,
                'gate' => fn (): bool => $this->permissions->can(CorporateCommunicationPermissions::OUTGOING_CORRESPONDENCE)
                    || $this->legacyScopes->allows(LegacyScopeService::CORPORATE_OUTGOING),
            ],
            [
                'key' => 'government_circulars',
                'table' => 'government_circulars',
                'date' => 'created_at',
                'route' => 'modules.government-circulars.index',
                'company_scoped' => true,
                'gate' => fn (): bool => $this->permissions->can(CorporateCommunicationPermissions::GOVERNMENT_CIRCULARS),
            ],
            [
                'key' => 'inspection_visits',
                'table' => 'government_inspection_visits',
                'date' => 'created_at',
                'pending' => [1, 7],
                'completed' => [2, 5, 6],
                'attention' => [3, 4],
                'attention_key' => 'inspection_overdue',
                'route' => 'modules.inspection-visits.index',
                'company_scoped' => true,
                'gate' => fn (): bool => $this->permissions->can(CorporateCommunicationPermissions::INSPECTION_VISITS),
            ],
            [
                'key' => 'data_requests',
                'table' => 'g_data',
                'date' => "SUBSTR(create_at, 1, 10)",
                'raw_date' => true,
                'route' => 'modules.data-requests.index',
                'company_scoped' => true,
                'gate' => fn (): bool => $this->permissions->can(CorporateCommunicationPermissions::DATA_REQUESTS),
            ],
            [
                'key' => 'technical_failures',
                'table' => 'technical_failure_notice',
                'date' => 'FROM_UNIXTIME(CAST(date_time AS UNSIGNED))',
                'raw_date' => true,
                'pending' => [0, 1, 2],
                'completed' => [3],
                'attention' => [0, 2],
                'attention_key' => 'technical_failures_open',
                'route' => 'modules.technical-failures.index',
                'company_scoped' => true,
                'gate' => fn (): bool => $this->permissions->can('technical_failure_notice')
                    || $this->legacyScopes->allows(LegacyScopeService::TECHNICAL_FAILURE),
            ],
            [
                'key' => 'work_absence',
                'table' => 'absence_notification_service',
                'date' => 'FROM_UNIXTIME(CAST(`date` AS UNSIGNED))',
                'raw_date' => true,
                'status_column' => 'action_type',
                'pending_null' => true,
                'attention_key' => 'work_absence_unprocessed',
                'route' => 'modules.work-absence.notifications.index',
                'company_scoped' => true,
                'gate' => fn (): bool => $this->permissions->can(WorkAbsenceNotificationPermissions::VIEW)
                    || $this->legacyScopes->allows(LegacyScopeService::EMPLOYEE_SERVICES),
            ],
            [
                'key' => 'training',
                'table' => 'training_confirmation',
                'date' => 'created_at',
                'pending' => [1, 2, 8],
                'completed' => [3, 4, 5, 6, 7],
                'route' => 'modules.training.management.index',
                'company_scoped' => true,
                'gate' => fn (): bool => $this->permissions->can(TrainingPermissions::MANAGEMENT)
                    || $this->permissions->can(TrainingPermissions::COORDINATION),
            ],
            [
                'key' => 'employee_leave',
                'table' => 'emp_vacations',
                'date' => 'FROM_UNIXTIME(CAST(`date` AS UNSIGNED))',
                'raw_date' => true,
                'status_column' => 'hr_approval',
                'pending' => [(int) config('hm.employee_leave.approval_status_ids.pending', 4)],
                'completed' => [
                    (int) config('hm.employee_leave.approval_status_ids.approved', 1),
                    (int) config('hm.employee_leave.approval_status_ids.rejected', 2),
                    (int) config('hm.employee_leave.approval_status_ids.partial', 3),
                ],
                'pending_null' => true,
                'attention_key' => 'leave_pending',
                'route' => 'modules.leave.requests.index',
                'company_scoped' => true,
                'gate' => fn (): bool => $this->permissions->can(EmployeeLeavePermissions::VIEW),
            ],
            [
                'key' => 'medical_appointments',
                'table' => 'book_a_medical_appointment',
                'date' => 'created_at',
                'pending' => [1, 2, 4, 5, 6, 11],
                'completed' => [3, 8, 13, 14],
                'attention' => [4, 11],
                'attention_key' => 'appointments_reschedule',
                'route' => 'modules.medical-appointments.index',
                'company_scoped' => true,
                'gate' => fn (): bool => $this->permissions->isAdmin()
                    || (in_array((int) session('hr_branch_id'), MedicalAppointmentScope::BRANCH_IDS, true)
                        && in_array((int) session('companies_groups_id'), MedicalAppointmentScope::COMPANY_IDS, true)),
            ],
        ];

        return array_values(array_filter($registry, fn (array $module): bool => (bool) $module['gate']()));
    }

    /**
     * Same scope the module repositories apply: always the session company,
     * and the session branch unless the user is a level-3 administrator.
     */
    private function scoped(string $table): \Illuminate\Database\Query\Builder
    {
        $query = DB::table($table)
            ->where('companies_groups_id', (int) session('companies_groups_id', 0));

        if ((int) session('hr_user_level', 0) !== 3 && (int) session('hr_branch_id', 0) > 0) {
            $query->where('branch_id', (int) session('hr_branch_id'));
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $module
     * @return array{total: int, last7: int, last30: int, mtd: int, pending: int|null, completed: int|null, mtd_pending: int|null, mtd_completed: int|null, attention: int|null}
     */
    private function moduleStats(array $module, Carbon $since7, Carbon $since30, Carbon $monthStart): array
    {
        $dateExpr = $module['date'] === 'created_at' ? '`created_at`' : $module['date'];
        $statusColumn = $module['status_column'] ?? 'status';

        $selects = [
            'COUNT(*) AS total',
            "SUM(CASE WHEN {$dateExpr} >= ? THEN 1 ELSE 0 END) AS last7",
            "SUM(CASE WHEN {$dateExpr} >= ? THEN 1 ELSE 0 END) AS last30",
            "SUM(CASE WHEN {$dateExpr} >= ? THEN 1 ELSE 0 END) AS mtd",
        ];
        $bindings = [$since7->toDateString(), $since30->toDateString(), $monthStart->toDateString()];

        $hasStatuses = isset($module['pending']) || ! empty($module['pending_null']);

        if ($hasStatuses) {
            $pendingCondition = $this->statusCondition($statusColumn, $module['pending'] ?? [], ! empty($module['pending_null']));
            $completedCondition = $this->statusCondition($statusColumn, $module['completed'] ?? [], false);
            $selects[] = "SUM(CASE WHEN {$pendingCondition} THEN 1 ELSE 0 END) AS pending";
            $selects[] = "SUM(CASE WHEN {$completedCondition} THEN 1 ELSE 0 END) AS completed";
            $selects[] = "SUM(CASE WHEN {$pendingCondition} AND {$dateExpr} >= ? THEN 1 ELSE 0 END) AS mtd_pending";
            $selects[] = "SUM(CASE WHEN {$completedCondition} AND {$dateExpr} >= ? THEN 1 ELSE 0 END) AS mtd_completed";
            $bindings[] = $monthStart->toDateString();
            $bindings[] = $monthStart->toDateString();
        }

        if (isset($module['attention'])) {
            $selects[] = 'SUM(CASE WHEN '.$this->statusCondition($statusColumn, $module['attention'], false).' THEN 1 ELSE 0 END) AS attention';
        }

        $row = $this->scoped($module['table'])
            ->selectRaw(implode(', ', $selects), $bindings)
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'last7' => (int) ($row->last7 ?? 0),
            'last30' => (int) ($row->last30 ?? 0),
            'mtd' => (int) ($row->mtd ?? 0),
            'pending' => $hasStatuses ? (int) ($row->pending ?? 0) : null,
            'completed' => $hasStatuses ? (int) ($row->completed ?? 0) : null,
            'mtd_pending' => $hasStatuses ? (int) ($row->mtd_pending ?? 0) : null,
            'mtd_completed' => $hasStatuses ? (int) ($row->mtd_completed ?? 0) : null,
            'attention' => isset($module['attention'])
                ? (int) ($row->attention ?? 0)
                : (! empty($module['pending_null']) && isset($module['attention_key']) ? (int) ($row->pending ?? 0) : null),
        ];
    }

    /**
     * @param  list<int>  $statuses
     */
    private function statusCondition(string $column, array $statuses, bool $includeNull): string
    {
        $parts = [];

        if ($statuses !== []) {
            $parts[] = "`{$column}` IN (".implode(',', array_map('intval', $statuses)).')';
        }

        if ($includeNull) {
            $parts[] = "`{$column}` IS NULL";
        }

        return $parts === [] ? '1 = 0' : '('.implode(' OR ', $parts).')';
    }

    /**
     * @param  array<string, mixed>  $module
     * @return array<string, int> map of Y-m-d => count
     */
    private function moduleDailyCounts(array $module, Carbon $since30): array
    {
        $dateExpr = $module['date'] === 'created_at' ? '`created_at`' : $module['date'];

        return $this->scoped($module['table'])
            ->selectRaw("DATE({$dateExpr}) AS day, COUNT(*) AS n")
            ->whereRaw("{$dateExpr} >= ?", [$since30->toDateString()])
            ->groupBy('day')
            ->pluck('n', 'day')
            ->map(fn ($n): int => (int) $n)
            ->all();
    }

    /**
     * @param  array<string, array<string, int|null>>  $stats
     * @return list<array<string, mixed>>
     */
    private function kpis(array $stats): array
    {
        $sum = fn (string $field): int => array_sum(array_map(
            fn (array $row): int => (int) ($row[$field] ?? 0),
            $stats,
        ));

        // Month-to-date growth: MTD records relative to the pre-month baseline.
        $growth = function (int $value, int $mtd): int {
            if ($mtd <= 0) {
                return 0;
            }

            $baseline = $value - $mtd;

            return $baseline > 0 ? (int) round($mtd / $baseline * 100) : 100;
        };

        $rows = [
            ['key' => 'total', 'value' => $sum('total'), 'mtd' => $sum('mtd'), 'icon' => 'bi-folder2-open', 'variant' => 'primary'],
            ['key' => 'last7', 'value' => $sum('last7'), 'mtd' => $sum('mtd'), 'icon' => 'bi-activity', 'variant' => 'dark'],
            ['key' => 'pending', 'value' => $sum('pending'), 'mtd' => $sum('mtd_pending'), 'icon' => 'bi-clock', 'variant' => 'primary'],
            ['key' => 'completed', 'value' => $sum('completed'), 'mtd' => $sum('mtd_completed'), 'icon' => 'bi-check2-circle', 'variant' => 'dark'],
        ];

        foreach ($rows as $i => $row) {
            $rows[$i]['growth'] = $row['key'] === 'last7'
                ? $growth($sum('total'), $sum('mtd'))
                : $growth((int) $row['value'], (int) $row['mtd']);
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $modules
     * @param  array<string, array<string, int>>  $daily
     * @return array{labels: list<string>, total: list<int>, last7: int, last30: int}
     */
    private function trend(array $modules, array $daily, Carbon $sinceTrend, Carbon $today): array
    {
        $labels = [];
        $totals = [];

        for ($day = $sinceTrend->copy(); $day->lte($today); $day->addDay()) {
            $key = $day->toDateString();
            $labels[] = $key;
            $sum = 0;

            foreach ($modules as $module) {
                $sum += $daily[$module['key']][$key] ?? 0;
            }

            $totals[] = $sum;
        }

        return [
            'labels' => $labels,
            'total' => $totals,
            'last7' => array_sum(array_slice($totals, -7)),
            'last30' => array_sum(array_slice($totals, -30)),
        ];
    }

    /**
     * All-time record count per permitted module (doughnut source).
     *
     * @param  list<array<string, mixed>>  $modules
     * @param  array<string, array<string, int|null>>  $stats
     * @return list<array{key: string, label: string, total: int, url: string|null}>
     */
    private function moduleTotals(array $modules, array $stats): array
    {
        $totals = [];

        foreach ($modules as $module) {
            $totals[] = [
                'key' => $module['key'],
                'label' => __('dashboard.analytics.modules.'.$module['key']),
                'total' => (int) $stats[$module['key']]['total'],
                'url' => $this->moduleUrl($module),
            ];
        }

        usort($totals, fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        return $totals;
    }

    /**
     * Pending vs completed per module that has status semantics.
     *
     * @param  list<array<string, mixed>>  $modules
     * @param  array<string, array<string, int|null>>  $stats
     * @return list<array{key: string, label: string, pending: int, completed: int}>
     */
    private function statusByModule(array $modules, array $stats): array
    {
        $rows = [];

        foreach ($modules as $module) {
            $stat = $stats[$module['key']];

            if ($stat['pending'] === null && $stat['completed'] === null) {
                continue;
            }

            $rows[] = [
                'key' => $module['key'],
                'label' => __('dashboard.analytics.modules.'.$module['key']),
                'pending' => (int) $stat['pending'],
                'completed' => (int) $stat['completed'],
            ];
        }

        usort($rows, fn (array $a, array $b): int => ($b['pending'] + $b['completed']) <=> ($a['pending'] + $a['completed']));

        return $rows;
    }

    /**
     * Administrator-only branch comparison over the two highest-volume
     * modules. Never built for branch users, so nothing can leak.
     *
     * @return array{labels: list<string>, complaints: list<int>, inquiries: list<int>}|null
     */
    private function branchComparison(): ?array
    {
        if ((int) session('hr_user_level', 0) !== 3) {
            return null;
        }

        $companyId = (int) session('companies_groups_id', 0);
        $nameColumn = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';

        $complaints = DB::table('complaints')
            ->where('companies_groups_id', $companyId)
            ->selectRaw('branch_id, COUNT(*) AS n')
            ->groupBy('branch_id')
            ->pluck('n', 'branch_id');

        $inquiries = DB::table('inquiries_and_services')
            ->where('companies_groups_id', $companyId)
            ->selectRaw('branch_id, COUNT(*) AS n')
            ->groupBy('branch_id')
            ->pluck('n', 'branch_id');

        $branchIds = $complaints->keys()->merge($inquiries->keys())->unique()
            ->sortByDesc(fn (int $id): int => (int) ($complaints[$id] ?? 0) + (int) ($inquiries[$id] ?? 0))
            ->take(8)
            ->values();

        if ($branchIds->isEmpty()) {
            return null;
        }

        $names = DB::table('branches')
            ->whereIn('id', $branchIds)
            ->pluck($nameColumn, 'id');

        return [
            'labels' => $branchIds->map(fn (int $id): string => (string) ($names[$id] ?? "#{$id}"))->all(),
            'complaints' => $branchIds->map(fn (int $id): int => (int) ($complaints[$id] ?? 0))->all(),
            'inquiries' => $branchIds->map(fn (int $id): int => (int) ($inquiries[$id] ?? 0))->all(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $modules
     * @param  array<string, array<string, int|null>>  $stats
     * @return list<array{key: string, label: string, count: int, url: string|null}>
     */
    private function attention(array $modules, array $stats): array
    {
        $items = [];

        foreach ($modules as $module) {
            if (! isset($module['attention_key'])) {
                continue;
            }

            $count = (int) ($stats[$module['key']]['attention'] ?? 0);

            if ($count === 0) {
                continue;
            }

            $items[] = [
                'key' => $module['attention_key'],
                'label' => __('dashboard.analytics.attention.'.$module['attention_key']),
                'count' => $count,
                'url' => $this->moduleUrl($module),
            ];
        }

        usort($items, fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        return $items;
    }

    /**
     * Latest records across the highest-volume permitted modules.
     *
     * @param  list<array<string, mixed>>  $modules
     * @return list<array{module: string, label: string, title: string, date: string|null, url: string|null}>
     */
    private function latestActivity(array $modules): array
    {
        $sources = [
            'complaints' => ['columns' => ['id', 'complainant_name', 'patient_name', 'created_at'], 'title' => fn (object $r): string => trim((string) ($r->complainant_name ?: $r->patient_name)) ?: '#'.$r->id, 'when' => fn (object $r): ?string => $r->created_at],
            'inquiries' => ['columns' => ['id', 'enquirer', 'date'], 'title' => fn (object $r): string => trim((string) $r->enquirer) ?: '#'.$r->id, 'when' => fn (object $r): ?string => is_numeric($r->date) ? date('Y-m-d H:i', (int) $r->date) : null],
            'correspondence' => ['columns' => ['id', 'sender', 'created_at'], 'title' => fn (object $r): string => trim((string) $r->sender) ?: '#'.$r->id, 'when' => fn (object $r): ?string => $r->created_at],
            'technical_failures' => ['columns' => ['id', 'notice', 'date_time'], 'title' => fn (object $r): string => mb_strimwidth(trim((string) $r->notice), 0, 60, '…') ?: '#'.$r->id, 'when' => fn (object $r): ?string => is_numeric($r->date_time) ? date('Y-m-d H:i', (int) $r->date_time) : null],
        ];

        $items = [];

        foreach ($modules as $module) {
            if (! isset($sources[$module['key']])) {
                continue;
            }

            $source = $sources[$module['key']];
            $rows = $this->scoped($module['table'])
                ->select($source['columns'])
                ->orderByDesc('id')
                ->limit(3)
                ->get();

            foreach ($rows as $row) {
                $items[] = [
                    'module' => $module['key'],
                    'label' => __('dashboard.analytics.modules.'.$module['key']),
                    'title' => ($source['title'])($row),
                    'date' => ($source['when'])($row),
                    'url' => $this->moduleUrl($module),
                ];
            }
        }

        usort($items, fn (array $a, array $b): int => strcmp((string) $b['date'], (string) $a['date']));

        return array_slice($items, 0, 8);
    }

    /**
     * @param  array<string, mixed>  $module
     */
    private function moduleUrl(array $module): ?string
    {
        $routeName = (string) ($module['route'] ?? '');

        return $routeName !== '' && Route::has($routeName) ? route($routeName) : null;
    }
}
