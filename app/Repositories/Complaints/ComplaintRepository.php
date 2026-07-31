<?php

namespace App\Repositories\Complaints;

use App\Models\Complaint;
use App\Models\BranchDepartment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ComplaintRepository
{
    /**
     * @var list<string>
     */
    private const LIST_COLUMNS = [
        'id',
        'complaints_numbers_id',
        'file_number',
        'complainant_name',
        'complainant_name_ar',
        'complainant_name_en',
        'branches_departments_id',
        'date',
        'created_at',
        'status',
        'priority',
        'updated_at',
        'companies_groups_id',
        'publish',
        'type',
    ];

    /**
     * @var list<string>
     */
    private const DETAIL_COLUMNS = [
        'id',
        'complaints_numbers_id',
        'date',
        'created_at',
        'updated_at',
        'created_action_at',
        'complainant_name',
        'complainant_name_ar',
        'complainant_name_en',
        'patient_name',
        'patient_name_ar',
        'patient_name_en',
        'file_number',
        'branches_departments_id',
        'defendant',
        'details',
        'result',
        'employee_investigation',
        'mobile',
        'type',
        'status',
        'companies_groups_id',
        'publish',
    ];

    public function scopedQuery(): Builder
    {
        return Complaint::query()
            ->select(self::LIST_COLUMNS)
            ->where('companies_groups_id', (int) session('companies_groups_id', 0))
            ->where('publish', 1)
            ->where('status', '!=', 9)
            ->where(function (Builder $query) {
                $query->whereNull('deleted_at')
                    ->orWhere('deleted_at', '');
            });
    }

    /**
     * @return array{total: int, active: int, processed: int, closed: int}
     */
    public function dashboardCounts(): array
    {
        $stats = config('hm.complaints.dashboard_stats', []);
        $activeStatuses = $stats['active'] ?? [0, 1, 2, 3, 4];
        $processedStatus = (int) ($stats['processed'] ?? 5);
        $closedStatus = (int) ($stats['closed'] ?? 6);

        $base = $this->scopedQuery();

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->whereIn('status', $activeStatuses)->count(),
            'processed' => (clone $base)->where('status', $processedStatus)->count(),
            'closed' => (clone $base)->where('status', $closedStatus)->count(),
        ];
    }

    /**
     * @return array{
     *     processing_rate: int,
     *     most_active_department: string,
     *     latest_update: string|null,
     *     latest_update_label: string
     * }
     */
    public function dashboardInsights(): array
    {
        $stats = config('hm.complaints.dashboard_stats', []);
        $processedStatus = (int) ($stats['processed'] ?? 5);
        $closedStatus = (int) ($stats['closed'] ?? 6);
        $base = $this->scopedQuery();

        $total = (clone $base)->count();
        $resolved = $total > 0
            ? (clone $base)->whereIn('status', [$processedStatus, $closedStatus])->count()
            : 0;
        $processingRate = $total > 0 ? (int) round(($resolved / $total) * 100) : 0;

        $topDepartmentRow = (clone $base)
            ->select('branches_departments_id', DB::raw('COUNT(*) as complaint_count'))
            ->where('branches_departments_id', '>', 0)
            ->groupBy('branches_departments_id')
            ->orderByDesc('complaint_count')
            ->first();

        $mostActiveDepartment = '—';

        if ($topDepartmentRow !== null) {
            $department = BranchDepartment::query()
                ->select(['id', 'name_en', 'name_ar'])
                ->find((int) $topDepartmentRow->branches_departments_id);

            if ($department !== null) {
                $mostActiveDepartment = app()->getLocale() === 'ar'
                    ? trim((string) ($department->name_ar ?: $department->name_en))
                    : trim((string) ($department->name_en ?: $department->name_ar));
                $mostActiveDepartment = $mostActiveDepartment !== '' ? $mostActiveDepartment : '—';
            }
        }

        $latestUpdate = (clone $base)->max('updated_at');
        $latestUpdateLabel = '—';

        $timestamp = $this->parseLegacyTimestamp($latestUpdate);

        if ($timestamp !== null) {
            $latestUpdateLabel = $timestamp->isToday()
                ? __('complaints.insights.today_at', ['time' => $timestamp->format('H:i')])
                : $timestamp->format('Y-m-d H:i');
        }

        return [
            'processing_rate' => $processingRate,
            'most_active_department' => $mostActiveDepartment,
            'latest_update' => $latestUpdate ? (string) $latestUpdate : null,
            'latest_update_label' => $latestUpdateLabel,
        ];
    }

    private function parseLegacyTimestamp(mixed $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        if (ctype_digit($raw)) {
            $seconds = (int) $raw;

            return $seconds > 0 ? Carbon::createFromTimestamp($seconds) : null;
        }

        try {
            return Carbon::parse($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return LengthAwarePaginator<int, Complaint>
     */
    public function paginateFiltered(string $search, ?int $status, int $perPage): LengthAwarePaginator
    {
        return $this->scopedQuery()
            ->with([
                'department:id,name_en,name_ar',
                'currentStatus:id,name_en,name_ar,info',
            ])
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $inner) use ($search) {
                    $inner->where('file_number', 'like', '%'.$search.'%')
                        ->orWhere('complaints_numbers_id', 'like', '%'.$search.'%')
                        ->orWhere('id_no', 'like', '%'.$search.'%');
                });
            })
            ->when($status !== null, fn (Builder $query) => $query->where('status', $status))
            ->orderByDesc('complaints_numbers_id')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findForDetail(int $id): ?Complaint
    {
        return Complaint::query()
            ->select(self::DETAIL_COLUMNS)
            ->where('companies_groups_id', (int) session('companies_groups_id', 0))
            ->where('publish', 1)
            ->where('status', '!=', 9)
            ->where(function (Builder $query) {
                $query->whereNull('deleted_at')
                    ->orWhere('deleted_at', '');
            })
            ->whereKey($id)
            ->with([
                'department:id,name_en,name_ar',
                'currentStatus:id,name_en,name_ar,info',
            ])
            ->first();
    }
}
