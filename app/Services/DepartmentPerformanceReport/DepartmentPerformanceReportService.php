<?php

namespace App\Services\DepartmentPerformanceReport;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DepartmentPerformanceReportService
{
    /**
     * These are the two department reports exposed by the old admin menu.
     * The numeric branch ids are the legacy department ids, not the logged-in
     * user's hospital branch id.
     *
     * @var array<string, array{branch_id:int, table:string, title_key:string, summary:array<int, array{key:string,label:string,icon:string}>, sections:array<string, array{table:string,file:?string,title:string,columns:array<int,array{key:string,label:string}>}>}>
     */
    private const DEFINITIONS = [
        'collection' => [
            'branch_id' => 2,
            'table' => 'report_2',
            'title_key' => 'collection',
            'summary' => [
                ['key' => 'patients_have_remaining_money_inpatient', 'label' => 'patients_remaining_count', 'icon' => 'bi-person-exclamation'],
                ['key' => 'employees_revenue_shortfall_outpatient', 'label' => 'outpatient_staff_deficit_count', 'icon' => 'bi-person-down'],
                ['key' => 'employees_revenue_shortfall_emergency', 'label' => 'emergency_staff_deficit_count', 'icon' => 'bi-person-down'],
                ['key' => 'employees_revenue_shortfall_hospitalization_office', 'label' => 'inpatient_office_staff_deficit_count', 'icon' => 'bi-person-down'],
                ['key' => 'directors_revenue_deficit', 'label' => 'directors_deficit_count', 'icon' => 'bi-people'],
                ['key' => 'total_remaining_for_patients_hospitalization_department', 'label' => 'inpatient_remaining_total', 'icon' => 'bi-cash-stack'],
                ['key' => 'total_revenue_deficit_outpatient_department', 'label' => 'outpatient_revenue_deficit_total', 'icon' => 'bi-graph-down-arrow'],
                ['key' => 'total_revenue_shortfall_emergency_department', 'label' => 'emergency_revenue_deficit_total', 'icon' => 'bi-graph-down-arrow'],
                ['key' => 'total_revenue_deficit_hypnotherapy_office', 'label' => 'inpatient_office_revenue_deficit_total', 'icon' => 'bi-graph-down-arrow'],
                ['key' => 'total_revenue_shortfall_emergency_managers', 'label' => 'emergency_managers_deficit_total', 'icon' => 'bi-graph-down-arrow'],
            ],
            'sections' => [
                'revenue-deficit' => [
                    'table' => 'report_2_revenue_deficit', 'file' => null, 'title' => 'revenue_deficit',
                    'columns' => [
                        ['key' => 'emp_id_label', 'label' => 'employee'], ['key' => 'department_label', 'label' => 'department'],
                        ['key' => 'amount', 'label' => 'financial_deficit'], ['key' => 'action_label', 'label' => 'action'],
                        ['key' => 'other', 'label' => 'notes'],
                    ],
                ],
                'owed-amount' => [
                    'table' => 'report_2_owed_amount', 'file' => null, 'title' => 'owed_amount',
                    'columns' => [
                        ['key' => 'filenumber6', 'label' => 'file_number'], ['key' => 'room', 'label' => 'room'],
                        ['key' => 'amount_due', 'label' => 'amount'], ['key' => 'reason_label', 'label' => 'reason'],
                        ['key' => 'other_reason', 'label' => 'other_reason'], ['key' => 'deficit_action_label', 'label' => 'action'],
                        ['key' => 'deficit_action_reason', 'label' => 'action_notes'],
                    ],
                ],
                'no-pledge' => [
                    'table' => 'report_2_no_pledge_pay', 'file' => null, 'title' => 'no_pledge',
                    'columns' => [
                        ['key' => 'filenumber3', 'label' => 'file_number'], ['key' => 'room_no', 'label' => 'room'],
                        ['key' => 'admission_datetime', 'label' => 'admission_date'], ['key' => 'hospitalization_label', 'label' => 'hospitalization_location'],
                        ['key' => 'not_signing_reasons_label', 'label' => 'reason'],
                    ],
                ],
                'bond-signed' => [
                    'table' => 'report_2_patients_bond_signed', 'file' => 'file', 'title' => 'bond_signed',
                    'columns' => [
                        ['key' => 'p_name', 'label' => 'patient_name'], ['key' => 'filenumber7', 'label' => 'file_number'],
                        ['key' => 'idno', 'label' => 'patient_id'], ['key' => 'payer_name', 'label' => 'payer_name'],
                        ['key' => 'payer_name_idno', 'label' => 'payer_id'], ['key' => 'bond_amount', 'label' => 'bond_amount'],
                    ],
                ],
                'previous-paid' => [
                    'table' => 'report_2_previous_paid', 'file' => 'files', 'title' => 'previous_paid',
                    'columns' => [
                        ['key' => 'patient_name', 'label' => 'patient_name'], ['key' => 'filenumber5', 'label' => 'file_number'],
                        ['key' => 'patient_idno', 'label' => 'patient_id'], ['key' => 'total_amount', 'label' => 'invoice_total'],
                        ['key' => 'paid_amount', 'label' => 'paid_amount'], ['key' => 'rest_amount', 'label' => 'remaining_amount'],
                        ['key' => 'invoce_no', 'label' => 'invoice_number'],
                    ],
                ],
            ],
        ],
        'legal' => [
            'branch_id' => 3,
            'table' => 'report_3',
            'title_key' => 'legal',
            'summary' => [
                ['key' => 'cases_filed_in_court', 'label' => 'cases_filed_in_court', 'icon' => 'bi-bank'],
                ['key' => 'total_requests_Najiz', 'label' => 'najiz_requests', 'icon' => 'bi-file-earmark-check'],
                ['key' => 'pending_cases', 'label' => 'pending_cases', 'icon' => 'bi-hourglass-split'],
            ],
            'sections' => [
                'general-court' => [
                    'table' => 'report_3_report_cases_filed_general_court', 'file' => 'file', 'title' => 'general_court',
                    'columns' => [
                        ['key' => 'p_name', 'label' => 'patient_name'], ['key' => 'filenumber7', 'label' => 'file_number'],
                        ['key' => 'hospital_admission_label', 'label' => 'hospitalization_location'], ['key' => 'payer_name', 'label' => 'payer_name'],
                        ['key' => 'payer_name_idno', 'label' => 'payer_id'], ['key' => 'payer_name_type_label', 'label' => 'defendant_type'],
                        ['key' => 'judicial_department', 'label' => 'judicial_department'], ['key' => 'request_type_label', 'label' => 'request_type'],
                        ['key' => 'order_number', 'label' => 'request_number'], ['key' => 'total_amount', 'label' => 'invoice_total'],
                        ['key' => 'amount_paid', 'label' => 'paid_amount'], ['key' => 'remaining_amount', 'label' => 'remaining_amount'],
                    ],
                ],
                'pending-claims' => [
                    'table' => 'report_3_pending_claims_report', 'file' => 'file', 'title' => 'pending_claims',
                    'columns' => [
                        ['key' => 'case_number', 'label' => 'case_number'], ['key' => 'session_date_display', 'label' => 'session_date'],
                        ['key' => 'filenumber5', 'label' => 'file_number'], ['key' => 'respondent', 'label' => 'respondent'],
                        ['key' => 'respondent_idno', 'label' => 'respondent_id'], ['key' => 'orders', 'label' => 'orders'],
                    ],
                ],
                'government' => [
                    'table' => 'report_3_case_filed_government', 'file' => 'file', 'title' => 'government_cases',
                    'columns' => [
                        ['key' => 'direction_label', 'label' => 'objection_authority'], ['key' => 'orderNumber', 'label' => 'request_number'],
                        ['key' => 'order_type_label', 'label' => 'request_type'],
                    ],
                ],
                'bond-signed' => [
                    'table' => 'report_2_patients_bond_signed', 'file' => 'file', 'title' => 'bond_signed',
                    'columns' => [
                        ['key' => 'p_name', 'label' => 'patient_name'], ['key' => 'filenumber7', 'label' => 'file_number'],
                        ['key' => 'idno', 'label' => 'patient_id'], ['key' => 'payer_name', 'label' => 'payer_name'],
                        ['key' => 'payer_name_idno', 'label' => 'payer_id'], ['key' => 'bond_amount', 'label' => 'bond_amount'],
                    ],
                ],
                'previous-paid' => [
                    'table' => 'report_2_previous_paid', 'file' => 'files', 'title' => 'previous_paid',
                    'columns' => [
                        ['key' => 'patient_name', 'label' => 'patient_name'], ['key' => 'filenumber5', 'label' => 'file_number'],
                        ['key' => 'patient_idno', 'label' => 'patient_id'], ['key' => 'total_amount', 'label' => 'invoice_total'],
                        ['key' => 'paid_amount', 'label' => 'paid_amount'], ['key' => 'rest_amount', 'label' => 'remaining_amount'],
                        ['key' => 'invoce_no', 'label' => 'invoice_number'],
                    ],
                ],
            ],
        ],
    ];

    /** @return array<string, mixed> */
    public function definition(string $department): array
    {
        abort_unless(isset(self::DEFINITIONS[$department]), 404);

        return self::DEFINITIONS[$department];
    }

    /** @return array{from:string,to:string,employee:string,period:string,submitted:bool} */
    public function filters(array $input): array
    {
        $from = (string) ($input['from'] ?? '');
        $to = (string) ($input['to'] ?? '');

        return [
            'from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : date('Y-01-01'),
            'to' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) ? $to : date('Y-m-d'),
            'employee' => (string) ($input['employee'] ?? 'all'),
            'period' => (string) ($input['period'] ?? 'all'),
            'submitted' => (bool) ($input['submitted'] ?? false),
        ];
    }

    /** @return array{employees:Collection,periods:Collection} */
    public function options(string $department): array
    {
        $definition = $this->definition($department);
        $companyId = (int) session('companies_groups_id', 0);

        return [
            'employees' => $this->tableExists('ra_users')
                ? DB::table('ra_users')->where('companies_groups_id', $companyId)->where('branch_id', $definition['branch_id'])
                    ->where('hr_user_level', 1)->orderBy('hr_first_name')->get(['hr_id', 'hr_username', 'hr_first_name', 'hr_last_name'])
                : collect(),
            'periods' => $this->tableExists('duty_period')
                ? DB::table('duty_period')->where('publish', 1)->orderBy('id')->get(['id', 'name_ar', 'name_en'])
                : collect(),
        ];
    }

    /** @return array<string, mixed> */
    public function report(string $department, array $filters): array
    {
        $definition = $this->definition($department);
        $empty = ['reports' => collect(), 'reportIds' => [], 'summary' => [], 'sections' => collect(), 'totalRows' => 0];

        if (! $filters['submitted'] || ! $this->tableExists($definition['table'])) {
            return $empty;
        }

        $reports = $this->reportsQuery($definition, $filters)->get();
        $reportIds = $reports->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $summary = collect($definition['summary'])->map(function (array $item) use ($reports): array {
            return $item + ['total' => $reports->sum(fn (object $report): float => (float) ($report->{$item['key']} ?? 0))];
        })->values()->all();

        $sections = collect();
        $totalRows = 0;
        foreach ($definition['sections'] as $key => $section) {
            if (! $this->tableExists($section['table'])) {
                continue;
            }

            $rows = $this->childRows($department, $definition, $key, $section, $reportIds, $reports);
            if ($rows->isEmpty()) {
                continue;
            }

            $totalRows += $rows->count();
            $sections->put($key, [
                'title' => $section['title'], 'columns' => $section['columns'], 'file' => $section['file'], 'rows' => $rows,
            ]);
        }

        return compact('reports', 'reportIds', 'summary', 'sections', 'totalRows');
    }

    public function attachment(string $department, string $sectionKey, int $entry): ?object
    {
        $definition = $this->definition($department);
        $section = $definition['sections'][$sectionKey] ?? null;
        if ($section === null || $section['file'] === null || ! $this->tableExists($section['table'])) {
            return null;
        }

        $row = DB::table($section['table'].' as child')
            ->join($definition['table'].' as report', 'report.id', '=', 'child.report_id')
            ->where('child.id', $entry)->where('child.branch_id', $definition['branch_id'])
            ->where('report.branch_id', $definition['branch_id'])
            ->where('report.companies_groups_id', (int) session('companies_groups_id', 0))
            ->first(['child.id', 'child.'.$section['file'].' as file']);

        if ($row === null || trim((string) $row->file) === '') {
            return null;
        }

        return $row;
    }

    private function reportsQuery(array $definition, array $filters): Builder
    {
        $query = DB::table($definition['table'].' as report')
            ->leftJoin('ra_users as creator', 'creator.hr_id', '=', 'report.creator')
            ->leftJoin('duty_period as period', 'period.id', '=', 'report.period')
            ->where('report.branch_id', $definition['branch_id'])
            ->where('report.companies_groups_id', (int) session('companies_groups_id', 0))
            ->whereBetween('report.date', [$this->timestamp($filters['from']), $this->timestamp($filters['to'], true)])
            ->select([
                'report.*', 'period.name_ar as period_name_ar', 'period.name_en as period_name_en',
                'creator.hr_username as creator_username', 'creator.hr_first_name', 'creator.hr_last_name',
            ])->orderBy('report.date');

        if ($filters['employee'] !== 'all' && ctype_digit($filters['employee'])) {
            $query->where('report.creator', (int) $filters['employee']);
        }
        if ($filters['period'] !== 'all' && ctype_digit($filters['period'])) {
            $query->where('report.period', (int) $filters['period']);
        }

        return $query;
    }

    /** @param array{table:string,file:?string,title:string,columns:array<int,array{key:string,label:string}>} $section */
    private function childRows(string $department, array $definition, string $sectionKey, array $section, array $reportIds, Collection $reports): Collection
    {
        if ($reportIds === []) {
            return collect();
        }

        $rows = DB::table($section['table'])->whereIn('report_id', $reportIds)
            ->where('branch_id', $definition['branch_id'])->orderBy('date')->get();
        $meta = $reports->keyBy('id');
        $lookups = $this->lookupMaps();

        return $rows->map(function (object $row) use ($department, $definition, $sectionKey, $section, $meta, $lookups): object {
            $parent = $meta->get((int) ($row->report_id ?? 0));
            $row->report_date = $this->date($row->date ?? null);
            $row->period_label = $this->localized($parent?->period_name_ar, $parent?->period_name_en);
            $row->creator_label = trim((string) ($parent?->creator_username.' '.$parent?->hr_first_name.' '.$parent?->hr_last_name));
            $row->attachment_section = $sectionKey;

            if ($department === 'collection') {
                $row->emp_id_label = $lookups['users'][(int) ($row->emp_id ?? 0)] ?? ($row->emp_id ?? '');
                $row->department_label = $lookups['departments'][(int) ($row->branchid ?? 0)] ?? ($row->branchid ?? '');
                $row->action_label = $lookups['action_taken'][(int) ($row->action ?? 0)] ?? ($row->action ?? '');
                $row->reason_label = $lookups['rep2_reasons'][(int) ($row->reason ?? 0)] ?? ($row->reason ?? '');
                $row->deficit_action_label = $lookups['rep2_actions'][(int) ($row->deficit_action ?? 0)] ?? ($row->deficit_action ?? '');
                $row->admission_datetime = $this->dateTime($row->date_time ?? null);
                $row->hospitalization_label = $lookups['branches'][(int) ($row->hospital_admission ?? 0)] ?? ($row->hospital_admission ?? '');
                $row->not_signing_reasons_label = $lookups['rep2_pledge_reasons'][(int) ($row->not_signing_reasons ?? 0)] ?? ($row->not_signing_reasons ?? '');
            } else {
                $row->hospital_admission_label = $lookups['companies'][(int) ($row->{'hospitalـadmission'} ?? 0)] ?? ($row->{'hospitalـadmission'} ?? '');
                $row->payer_name_type_label = $lookups['rep3_payer_types'][(int) ($row->payer_name_type ?? 0)] ?? ($row->payer_name_type ?? '');
                $row->request_type_label = $lookups['rep3_request_types'][(int) ($row->request_type ?? 0)] ?? ($row->request_type ?? '');
                $row->direction_label = $lookups['rep3_directions'][(int) ($row->direction ?? 0)] ?? ($row->direction ?? '');
                $row->order_type_label = $lookups['rep3_order_types'][(int) ($row->order_type ?? 0)] ?? ($row->order_type ?? '');
                $row->session_date_display = $this->date($row->session_date ?? null);
            }

            return $row;
        });
    }

    /** @return array<string, array<int, string>> */
    private function lookupMaps(): array
    {
        $definitions = [
            'users' => ['ra_users', 'hr_id'], 'departments' => ['branches_departments', 'id'],
            'action_taken' => ['action_taken', 'id'], 'rep2_reasons' => ['rep2_amont_due_reasons', 'id'],
            'rep2_actions' => ['rep2_amount_due_action', 'id'], 'rep2_pledge_reasons' => ['rep2_not_signing_pledge_to_pay_reasons', 'id'],
            'branches' => ['branches', 'id'], 'companies' => ['companies_groups', 'id'],
            'rep3_payer_types' => ['rep3_payer_name_type', 'id'], 'rep3_request_types' => ['rep3_request_type', 'id'],
            'rep3_directions' => ['rep3_objection_court_direction', 'id'], 'rep3_order_types' => ['rep3_orders_type', 'id'],
        ];
        $maps = [];

        foreach ($definitions as $name => [$table, $key]) {
            if (! $this->tableExists($table)) {
                $maps[$name] = [];
                continue;
            }

            $query = DB::table($table);
            if (Schema::hasColumn($table, 'publish')) {
                $query->where('publish', 1);
            }
            $maps[$name] = $query->get()->mapWithKeys(fn (object $row): array => [
                (int) $row->{$key} => $this->localized($row->name_ar ?? null, $row->name_en ?? null),
            ])->all();
        }

        return $maps;
    }

    private function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    private function localized(?string $ar, ?string $en): string
    {
        return app()->getLocale() === 'ar' ? trim((string) ($ar ?: $en)) : trim((string) ($en ?: $ar));
    }

    private function timestamp(string $date, bool $end = false): int
    {
        return strtotime($date.($end ? ' 23:59:59' : ' 00:00:00')) ?: 0;
    }

    private function date(mixed $value): string
    {
        $timestamp = is_numeric($value) ? (int) $value : strtotime((string) $value);

        return $timestamp ? date('Y-m-d', $timestamp) : '';
    }

    private function dateTime(mixed $value): string
    {
        $timestamp = is_numeric($value) ? (int) $value : strtotime((string) $value);

        return $timestamp ? date('Y-m-d H:i', $timestamp) : '';
    }
}
