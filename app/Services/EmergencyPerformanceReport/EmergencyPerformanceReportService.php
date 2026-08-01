<?php

namespace App\Services\EmergencyPerformanceReport;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EmergencyPerformanceReportService
{
    private const BRANCH_ID = 1;

    /** @var array<string, array{table: string, file: ?string, title: string, columns: array<int, array{key: string, label: string}>}> */
    private const SECTIONS = [
        'non-approval' => [
            'table' => 'non_approval_ehala_1', 'file' => 'file', 'title' => 'أسباب عدم اعتماد الحالات',
            'columns' => [
                ['key' => 'status_number', 'label' => 'رقم الحالة'], ['key' => 'doctor', 'label' => 'الطبيب المستلم'],
                ['key' => 'specialization_label', 'label' => 'التخصص'], ['key' => 'status_reason_label', 'label' => 'سبب عدم الاعتماد'],
                ['key' => 'status_action', 'label' => 'الإجراء'],
            ],
        ],
        'pending-approvals' => [
            'table' => 'emergency_pending_approvals_1', 'file' => null, 'title' => 'الحالات المعلقة في برنامج إحالة',
            'columns' => [
                ['key' => 'filenumber', 'label' => 'رقم الملف'], ['key' => 'hospitalization_label', 'label' => 'موقع التنويم'],
                ['key' => 'approving_bodies', 'label' => 'جهة الموافقة'], ['key' => 'suspension_reasons_label', 'label' => 'سبب التعليق'],
                ['key' => 'action', 'label' => 'الإجراء'], ['key' => 'other', 'label' => 'سبب آخر'],
            ],
        ],
        'amounts-due' => [
            'table' => 'amounts_due_1', 'file' => null, 'title' => 'المبالغ المستحقة',
            'columns' => [
                ['key' => 'filenumber', 'label' => 'رقم الملف'], ['key' => 'room_num', 'label' => 'رقم الغرفة'],
                ['key' => 'payment_type_label', 'label' => 'نوع الدفع'], ['key' => 'amount_due', 'label' => 'المبلغ'],
                ['key' => 'payment_reason_label', 'label' => 'السبب'], ['key' => 'payment_action_label', 'label' => 'الإجراء'],
                ['key' => 'payment_reason_other', 'label' => 'سبب آخر'], ['key' => 'payment_action_other', 'label' => 'إجراء آخر'],
            ],
        ],
        'patients-signed' => [
            'table' => 'patients_signed_1', 'file' => null, 'title' => 'حالات عدم توقيع الالتزام',
            'columns' => [
                ['key' => 'filenumber', 'label' => 'رقم الملف'], ['key' => 'roomNum', 'label' => 'رقم الغرفة'],
                ['key' => 'hospitalization_label', 'label' => 'موقع التنويم'], ['key' => 'patients_signed_reason_label', 'label' => 'سبب عدم التوقيع'],
                ['key' => 'patients_signed_reason_other', 'label' => 'سبب آخر'],
            ],
        ],
        'screening' => [
            'table' => 'visual_screening_form_1', 'file' => null, 'title' => 'استمارات الفرز البصري التنفسي',
            'columns' => [
                ['key' => 'sending_time', 'label' => 'وقت الإرسال'], ['key' => 'sending_method_label', 'label' => 'طريقة الإرسال'],
            ],
        ],
        'death-pending' => [
            'table' => 'death_pending_1', 'file' => null, 'title' => 'تباليغ الوفاة المعلقة',
            'columns' => [
                ['key' => 'name', 'label' => 'اسم المتوفى'], ['key' => 'filenumber', 'label' => 'رقم الملف'],
                ['key' => 'death_datetime', 'label' => 'التاريخ والوقت'], ['key' => 'death_pending_reason_label', 'label' => 'السبب'],
                ['key' => 'death_pending_action_label', 'label' => 'الإجراء'], ['key' => 'death_pending_reason_other', 'label' => 'سبب آخر'],
                ['key' => 'death_pending_action_other', 'label' => 'إجراء آخر'],
            ],
        ],
        'long-stay' => [
            'table' => 'more_than_two_hour_1', 'file' => null, 'title' => 'حالات تجاوز البقاء ساعتين',
            'columns' => [
                ['key' => 'filenumber', 'label' => 'رقم الملف'], ['key' => 'long_stay_datetime', 'label' => 'التاريخ والوقت'],
                ['key' => 'more_than_two_hour_reason_label', 'label' => 'السبب'], ['key' => 'more_than_two_hour_action_label', 'label' => 'الإجراء'],
                ['key' => 'more_than_two_hour_reason_other', 'label' => 'سبب آخر'], ['key' => 'more_than_two_hour_action_other', 'label' => 'إجراء آخر'],
            ],
        ],
        'notes' => [
            'table' => 'report_1_patient', 'file' => null, 'title' => 'الملاحظات والصعوبات',
            'columns' => [
                ['key' => 'file_number', 'label' => 'رقم الملف'], ['key' => 'notice', 'label' => 'الملاحظة أو الصعوبة'],
                ['key' => 'description', 'label' => 'الوصف'], ['key' => 'notice_type', 'label' => 'النوع'],
                ['key' => 'action', 'label' => 'الإجراء'], ['key' => 'status', 'label' => 'الحالة'],
            ],
        ],
        'revenue-deficit' => [
            'table' => 'revenue_deficit_1', 'file' => null, 'title' => 'عجز الإيرادات',
            'columns' => [
                ['key' => 'emp_id', 'label' => 'الموظف'], ['key' => 'amount', 'label' => 'قيمة العجز المالي'],
                ['key' => 'reason', 'label' => 'السبب'], ['key' => 'action', 'label' => 'الإجراء'],
            ],
        ],
        'mortuary' => [
            'table' => 'dead_body', 'file' => null, 'title' => 'الجثامين',
            'columns' => [
                ['key' => 'filenumber', 'label' => 'رقم الملف'], ['key' => 'body_dead_in_refrigerator', 'label' => 'عدد أيام بقاء الجثة'],
                ['key' => 'body_dead_in_refrigerator_reason_label', 'label' => 'سبب البقاء'],
            ],
        ],
        'support-services' => [
            'table' => 'support_services_1', 'file' => 'files', 'title' => 'ملاحظات الخدمات المساندة',
            'columns' => [
                ['key' => 'maintenance_departments', 'label' => 'القسم'], ['key' => 'maintenance_type', 'label' => 'النوع'],
                ['key' => 'maintenance_request_type', 'label' => 'المطلوب'], ['key' => 'description', 'label' => 'الوصف'],
            ],
        ],
    ];

    public function filters(array $input): array
    {
        return [
            'from' => (string) ($input['from'] ?? date('Y-01-01')),
            'to' => (string) ($input['to'] ?? date('Y-m-d')),
            'employee' => (string) ($input['employee'] ?? 'all'),
            'period' => (string) ($input['period'] ?? 'all'),
            'submitted' => (bool) ($input['submitted'] ?? false),
        ];
    }

    public function options(): array
    {
        $companyId = (int) session('companies_groups_id', 0);

        return [
            'employees' => DB::table('ra_users')
                ->where('companies_groups_id', $companyId)->where('branch_id', self::BRANCH_ID)
                ->where('hr_user_level', 1)->where('activated', 1)
                ->orderBy('hr_first_name')->get(['hr_id', 'hr_username', 'hr_first_name', 'hr_last_name']),
            'periods' => DB::table('duty_period')->where('publish', 1)->orderBy('id')->get(['id', 'name_ar', 'name_en']),
        ];
    }

    /** @return array<string, mixed> */
    public function report(array $filters): array
    {
        if (! $filters['submitted']) {
            return ['reports' => collect(), 'attendance' => $this->emptyAttendance(), 'sections' => collect(), 'reportIds' => []];
        }

        $reports = $this->reportsQuery($filters)->get();
        $reportIds = $reports->pluck('id')->map(fn ($id) => (int) $id)->all();
        $attendance = $this->attendance($filters, $reportIds);
        $sections = collect();

        foreach (self::SECTIONS as $key => $definition) {
            $rows = $this->childRows($key, $definition, $reportIds, $reports);
            if ($rows->isNotEmpty()) {
                $sections->put($key, ['title' => $definition['title'], 'columns' => $definition['columns'], 'file' => $definition['file'], 'rows' => $rows]);
            }
        }

        return compact('reports', 'attendance', 'sections', 'reportIds');
    }

    public function attachment(string $section, int $entry): ?object
    {
        $definition = self::SECTIONS[$section] ?? null;
        if ($definition === null || $definition['file'] === null) {
            return null;
        }

        $row = DB::table($definition['table'].' as child')
            ->join('report_1 as report', 'report.id', '=', 'child.report_id')
            ->where('child.id', $entry)->where('child.branch_id', self::BRANCH_ID)
            ->where('report.branch_id', self::BRANCH_ID)
            ->where('report.companies_groups_id', (int) session('companies_groups_id', 0))
            ->first(['child.id', 'child.report_id', 'child.'.$definition['file'].' as file']);

        if ($row === null || trim((string) $row->file) === '') {
            return null;
        }

        return $row;
    }

    private function reportsQuery(array $filters): Builder
    {
        $query = DB::table('report_1 as report')
            ->leftJoin('ra_users as creator', 'creator.hr_id', '=', 'report.creator')
            ->leftJoin('duty_period as period', 'period.id', '=', 'report.period')
            ->where('report.branch_id', self::BRANCH_ID)
            ->where('report.companies_groups_id', (int) session('companies_groups_id', 0))
            ->whereBetween('report.date', [$this->timestamp($filters['from']), $this->timestamp($filters['to'], true)])
            ->select([
                'report.*', 'period.name_ar as period_name_ar', 'period.name_en as period_name_en',
                'creator.hr_username as creator_username', 'creator.hr_first_name', 'creator.hr_last_name',
            ])->orderBy('report.date');

        if ($filters['employee'] !== 'all') {
            $query->where('report.creator', (int) $filters['employee']);
        }
        if ($filters['period'] !== 'all') {
            $query->where('report.period', (int) $filters['period']);
        }

        return $query;
    }

    private function attendance(array $filters, array $reportIds): array
    {
        if ($reportIds === []) {
            return $this->emptyAttendance();
        }

        $row = DB::table('employees_attendance')
            ->whereIn('report_id', $reportIds)->where('branch_id', self::BRANCH_ID)
            ->where('companies_groups_id', (int) session('companies_groups_id', 0))
            ->selectRaw('COALESCE(SUM(attendees), 0) attendees, COALESCE(SUM(absence), 0) absence, COALESCE(SUM(latecomers), 0) latecomers, COALESCE(SUM(permissible), 0) permissible')
            ->first();

        return [
            'attendees' => (int) ($row->attendees ?? 0), 'absence' => (int) ($row->absence ?? 0),
            'latecomers' => (int) ($row->latecomers ?? 0), 'permissible' => (int) ($row->permissible ?? 0),
        ];
    }

    /** @param array{table: string, file: ?string, title: string, columns: array<int, array{key: string, label: string}>} $definition */
    private function childRows(string $key, array $definition, array $reportIds, Collection $reports): Collection
    {
        if ($reportIds === []) {
            return collect();
        }

        $rows = DB::table($definition['table'])->whereIn('report_id', $reportIds)->where('branch_id', self::BRANCH_ID)->orderBy('date')->get();
        $meta = $reports->keyBy('id');
        $lookups = $this->lookupMaps();

        return $rows->map(function (object $row) use ($key, $meta, $lookups): object {
            $parent = $meta->get((int) $row->report_id);
            $row->report_date = $this->date((int) ($row->date ?? 0));
            $row->period_label = $this->localized($parent?->period_name_ar, $parent?->period_name_en);
            $row->creator_label = trim((string) ($parent?->creator_username.' '.$parent?->hr_first_name.' '.$parent?->hr_last_name));
            $row->specialization_label = $lookups['branches_departments'][(int) ($row->specialization ?? 0)] ?? ($row->specialization ?? '');
            $row->status_reason_label = $lookups['rep1_ehala_non_approval_reason'][(int) ($row->status_reason ?? 0)] ?? ($row->status_reason ?? '');
            $row->hospitalization_label = $lookups['rep1_hospitalization_place'][(int) ($row->hospitalization ?? 0)] ?? ($row->hospitalization ?? '');
            $row->suspension_reasons_label = $lookups['rep1_suspension_reasons'][(int) ($row->suspension_reasons ?? 0)] ?? ($row->suspension_reasons ?? '');
            $row->payment_type_label = $lookups['rep1_payment_type'][(int) ($row->payment_type ?? 0)] ?? ($row->payment_type ?? '');
            $row->payment_reason_label = $lookups['rep1_payment_due_reason'][(int) ($row->payment_reason ?? 0)] ?? ($row->payment_reason ?? '');
            $row->payment_action_label = $lookups['rep1_payment_due_action'][(int) ($row->payment_action ?? 0)] ?? ($row->payment_action ?? '');
            $row->patients_signed_reason_label = $lookups['rep1_not_signed_commitment_reason'][(int) ($row->patients_signed_reason ?? 0)] ?? ($row->patients_signed_reason ?? '');
            $row->death_pending_reason_label = $lookups['rep1_death_pending_reason'][(int) ($row->death_pending_reason ?? 0)] ?? ($row->death_pending_reason ?? '');
            $row->death_pending_action_label = $lookups['rep1_death_pending_action'][(int) ($row->death_pending_action ?? 0)] ?? ($row->death_pending_action ?? '');
            $row->more_than_two_hour_reason_label = $lookups['rep1_more_than_two_hours_reasons'][(int) ($row->more_than_two_hour_reason ?? 0)] ?? ($row->more_than_two_hour_reason ?? '');
            $row->more_than_two_hour_action_label = $lookups['rep1_more_than_two_hours_action'][(int) ($row->more_than_two_hour_action ?? 0)] ?? ($row->more_than_two_hour_action ?? '');
            $row->body_dead_in_refrigerator_reason_label = $lookups['rep1_keeping_body_in_mortuary'][(int) ($row->body_dead_in_refrigerator_reason ?? 0)] ?? ($row->body_dead_in_refrigerator_reason ?? '');
            $row->sending_time = sprintf('%02d:%02d', (int) ($row->sending_time_hour ?? 0), (int) ($row->sending_time_minute ?? 0));
            $row->sending_method_label = [1 => 'منصة صحة', 2 => 'البريد الإلكتروني'][(int) ($row->sending_method ?? 0)] ?? ($row->sending_method ?? '');
            $row->death_datetime = $this->dateTime((int) ($row->datetime ?? 0));
            $row->long_stay_datetime = $this->dateTime((int) ($row->date_time ?? 0));
            $row->emp_id = $lookups['users'][(int) ($row->emp_id ?? 0)] ?? ($row->emp_id ?? '');
            $row->attachment_section = $key;

            return $row;
        });
    }

    /** @return array<string, array<int, string>> */
    private function lookupMaps(): array
    {
        $maps = ['users' => [], 'branches_departments' => []];
        foreach (['branches_departments' => ['branches_departments', 'id'], 'rep1_ehala_non_approval_reason' => ['rep1_ehala_non_approval_reason', 'id'], 'rep1_hospitalization_place' => ['rep1_hospitalization_place', 'id'], 'rep1_suspension_reasons' => ['rep1_suspension_reasons', 'id'], 'rep1_payment_type' => ['rep1_payment_type', 'id'], 'rep1_payment_due_reason' => ['rep1_payment_due_reason', 'id'], 'rep1_payment_due_action' => ['rep1_payment_due_action', 'id'], 'rep1_not_signed_commitment_reason' => ['rep1_not_signed_commitment_reason', 'id'], 'rep1_death_pending_reason' => ['rep1_death_pending_reason', 'id'], 'rep1_death_pending_action' => ['rep1_death_pending_action', 'id'], 'rep1_more_than_two_hours_reasons' => ['rep1_more_than_two_hours_reasons', 'id'], 'rep1_more_than_two_hours_action' => ['rep1_more_than_two_hours_action', 'id'], 'rep1_keeping_body_in_mortuary' => ['rep1_keeping_body_in_mortuary', 'id']] as $name => [$table, $key]) {
            $rows = DB::table($table)->get([$key, 'name_ar', 'name_en']);
            $maps[$name] = $rows->mapWithKeys(fn ($row) => [(int) $row->{$key} => $this->localized($row->name_ar, $row->name_en)])->all();
        }
        $maps['users'] = DB::table('ra_users')->get(['hr_id', 'hr_username', 'hr_first_name', 'hr_last_name'])->mapWithKeys(fn ($row) => [(int) $row->hr_id => trim($row->hr_username.' '.$row->hr_first_name.' '.$row->hr_last_name)])->all();

        return $maps;
    }

    private function localized(?string $ar, ?string $en): string
    {
        return app()->getLocale() === 'ar' ? trim((string) ($ar ?: $en)) : trim((string) ($en ?: $ar));
    }

    private function timestamp(string $date, bool $end = false): int
    {
        return strtotime($date.($end ? ' 23:59:59' : ' 00:00:00')) ?: 0;
    }

    private function date(int $timestamp): string
    {
        return $timestamp > 0 ? date('Y-m-d', $timestamp) : '';
    }

    private function dateTime(int $timestamp): string
    {
        return $timestamp > 0 ? date('Y-m-d H:i', $timestamp) : '';
    }

    /** @return array{attendees: int, absence: int, latecomers: int, permissible: int} */
    private function emptyAttendance(): array
    {
        return ['attendees' => 0, 'absence' => 0, 'latecomers' => 0, 'permissible' => 0];
    }
}
