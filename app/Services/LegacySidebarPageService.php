<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use App\Services\Sms\SmsGateway;
use Illuminate\Support\Str;

/**
 * The old branch menu contains a number of small reference/report screens
 * which used to be implemented as standalone PHP files.  This service keeps
 * their original table names and tenancy columns while giving them a common
 * Laravel workflow and a common Hope/Figma presentation.
 */
class LegacySidebarPageService
{
    private const SPECS = [
        'adm_country' => ['label' => 'الدول', 'table' => 'country', 'mode' => 'reference', 'scope' => 'global', 'fields' => ['name_en', 'name_ar', 'name_ch', 'icon', 'c_key']],
        'administrative_cases' => ['label' => 'القضايا الإدارية', 'table' => 'administrative_cases', 'mode' => 'case', 'scope' => 'branch', 'display' => ['decision_number', 'entity', 'defendant_name', 'claim_amount', 'case_number', 'status'], 'fields' => ['claimant_name', 'claimant_id', 'claimant_cr_number', 'case_date', 'defendant_name', 'defendant_id', 'defendant_cr_number', 'claim_type', 'claim_type_other', 'claim_amount', 'case_summary', 'liable_mobile', 'nationality', 'id_number', 'decision_number', 'decision_date', 'covered_amount', 'uncovered_amount', 'received_date', 'objecting_party', 'subject', 'requests', 'attachments_name', 'request_number', 'case_number', 'notice_date']],
        'archives' => ['label' => 'الأرشيف القانوني', 'table' => 'archives', 'mode' => 'case', 'scope' => 'branch', 'fields' => ['name', 'no_id', 'no_lawsuit', 'status', 'becuse', 'Paymentـstatus', 'lawsuit_date', 'note']],
        'birth_notification' => ['label' => 'تبليغات الولادة', 'table' => 'birth_notification', 'mode' => 'clinical', 'scope' => 'company', 'fields' => ['father_full_name', 'mother_full_name', 'newborn_name', 'newborn_file_number', 'mother_file_number', 'room_number', 'gender', 'newborn_type', 'newborn_status', 'birth_status', 'birth_notification_obstetrics', 'mobile', 'language']],
        'branches_emails' => ['label' => 'بريد الفروع', 'table' => 'branches_emails', 'mode' => 'reference', 'scope' => 'company', 'fields' => ['branches_id', 'subsection', 'email_to', 'email_cc']],
        'central_follow_up' => ['label' => 'متابعة السنترال', 'table' => 'central-follow-up', 'mode' => 'communication', 'scope' => 'branch', 'fields' => ['name', 'caller_name', 'caller_section', 'ext_number', 'date']],
        'central_ext' => ['label' => 'تحويلات السنترال', 'table' => 'central_ext_phone', 'mode' => 'reference', 'scope' => 'company', 'fields' => ['floor_id', 'section_id', 'name', 'job_title', 'mobile', 'ext_phone', 'home_phone']],
        'centralsections' => ['label' => 'أقسام السنترال', 'table' => 'centeral_sections', 'mode' => 'reference', 'scope' => 'company', 'fields' => ['name_en', 'name_ar']],
        'city' => ['label' => 'المدن', 'table' => 'city', 'mode' => 'reference', 'scope' => 'global', 'fields' => ['country_id', 'name_en', 'name_ar', 'name_ch']],
        'commercial_cases' => ['label' => 'القضايا التجارية', 'table' => 'commercial_cases', 'mode' => 'case', 'scope' => 'branch', 'display' => ['case_number', 'claimant_name', 'claimant_cr_number', 'defendant_name', 'defendant_cr_number', 'claim_amount', 'status'], 'fields' => ['commercial_cases_payment_type_id', 'claimant_name', 'claimant_id', 'claimant_cr_number', 'case_date', 'defendant_name', 'defendant_id', 'defendant_cr_number', 'claim_type', 'claim_type_other', 'claim_amount', 'case_summary', 'liable_mobile', 'covered_amount', 'uncovered_amount', 'received_date', 'subject', 'requests', 'attachments_name', 'request_number', 'case_number']],
        'emergency_new_call' => ['label' => 'نداءات السنترال الجديدة', 'table' => 'emergency_new_call', 'mode' => 'clinical', 'scope' => 'branch', 'fields' => ['period', 'location', 'code', 'code_reason']],
        'executive_title' => ['label' => 'السندات التنفيذية', 'table' => 'executive_title', 'mode' => 'case', 'scope' => 'branch', 'display' => ['date', 'liable_name', 'liable_idno', 'file_number', 'request_number', 'status'], 'fields' => ['executive_title_payment_type_id', 'patient_name', 'admission_date', 'file_number', 'discharge_date', 'patient_nationality', 'patient_idno', 'issued_date', 'due_date', 'liable_name', 'liable_idno', 'liable_nationality', 'liable_mobile', 'value', 'value_writing', 'entity', 'company_representative', 'adjudicated_amount', 'request_number', 'case_number', 'instrumen_number', 'instrumen_number_date', 'transferred_from']],
        'executive_title_complete_documents' => ['label' => 'استكمال مستندات السندات التنفيذية', 'table' => 'executive_title', 'mode' => 'documents', 'scope' => 'branch', 'display' => ['patient_name', 'file_number', 'liable_name', 'liable_mobile', 'status', 'executive_title_approval_status_id', 'created_at']],
        'financial_claim_notice' => ['label' => 'إشعارات المطالبات المالية', 'table' => 'financial_claim_notice', 'mode' => 'communication', 'scope' => 'branch', 'fields' => ['mobile', 'contract_type', 'file_number', 'patient_name', 'patient_id_number', 'hospitalization_days', 'amount_due', 'contractor_name', 'contractor_id_number', 'content']],
        'info' => ['label' => 'معلومات النظام', 'table' => 'info', 'mode' => 'reference', 'scope' => 'global', 'fields' => ['name', 'info']],
        'labor_cases' => ['label' => 'القضايا العمالية', 'table' => 'labor_cases', 'mode' => 'case', 'scope' => 'branch', 'display' => ['case_number', 'claimant_name', 'claimant_id', 'defendant_name', 'defendant_id', 'claim_amount', 'status'], 'fields' => ['labor_cases_payment_type_id', 'claimant_name', 'claimant_id', 'claimant_cr_number', 'case_date', 'defendant_name', 'defendant_id', 'defendant_cr_number', 'claim_type', 'claim_type_other', 'claim_amount', 'case_summary', 'liable_mobile', 'nationality', 'id_number', 'covered_amount', 'uncovered_amount', 'received_date', 'subject', 'requests', 'attachments_name', 'request_number', 'case_number']],
        'lawsuit_complete_documents' => ['label' => 'استكمال مستندات المطالبات المالية', 'table' => 'lawsuit', 'mode' => 'documents', 'scope' => 'branch', 'display' => ['patient_name', 'file_number', 'liable_name', 'liable_mobile', 'status', 'lawsuit_approval_status_id', 'created_at']],
        'lawsuit_users_mobile' => ['label' => 'مستخدمي الجوال للقضايا', 'table' => 'lawsuit_users_mobile', 'mode' => 'reference', 'scope' => 'company', 'fields' => ['name_ar', 'mobile', 'email']],
        'lawsuitapproval' => ['label' => 'اعتماد المطالبات المالية', 'table' => 'lawsuit', 'mode' => 'approval', 'scope' => 'branch', 'display' => ['patient_name', 'file_number', 'liable_name', 'liable_mobile', 'status', 'lawsuit_approval_status_id', 'disapproval_reason', 'created_at']],
        'medica_report' => ['label' => 'التقارير الطبية', 'table' => 'medica_report', 'mode' => 'clinical', 'scope' => 'company', 'fields' => ['patient_name', 'nationality', 'birth_date', 'file_number', 'doctor', 'entry_date', 'exit_date', 'medical_diagnosis', 'treatment', 'recommendation', 'report_type', 'visit_date']],
        'medical_approval_notifications' => ['label' => 'إشعارات الموافقات الطبية', 'table' => 'medical_approval_notifications', 'mode' => 'clinical', 'scope' => 'branch', 'fields' => ['patient_name', 'patient_identity', 'room_number', 'clinician_id', 'inpatient_clinician_id', 'medical_approval_cc_ids', 'notes']],
        'medical_cases' => ['label' => 'القضايا الطبية', 'table' => 'medical_cases', 'mode' => 'case', 'scope' => 'branch', 'display' => ['case_number', 'claimant_name', 'claimant_id', 'defendant_name', 'defendant_id', 'status'], 'fields' => ['claimant_name', 'claimant_id', 'defendant_name', 'defendant_id', 'specialty', 'claim_type', 'mobile', 'email', 'agency_number', 'request_number', 'case_number', 'attachments_name']],
        'onlinetody' => ['label' => 'المتابعة الإلكترونية اليومية', 'table' => 'ra_users', 'key' => 'hr_id', 'mode' => 'report', 'scope' => 'branch', 'display' => ['hr_first_name', 'hr_last_name', 'hr_username', 'hr_email_address', 'mobile', 'hr_last_login', 'status']],
        'rep_ss' => ['label' => 'طلب تقرير طبي', 'table' => 'rep_ss', 'mode' => 'clinical', 'scope' => 'branch', 'display' => ['name', 'no_file', 'service', 'Paymentـstatus', 'Patientـname', 'dateIn', 'dateOut', 'countries', 'branches_departments', 'onid', 'details', 'status', 'Answer', 'becuse'], 'fields' => ['name', 'no_file', 'service', 'Paymentـstatus', 'Patientـname', 'dateIn', 'dateOut', 'countries', 'branches_departments', 'onid', 'details']],
        'psychosocial_assessment_all' => ['label' => 'التقييم النفسي والاجتماعي', 'table' => 'psychosocial_assessment', 'mode' => 'clinical', 'scope' => 'company', 'fields' => ['the_name', 'gender', 'file_no', 'room_no', 'religion', 'nationality', 'education_level', 'id_no', 'age', 'address', 'city', 'occupation', 'mobile_no', 'family_support', 'marital_status', 'medical_diagnosis', 'room_type', 'notice']],
        'sanad_reg' => ['label' => 'تفعيل السندات', 'table' => 'sanad_reg', 'mode' => 'finance', 'scope' => 'branch', 'fields' => ['first_no', 'last_no', 'branch', 'comment']],
        'sanad_track1' => ['label' => 'متابعة السندات', 'table' => 'sanad', 'mode' => 'finance', 'scope' => 'branch', 'create' => false, 'fields' => ['sanad_no', 'paid_date', 'paid', 'branch', 'status', 'comment']],
        'sit_rep2' => ['label' => 'طلب إفادة - استفسار مطالبة مالية', 'table' => 'sits_rep', 'mode' => 'communication', 'scope' => 'company', 'display' => ['name', 'onid', 'no_lawsuit', 'Paymentـstatus', 'lawsuit_date', 'dates', 'status', 'c', 'note', 'send_Section', 'becuse'], 'fields' => ['name', 'onid', 'no_lawsuit', 'Paymentـstatus', 'lawsuit_date', 'dates', 'c', 'note', 'send_Section']],
        'shift_schedule' => ['label' => 'جدول المناوبات', 'table' => 'shift_schedule', 'mode' => 'reference', 'scope' => 'company', 'fields' => ['eventTitle', 'eventLabel', 'eventStartDate', 'eventEndDate', 'eventLocation', 'alternative_name']],
        'sms' => ['label' => 'رسائل الجوال', 'table' => 'sms_archive', 'mode' => 'sms', 'scope' => 'branch', 'display' => ['mobile', 'message', 'type', 'language', 'created_by', 'created_at']],
    ];

    public function spec(string $page): array
    {
        abort_unless(isset(self::SPECS[$page]), 404);

        return self::SPECS[$page] + ['fields' => [], 'scope' => 'global', 'mode' => 'report', 'create' => true];
    }

    public function all(): array
    {
        return self::SPECS;
    }

    public function columns(string $page): array
    {
        $spec = $this->spec($page);
        if (! $this->available($page)) {
            return [];
        }

        $actual = Schema::getColumnListing($spec['table']);
        $configured = array_values(array_intersect($spec['display'] ?? $spec['fields'], $actual));

        if ($configured !== []) {
            return $configured;
        }

        return array_values(array_diff($actual, ['created_at', 'updated_at', 'companies_groups_id', 'branch_id']));
    }

    public function available(string $page): bool
    {
        $table = $this->spec($page)['table'];
        if (! $table) {
            return false;
        }

        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return array<string, array<int|string, string>> */
    public function options(string $page): array
    {
        $maps = [
            'adm_country' => [],
            'birth_notification' => [
                'gender' => 'gender',
                'birth_notification_obstetrics' => 'birth_notification_obstetrics',
            ],
            'branches_emails' => ['branches_id' => 'branches', 'subsection' => 'branches_departments'],
            'central_ext' => ['floor_id' => 'centeral_floors', 'section_id' => 'centeral_sections'],
            'city' => ['country_id' => 'country'],
            'commercial_cases' => ['commercial_cases_payment_type_id' => 'commercial_cases_payment_type'],
            'emergency_new_call' => [
                'period' => 'duty_period',
                'location' => 'branches_area',
                'code' => 'emergency_calls_codes',
                'code_reason' => 'emergency_calls_codes_reasons',
            ],
            'executive_title' => ['executive_title_payment_type_id' => 'executive_title_payment_type'],
            'financial_claim_notice' => ['contract_type' => 'branches'],
            'labor_cases' => ['labor_cases_payment_type_id' => 'labor_cases_payment_type'],
            'medical_cases' => ['medical_cases_payment_type_id' => 'medical_cases_payment_type'],
            'medical_approval_notifications' => [
                'clinician_id' => 'clinicians',
                'inpatient_clinician_id' => 'clinicians',
                'medical_approval_status_id' => 'medical_approval_statuses',
                'rejection_reason_id' => 'medical_approval_rejection_reasons',
            ],
            'psychosocial_assessment_all' => ['gender' => 'gender', 'room_type' => 'room_type'],
            'sanad_reg' => ['branch' => 'branches'],
            'sanad_track1' => ['branch' => 'branches'],
            'shift_schedule' => ['eventTitle' => 'clinicians'],
        ];

        $options = [];
        foreach ($maps[$page] ?? [] as $field => $table) {
            $options[$field] = $this->optionList($table);
        }

        if ($page === 'birth_notification') {
            $options['newborn_type'] = [1 => 'مولود واحد', 2 => 'توأم', 3 => 'أكثر من توأم'];
            $options['newborn_status'] = [1 => 'حي', 2 => 'متوفى'];
            $options['birth_status'] = [1 => 'داخل المنشأة', 2 => 'خارج المنشأة'];
            $options['language'] = [1 => 'العربية', 2 => 'English'];
        }

        if (in_array($page, ['commercial_cases', 'labor_cases', 'medical_cases'], true)) {
            $options['claim_type'] = [1 => 'مقاولات', 2 => 'توريد', 3 => 'أخرى'];
        }

        return $options;
    }

    public function supportsAttachments(string $page): bool
    {
        $spec = $this->attachmentSpec($page);

        return $spec !== null && Schema::hasTable($spec['table']);
    }

    public function supportsRequiredDocuments(string $page): bool
    {
        $tables = match ($page) {
            'lawsuit_complete_documents' => ['lawsuit_required_documents', 'lawsuit_required_documents_attachments'],
            'executive_title_complete_documents' => ['executive_title_required_documents', 'executive_title_required_documents_attachments'],
            default => [],
        };

        return $tables !== [] && collect($tables)->every(fn (string $table): bool => Schema::hasTable($table));
    }

    /** @return array<string, string> */
    public function smsSenders(): array
    {
        if (! Schema::hasTable('sms_sender_name')) {
            return [];
        }

        return DB::table('sms_sender_name')->orderBy('sender_name')->pluck('sender_name', 'sender_name')->all();
    }

    /** @return array<string, mixed> */
    public function defaults(string $page): array
    {
        if ($page === 'financial_claim_notice' && Schema::hasTable('financial_claim_notice_content')) {
            return ['content' => (string) (DB::table('financial_claim_notice_content')->value('content') ?? '')];
        }

        return [];
    }

    public function list(string $page, string $search = '', array $filters = []): LengthAwarePaginator
    {
        $spec = $this->spec($page);
        if (! $this->available($page)) {
            return new LengthAwarePaginator([], 0, 25, 1, ['path' => request()->url()]);
        }

        $table = $spec['table'];
        $columns = Schema::getColumnListing($table);
        $query = DB::table($table)->select($table.'.*');
        // Tables without an `id` column expose their primary key as `id` so the
        // shared list/modal template can address rows uniformly.
        if (! empty($spec['key']) && $spec['key'] !== 'id') {
            $query->addSelect($table.'.'.$spec['key'].' as id');
        }
        $this->scope($query, $spec, $table, $columns);

        if ($search !== '') {
            $searchable = array_values(array_intersect($columns, array_merge(
                $spec['fields'],
                $spec['display'] ?? [],
                ['name', 'name_ar', 'name_en', 'patient_name', 'file_number', 'mobile', 'email', 'subject', 'message', 'content', 'note', 'info'],
                $spec['mode'] === 'case' ? ['case_number', 'claimant_cr_number', 'defendant_cr_number', 'claimant_name', 'defendant_name'] : [],
            )));
            if ($searchable !== []) {
                $query->where(function ($nested) use ($searchable, $search): void {
                    foreach ($searchable as $column) {
                        $nested->orWhere($column, 'like', '%'.$search.'%');
                    }
                });
            }
        }

        if ($page === 'medical_approval_notifications') {
            if (($filters['sent_to_collection'] ?? '') !== '' && in_array('sent_to_collection', $columns, true)) {
                $query->where('sent_to_collection', (string) $filters['sent_to_collection']);
            }
            if (! empty($filters['date_from']) && in_array('created_at', $columns, true)) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }
            if (! empty($filters['date_to']) && in_array('created_at', $columns, true)) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }
        }

        if ($page === 'financial_claim_notice') {
            if (($filters['depId'] ?? '') !== '' && in_array('contract_type', $columns, true)) {
                $query->where('contract_type', (string) $filters['depId']);
            }
            if (($filters['statusId'] ?? '') !== '' && in_array('status', $columns, true)) {
                $query->where('status', (string) $filters['statusId']);
            }
        }

        if (($filters['status'] ?? '') !== '' && in_array('status', $columns, true)) {
            $query->where('status', (string) $filters['status']);
        }
        // The legacy case pages filter on their original `date` field even
        // when an audit `created_at` column is also present.
        $dateColumn = $spec['mode'] === 'case' && in_array('date', $columns, true)
            ? 'date'
            : (in_array('created_at', $columns, true) ? 'created_at' : (in_array('date', $columns, true) ? 'date' : (in_array('dates', $columns, true) ? 'dates' : null)));
        if ($dateColumn !== null && ! empty($filters['begin_date'])) {
            $beginDate = ($page === 'financial_claim_notice' || $this->numericColumn($table, $dateColumn)) && $dateColumn === 'date'
                ? strtotime((string) $filters['begin_date'])
                : (string) $filters['begin_date'];
            $query->where($dateColumn, '>=', $beginDate);
        }
        if ($dateColumn !== null && ! empty($filters['end_date'])) {
            $endDate = ($page === 'financial_claim_notice' || $this->numericColumn($table, $dateColumn)) && $dateColumn === 'date'
                ? strtotime((string) $filters['end_date'].' 23:59:59')
                : (string) $filters['end_date'].' 23:59:59';
            $query->where($dateColumn, '<=', $endDate);
        }

        $orderColumn = in_array('created_at', $columns, true) ? 'created_at' : (in_array('id', $columns, true) ? 'id' : $columns[0]);

        return $query->orderByDesc($table.'.'.$orderColumn)->paginate(25)->withQueryString();
    }

    /**
     * Return the old case-page status board using exactly the same tenant,
     * search, status and date filters as the listing below it.
     *
     * @return list<object>
     */
    public function caseDashboard(string $page, string $search = '', array $filters = []): array
    {
        $spec = $this->spec($page);
        $action = $this->caseActionSpec($page);

        if ($spec['mode'] !== 'case' || $action === null || ! $this->available($page) || ! Schema::hasTable($action['statuses'])) {
            return [];
        }

        $statusColumns = Schema::getColumnListing($action['statuses']);
        $statuses = DB::table($action['statuses'])
            ->when(in_array('publish', $statusColumns, true), fn ($query) => $query->where('publish', 1))
            ->orderBy(in_array('ranking', $statusColumns, true) ? 'ranking' : 'id')
            ->get(['id', 'name_ar', ...(in_array('info', $statusColumns, true) ? ['info'] : [])]);

        $table = $spec['table'];
        $columns = Schema::getColumnListing($table);

        return $statuses->map(function (object $status) use ($spec, $table, $columns, $search, $filters): object {
            $query = DB::table($table);
            $this->scope($query, $spec, $table, $columns);
            $this->applyCaseDashboardFilters($query, $table, $columns, $spec, $search, $filters);

            $status->count = $query->where($table.'.status', (int) $status->id)->count();

            return $status;
        })->all();
    }

    public function find(string $page, int $id): object
    {
        abort_unless($this->available($page), 404);
        $spec = $this->spec($page);
        $table = $spec['table'];
        $columns = Schema::getColumnListing($table);
        $query = DB::table($table)->where($table.'.id', $id);
        $this->scope($query, $spec, $table, $columns);
        $row = $query->first();

        abort_if($row === null, 404);

        return $row;
    }

    public function save(string $page, array $data, ?int $id = null): int
    {
        abort_unless($this->available($page), 404);
        $spec = $this->spec($page);
        abort_if($id === null && ! $spec['create'], 422, 'هذه الصفحة مخصصة للمتابعة ولا تسمح بإنشاء سجلات جديدة.');
        $table = $spec['table'];
        $columns = Schema::getColumnListing($table);
        $values = [];

        foreach ($spec['fields'] as $field) {
            if (in_array($field, $columns, true) && array_key_exists($field, $data)) {
                $value = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
                if (in_array($field, ['date', 'paid_date'], true) && is_string($value) && $value !== '' && ! is_numeric($value) && $this->numericColumn($table, $field)) {
                    $value = strtotime($value);
                }
                $values[$field] = $value;
            }
        }

        $creating = $id === null;
        $this->ownership($values, $columns, $spec['scope'], $creating);
        if ($creating && in_array('publish', $columns, true) && ! array_key_exists('publish', $values)) {
            $values['publish'] = 1;
        }
        if ($creating && in_array('date', $columns, true) && ! array_key_exists('date', $values)) {
            $values['date'] = (string) now()->timestamp;
        }
        if ($creating && in_array('create_at', $columns, true) && ! array_key_exists('create_at', $values)) {
            $values['create_at'] = now();
        }
        if ($creating && in_array('dates', $columns, true) && ! array_key_exists('dates', $values)) {
            $values['dates'] = now()->format('Y-m-d H:i:s');
        }
        if ($creating && in_array('status', $columns, true) && ! array_key_exists('status', $values)) {
            $values['status'] = '1';
        }
        if ($creating && in_array('gorup_id', $columns, true) && ! array_key_exists('gorup_id', $values)) {
            $values['gorup_id'] = (int) session('companies_groups_id');
        }
        if ($creating && in_array('grop_id', $columns, true) && ! array_key_exists('grop_id', $values)) {
            $values['grop_id'] = (int) session('companies_groups_id');
        }
        if ($creating && in_array('random', $columns, true) && ! array_key_exists('random', $values)) {
            $values['random'] = Str::random(22);
        }
        if ($creating && in_array('c', $columns, true) && ! array_key_exists('c', $values)) {
            $values['c'] = Str::random(16);
        }
        if (in_array($page, ['sanad_reg', 'sanad_track1'], true) && in_array('last_update', $columns, true)) {
            $values['last_update'] = $this->numericColumn($table, 'last_update') ? now()->timestamp : now()->format('YmdHis');
        }
        if (! $creating) {
            if (in_array('updated_by', $columns, true)) {
                $values['updated_by'] = (int) session('hr_user_id');
            }
            if (in_array('updated_at', $columns, true)) {
                $values['updated_at'] = now();
            }
            if (in_array('updated_name', $columns, true)) {
                $values['updated_name'] = $this->numericColumn($table, 'updated_name')
                    ? (int) session('hr_user_id')
                    : (string) session('hr_username', session('hr_user_id'));
            }
        } elseif (in_array('intered_name', $columns, true)) {
            $values['intered_name'] = $this->numericColumn($table, 'intered_name')
                ? (int) session('hr_user_id')
                : (string) session('hr_username', session('hr_user_id'));
        }
        if ($creating && in_array('m_group', $columns, true)) {
            $values['m_group'] = (int) session('m_group', session('companies_groups_id'));
        }

        if ($id === null) {
            $id = (int) DB::table($table)->insertGetId($values);
        } else {
            $this->find($page, $id);
            DB::table($table)->where('id', $id)->update($values);
        }

        if ($page === 'emergency_new_call' && array_key_exists('responsibles', $data) && Schema::hasTable('emergency_new_call_responsibles')) {
            $responsibles = preg_split('/[\s,;\r\n]+/', (string) $data['responsibles'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
            DB::table('emergency_new_call_responsibles')->where('emergency_new_call_id', $id)->delete();
            foreach (array_unique($responsibles) as $responsible) {
                DB::table('emergency_new_call_responsibles')->insert(['emergency_new_call_id' => $id, 'responsible_id' => (int) $responsible, 'response' => 0]);
            }
        }

        return $id;
    }

    public function toggle(string $page, int $id): void
    {
        abort_unless($this->spec($page)['mode'] === 'reference', 422);
        $row = $this->find($page, $id);
        $table = $this->spec($page)['table'];
        $columns = Schema::getColumnListing($table);

        if (in_array('publish', $columns, true)) {
            DB::table($table)->where('id', $id)->update(['publish' => (int) ! (int) ($row->publish ?? 0)]);
        } elseif (in_array('status', $columns, true)) {
            DB::table($table)->where('id', $id)->update(['status' => (string) ($row->status ?? '') === '0' ? '1' : '0']);
        }
    }

    public function delete(string $page, int $id): void
    {
        $row = $this->find($page, $id);
        $table = $this->spec($page)['table'];
        $columns = Schema::getColumnListing($table);
        if (in_array('deleted_at', $columns, true)) {
            DB::table($table)->where('id', $id)->update(['deleted_at' => now(), 'deleted_by' => session('hr_user_id')]);
            return;
        }

        abort_unless($this->spec($page)['mode'] === 'reference', 422);
        DB::table($table)->where('id', $row->id)->delete();
    }

    public function action(string $page, int $id, string $action, ?string $reason = null): void
    {
        $row = $this->find($page, $id);

        if ($page === 'medical_approval_notifications' && $action === 'send') {
            DB::table('medical_approval_notifications')->where('id', $row->id)->update([
                'sent_to_collection' => 1,
                'sent_to_collection_by' => (int) session('hr_user_id'),
                'sent_to_collection_at' => now(),
                'updated_by' => (int) session('hr_user_id'),
                'updated_at' => now(),
            ]);

            return;
        }

        if ($page === 'lawsuitapproval' && in_array($action, ['approve', 'reject'], true)) {
            $status = $action === 'approve' ? 1 : 2;
            DB::transaction(function () use ($row, $status, $reason): void {
                if (Schema::hasTable('lawsuit_manager_action')) {
                    DB::table('lawsuit_manager_action')->insert([
                        'lawsuit_id' => $row->id,
                        'status_id' => $status,
                        'details' => $reason,
                        'created_by' => (int) session('hr_user_id'),
                        'created_at' => now(),
                    ]);
                }
                if ($status === 1 && Schema::hasTable('lawsuit_actions')) {
                    DB::table('lawsuit_actions')->insert([
                        'lawsuit_id' => $row->id,
                        'status_id' => 17,
                        'branch_id' => (int) session('hr_branch_id'),
                        'created_by' => (int) session('hr_user_id'),
                    ]);
                }
                DB::table('lawsuit')->where('id', $row->id)->update([
                    'lawsuit_approval_status_id' => $status,
                    'disapproval_reason' => $reason,
                    'updated_by' => (int) session('hr_user_id'),
                    'updated_at' => now(),
                ]);
            });

            return;
        }

        abort(422, 'هذه العملية غير مدعومة لهذه الصفحة.');
    }

    public function attachments(string $page, int $id): array
    {
        $attachment = $this->attachmentSpec($page);
        if ($attachment === null || ! Schema::hasTable($attachment['table'])) {
            return [];
        }

        $this->find($page, $id);

        return DB::table($attachment['table'])->where($attachment['foreign'], $id)->orderByDesc('id')->get()->all();
    }

    public function uploadAttachment(string $page, int $id, \Illuminate\Http\UploadedFile $file): void
    {
        $attachment = $this->attachmentSpec($page);
        abort_unless($attachment !== null && Schema::hasTable($attachment['table']), 404);
        $this->find($page, $id);
        $path = $file->store('legacy-sidebar/'.$page);
        $columns = Schema::getColumnListing($attachment['table']);
        $values = [$attachment['foreign'] => $id, 'file_name' => $path];
        if (in_array('file', $columns, true) && ! in_array('file_name', $columns, true)) {
            $values = [$attachment['foreign'] => $id, 'file' => $path];
        }
        foreach (['created_by', 'uploaded_by'] as $column) {
            if (in_array($column, $columns, true)) {
                $values[$column] = (int) session('hr_user_id');
            }
        }
        if (in_array('companies_groups_id', $columns, true)) {
            $values['companies_groups_id'] = (int) session('companies_groups_id');
        }
        if (in_array('branch_id', $columns, true)) {
            $values['branch_id'] = (int) session('hr_branch_id');
        }
        if (in_array('created_at', $columns, true)) {
            $values['created_at'] = now();
        }
        DB::table($attachment['table'])->insert($values);
    }

    /** @param array<int, mixed> $files @param array<int, mixed> $labels */
    public function uploadAttachments(string $page, int $id, array $files, array $labels = []): void
    {
        $attachment = $this->attachmentSpec($page);
        abort_unless($attachment !== null && Schema::hasTable($attachment['table']), 404);
        $this->find($page, $id);
        $columns = Schema::getColumnListing($attachment['table']);

        foreach (array_values($files) as $index => $file) {
            if (! $file instanceof \Illuminate\Http\UploadedFile) {
                continue;
            }
            $path = $file->store('legacy-sidebar/'.$page);
            $values = [$attachment['foreign'] => $id];
            if (in_array('name', $columns, true)) {
                $values['name'] = trim((string) ($labels[$index] ?? $file->getClientOriginalName()));
            }
            if (in_array('file_name', $columns, true)) {
                $values['file_name'] = $path;
            } elseif (in_array('file', $columns, true)) {
                $values['file'] = $path;
            }
            foreach (['created_by', 'uploaded_by'] as $column) {
                if (in_array($column, $columns, true)) {
                    $values[$column] = (int) session('hr_user_id');
                }
            }
            if (in_array('created_at', $columns, true)) {
                $values['created_at'] = now();
            }
            DB::table($attachment['table'])->insert($values);
        }
    }

    public function updateRequestStatus(string $page, int $id, int $status, ?string $reason = null, ?int $sendSection = null): void
    {
        abort_unless(in_array($page, ['rep_ss', 'sit_rep2'], true), 404);
        $row = $this->find($page, $id);
        $table = $this->spec($page)['table'];
        $columns = Schema::getColumnListing($table);
        $updates = array_intersect_key([
            'status' => $status,
            'userid_new' => (int) session('hr_user_id'),
            'becuse' => $reason,
            'send_Section' => $sendSection,
        ], array_flip($columns));
        DB::table($table)->where('id', $row->id)->update($updates);

        $timeline = $page === 'rep_ss' ? 'rep_st' : 'sit_timeline';
        if (Schema::hasTable($timeline)) {
            $timelineColumns = Schema::getColumnListing($timeline);
            DB::table($timeline)->insert(array_intersect_key([
                'userid' => (int) session('hr_user_id'),
                'id_data' => $id,
                'status' => 4,
                'created_at' => now(),
                'create_at' => now(),
            ], array_flip($timelineColumns)));
        }
    }

    public function downloadAttachment(string $page, int $id, int $attachmentId): string
    {
        $attachment = $this->attachmentSpec($page);
        abort_unless($attachment !== null && Schema::hasTable($attachment['table']), 404);
        $this->find($page, $id);
        $row = DB::table($attachment['table'])->where('id', $attachmentId)->where($attachment['foreign'], $id)->first();
        abort_if($row === null, 404);
        $path = (string) ($row->file_name ?? $row->file ?? '');
        abort_unless($path !== '' && Storage::exists($path), 404);

        return Storage::path($path);
    }

    public function requiredDocuments(string $page, int $id): array
    {
        $spec = match ($page) {
            'lawsuit_complete_documents' => ['documents' => 'lawsuit_required_documents', 'attachments' => 'lawsuit_required_documents_attachments', 'foreign' => 'lawsuit_id', 'document' => 'lawsuit_required_documents_id'],
            'executive_title_complete_documents' => ['documents' => 'executive_title_required_documents', 'attachments' => 'executive_title_required_documents_attachments', 'foreign' => 'executive_title_id', 'document' => 'executive_title_required_documents_id'],
            default => null,
        };
        if ($spec === null || ! Schema::hasTable($spec['documents']) || ! Schema::hasTable($spec['attachments'])) {
            return [];
        }
        $this->find($page, $id);
        $attachments = DB::table($spec['attachments'])->where($spec['foreign'], $id)->get()->keyBy($spec['document']);

        return DB::table($spec['documents'])->where('publish', 1)->orderBy('id')->get()->map(fn ($document) => ['document' => $document, 'attachment' => $attachments->get($document->id)])->all();
    }

    public function uploadRequiredDocument(string $page, int $id, int $documentId, \Illuminate\Http\UploadedFile $file): void
    {
        $spec = match ($page) {
            'lawsuit_complete_documents' => ['documents' => 'lawsuit_required_documents', 'attachments' => 'lawsuit_required_documents_attachments', 'foreign' => 'lawsuit_id', 'document' => 'lawsuit_required_documents_id', 'parent' => 'lawsuit'],
            'executive_title_complete_documents' => ['documents' => 'executive_title_required_documents', 'attachments' => 'executive_title_required_documents_attachments', 'foreign' => 'executive_title_id', 'document' => 'executive_title_required_documents_id', 'parent' => 'executive_title'],
            default => null,
        };
        abort_unless($spec !== null && Schema::hasTable($spec['documents']) && Schema::hasTable($spec['attachments']), 404);
        $this->find($page, $id);
        abort_unless(DB::table($spec['documents'])->where('id', $documentId)->where('publish', 1)->exists(), 404);
        $path = $file->store('legacy-sidebar/'.$page.'/required-documents');
        $columns = Schema::getColumnListing($spec['attachments']);
        $values = [$spec['foreign'] => $id, $spec['document'] => $documentId, 'file' => $path];
        foreach (['companies_groups_id' => (int) session('companies_groups_id'), 'branch_id' => (int) session('hr_branch_id'), 'created_by' => (int) session('hr_user_id'), 'uploaded_by' => (int) session('hr_user_id'), 'uploaded_at' => now()->format('Y-m-d H:i:s')] as $column => $value) {
            if (in_array($column, $columns, true)) {
                $values[$column] = $value;
            }
        }
        $existing = DB::table($spec['attachments'])->where($spec['foreign'], $id)->where($spec['document'], $documentId)->first();
        if ($existing) {
            DB::table($spec['attachments'])->where('id', $existing->id)->update($values);
        } else {
            DB::table($spec['attachments'])->insert($values);
        }

        $missing = DB::table($spec['documents'])->where('publish', 1)->whereNotExists(function ($query) use ($spec, $id): void {
            $query->selectRaw('1')->from($spec['attachments'])->whereColumn($spec['attachments'].'.'.$spec['document'], $spec['documents'].'.id')->where($spec['attachments'].'.'.$spec['foreign'], $id)->whereNotNull($spec['attachments'].'.file');
        })->exists();
        if (! $missing && Schema::hasColumn($spec['parent'], 'status')) {
            DB::table($spec['parent'])->where('id', $id)->update(['status' => 14]);
        }
    }

    public function downloadRequiredDocument(string $page, int $id, int $documentId): string
    {
        $spec = match ($page) {
            'lawsuit_complete_documents' => ['attachments' => 'lawsuit_required_documents_attachments', 'foreign' => 'lawsuit_id', 'document' => 'lawsuit_required_documents_id'],
            'executive_title_complete_documents' => ['attachments' => 'executive_title_required_documents_attachments', 'foreign' => 'executive_title_id', 'document' => 'executive_title_required_documents_id'],
            default => null,
        };
        abort_unless($spec !== null && Schema::hasTable($spec['attachments']), 404);
        $this->find($page, $id);
        $row = DB::table($spec['attachments'])->where($spec['foreign'], $id)->where($spec['document'], $documentId)->first();
        $path = (string) ($row->file ?? '');
        abort_unless($row !== null && $path !== '' && Storage::exists($path), 404);

        return Storage::path($path);
    }

    public function sendSms(string $page, array $data, SmsGateway $gateway): int
    {
        abort_unless($this->spec($page)['mode'] === 'sms', 404);
        $table = $this->spec($page)['table'];
        $archiveColumns = $this->available($page) ? Schema::getColumnListing($table) : [];
        $mobiles = preg_split('/[\s,;\r\n]+/', (string) ($data['mobile'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $message = trim((string) ($data['message'] ?? ''));
        $sent = 0;

        foreach (array_unique($mobiles) as $mobile) {
            $normalizedMobile = preg_replace('/\D+/', '', (string) $mobile) ?? '';
            abort_unless(strlen($normalizedMobile) >= 9 && strlen($normalizedMobile) <= 15, 422, 'رقم الجوال غير صالح: '.$mobile);
            $result = $gateway->send((string) $mobile, $message, $data['sender'] ?? null);
            abort_unless($result['ok'], 502, 'تعذر إرسال الرسالة إلى '.$mobile.'.');
            if ($archiveColumns !== []) {
                $values = ['mobile' => $normalizedMobile, 'message' => $message];
                foreach (['created_by' => (int) session('hr_user_id'), 'branch_id' => (int) session('hr_branch_id'), 'companies_groups_id' => (int) session('companies_groups_id'), 'type' => (int) ($data['type'] ?? 0), 'language' => (int) ($data['language'] ?? 1)] as $column => $value) {
                    if (in_array($column, $archiveColumns, true)) {
                        $values[$column] = $value;
                    }
                }
                if (in_array('token', $archiveColumns, true)) {
                    $values['token'] = Str::random(32);
                }
                DB::table($table)->insert($values);
            }
            $sent++;
        }

        return $sent;
    }

    public function archiveSms(string $mobile, string $message, int $type = 1, int $language = 1): void
    {
        if (! Schema::hasTable('sms_archive')) {
            return;
        }

        $columns = Schema::getColumnListing('sms_archive');
        $values = [
            'mobile' => preg_replace('/\D+/', '', $mobile),
            'message' => $message,
        ];
        foreach (['created_by' => (int) session('hr_user_id'), 'branch_id' => (int) session('hr_branch_id'), 'companies_groups_id' => (int) session('companies_groups_id'), 'type' => $type, 'language' => $language] as $column => $value) {
            if (in_array($column, $columns, true)) {
                $values[$column] = $value;
            }
        }
        if (in_array('created_at', $columns, true)) {
            $values['created_at'] = now();
        }
        if (in_array('token', $columns, true)) {
            $values['token'] = Str::random(32);
        }

        DB::table('sms_archive')->insert($values);
    }

    public function logMedicalApproval(int $notification, int $status, ?int $reason, ?string $notes): void
    {
        if (! Schema::hasTable('medical_approval_notification_logs')) {
            return;
        }
        DB::table('medical_approval_notification_logs')->insert([
            'medical_approval_notifications_id' => $notification,
            'medical_approval_status_id' => $status,
            'rejection_reason_id' => $reason ?: null,
            'notes' => $notes,
            'created_at' => now(),
            'created_by' => (int) session('hr_user_id'),
        ]);
    }

    public function workflowState(string $page, int $id): ?object
    {
        if ($page !== 'medical_approval_notifications' || ! Schema::hasTable('medical_approval_notification_logs')) {
            return null;
        }

        $this->find($page, $id);

        return DB::table('medical_approval_notification_logs')
            ->where('medical_approval_notifications_id', $id)
            ->orderByDesc('id')
            ->first();
    }

    /** @return array{notification:object,mobiles:array<int,string>,emails:array<int,string>} */
    public function medicalApprovalDeliveryData(int $id): array
    {
        $notification = $this->find('medical_approval_notifications', $id);
        $companyId = (int) session('companies_groups_id');
        $recipients = collect();

        foreach (['medical_approval_collections', 'medical_approval_cc'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            $columns = Schema::getColumnListing($table);
            $query = DB::table($table);
            if (in_array('companies_groups_id', $columns, true)) {
                $query->where('companies_groups_id', $companyId);
            }
            if (in_array('status', $columns, true)) {
                $query->where('status', 'active');
            }
            $recipients = $recipients->concat($query->get());
        }

        return [
            'notification' => $notification,
            'mobiles' => $recipients->pluck('mobile')->filter()->map(fn ($value) => trim((string) $value))->unique()->values()->all(),
            'emails' => $recipients->pluck('email')->filter()->map(fn ($value) => trim((string) $value))->unique()->values()->all(),
        ];
    }

    public function workflowHistory(string $page, int $id): array
    {
        $this->find($page, $id);

        $caseAction = $this->caseActionSpec($page);
        if ($caseAction !== null && Schema::hasTable($caseAction['table'])) {
            $query = DB::table($caseAction['table'].' as actions')
                ->where('actions.'.$caseAction['foreign'], $id)
                ->orderByDesc('actions.id');
            if (Schema::hasTable($caseAction['statuses'])) {
                $query->leftJoin($caseAction['statuses'].' as statuses', 'statuses.id', '=', 'actions.status_id')
                    ->select(['actions.*', 'statuses.name_ar as status_name']);
            } else {
                $query->select('actions.*');
            }

            return $query->get()->map(function ($event) use ($caseAction) {
                $event->has_request_file = filled($event->{$caseAction['files']['request_file']} ?? null);
                $event->has_session_file = filled($event->{$caseAction['files']['session_file']} ?? null);

                return $event;
            })->all();
        }

        if ($page === 'medical_approval_notifications' && Schema::hasTable('medical_approval_notification_logs')) {
            return DB::table('medical_approval_notification_logs as logs')
                ->leftJoin('medical_approval_statuses as statuses', 'statuses.id', '=', 'logs.medical_approval_status_id')
                ->leftJoin('medical_approval_rejection_reasons as reasons', 'reasons.id', '=', 'logs.rejection_reason_id')
                ->where('logs.medical_approval_notifications_id', $id)
                ->orderByDesc('logs.id')
                ->select(['logs.*', 'statuses.name_ar as status_name', 'reasons.name_ar as reason_name'])
                ->get()->all();
        }

        if ($page === 'rep_ss' || $page === 'sit_rep2') {
            $timeline = $page === 'rep_ss' ? 'rep_st' : 'sit_timeline';
            if (Schema::hasTable($timeline)) {
                return DB::table($timeline)->where('id_data', $id)->orderByDesc('id')->get()->map(function ($event) {
                    $event->status_name = match ((int) ($event->status ?? 0)) {
                        1 => 'تم الإنشاء',
                        2 => 'تمت الإفادة',
                        3 => 'تصعيد',
                        4 => 'تحديث الحالة',
                        default => 'إجراء',
                    };
                    return $event;
                })->all();
            }
        }

        if ($page === 'lawsuitapproval' && Schema::hasTable('lawsuit_manager_action')) {
            return DB::table('lawsuit_manager_action as actions')
                ->leftJoin('lawsuit_approval_status as statuses', 'statuses.id', '=', 'actions.status_id')
                ->where('actions.lawsuit_id', $id)
                ->orderByDesc('actions.id')
                ->select(['actions.*', 'statuses.name_ar as status_name'])
                ->get()->all();
        }

        return [];
    }

    /** @return array<int|string, string> */
    public function caseStatuses(string $page): array
    {
        $spec = $this->caseActionSpec($page);

        return $spec === null ? [] : $this->optionList($spec['statuses']);
    }

    /** @return list<object> */
    public function caseStatements(string $page, int $id): array
    {
        $this->find($page, $id);
        $spec = $this->caseStatementSpec($page);
        if ($spec === null || ! Schema::hasTable($spec['table'])) {
            return [];
        }

        return DB::table($spec['table'])
            ->where($spec['foreign'], $id)
            ->orderByDesc('id')
            ->get()
            ->all();
    }

    public function canReplyToCaseStatements(string $page): bool
    {
        $spec = $this->caseStatementSpec($page);

        return $spec !== null
            && Schema::hasTable($spec['table'])
            && Schema::hasColumn($spec['table'], 'reply');
    }

    public function addCaseStatement(string $page, int $id, array $data, ?\Illuminate\Http\UploadedFile $file = null): void
    {
        $this->find($page, $id);
        $spec = $this->caseStatementSpec($page);
        abort_unless($spec !== null && Schema::hasTable($spec['table']), 404);

        $columns = Schema::getColumnListing($spec['table']);
        $values = [$spec['foreign'] => $id];
        foreach (['details', 'summary', 'section'] as $field) {
            if (in_array($field, $columns, true) && array_key_exists($field, $data)) {
                $values[$field] = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
            }
        }
        if (in_array('branch_id', $columns, true)) $values['branch_id'] = (int) session('hr_branch_id');
        if (in_array('created_by', $columns, true)) $values['created_by'] = (int) session('hr_user_id');
        if (in_array('created_at', $columns, true)) $values['created_at'] = now();
        if ($file !== null && in_array('file', $columns, true)) $values['file'] = $file->store('legacy-sidebar/'.$page.'/statements');

        DB::table($spec['table'])->insert($values);
    }

    public function replyCaseStatement(string $page, int $id, int $statementId, array $data, ?\Illuminate\Http\UploadedFile $file = null): void
    {
        $this->find($page, $id);
        $spec = $this->caseStatementSpec($page);
        abort_unless($spec !== null && Schema::hasTable($spec['table']), 404);

        $columns = Schema::getColumnListing($spec['table']);
        abort_unless(in_array('reply', $columns, true), 422, 'هذه الصفحة لا تحتوي على إجراء رد مستقل في بياناتها القديمة.');
        $statement = DB::table($spec['table'])->where('id', $statementId)->where($spec['foreign'], $id)->first();
        abort_if($statement === null, 404);

        $values = [];
        if (in_array('reply', $columns, true)) $values['reply'] = trim((string) $data['reply']);
        if (in_array('reply_date', $columns, true)) $values['reply_date'] = now()->format('Y-m-d H:i:s');
        if ($file !== null && in_array('file', $columns, true)) $values['file'] = $file->store('legacy-sidebar/'.$page.'/statements');
        DB::table($spec['table'])->where('id', $statementId)->where($spec['foreign'], $id)->update($values);
    }

    public function addCaseAction(string $page, int $id, array $data, array $files): void
    {
        $spec = $this->caseActionSpec($page);
        abort_unless($spec !== null && Schema::hasTable($spec['table']), 404);
        $this->find($page, $id);
        abort_unless(array_key_exists((int) $data['status_id'], $this->caseStatuses($page)), 422, 'حالة القضية غير صالحة.');

        $columns = Schema::getColumnListing($spec['table']);
        $values = [
            $spec['foreign'] => $id,
            'status_id' => (int) $data['status_id'],
            'branch_id' => (int) session('hr_branch_id'),
            'created_by' => (int) session('hr_user_id'),
        ];
        foreach (['details', 'request_number', 'applicant', 'request_date', 'case_number', 'sessions_number', 'session_summary', 'judgment_instrument', 'sessions_date', 'next_sessions_date'] as $field) {
            if (in_array($field, $columns, true) && array_key_exists($field, $data)) {
                $values[$field] = trim((string) $data[$field]);
            }
        }
        foreach ($spec['files'] as $input => $column) {
            if (isset($files[$input]) && $files[$input] instanceof \Illuminate\Http\UploadedFile && in_array($column, $columns, true)) {
                $values[$column] = $files[$input]->store('legacy-sidebar/'.$page.'/actions');
            }
        }
        if (in_array('created_at', $columns, true)) {
            $values['created_at'] = now();
        }

        DB::transaction(function () use ($spec, $values, $id, $data): void {
            DB::table($spec['table'])->insert($values);
            $parent = $this->spec($spec['page'])['table'];
            $updates = ['status' => (int) $data['status_id']];
            $parentColumns = Schema::getColumnListing($parent);
            if (in_array('updated_by', $parentColumns, true)) {
                $updates['updated_by'] = (int) session('hr_user_id');
            }
            if (in_array('updated_at', $parentColumns, true)) {
                $updates['updated_at'] = now();
            }
            DB::table($parent)->where('id', $id)->update($updates);
        });
    }

    public function downloadCaseActionFile(string $page, int $id, int $actionId, string $kind): string
    {
        $spec = $this->caseActionSpec($page);
        abort_unless($spec !== null && isset($spec['files'][$kind]), 404);
        $this->find($page, $id);
        $column = $spec['files'][$kind];
        $path = DB::table($spec['table'])->where('id', $actionId)->where($spec['foreign'], $id)->value($column);
        abort_unless(filled($path) && Storage::exists((string) $path), 404);

        return Storage::path((string) $path);
    }

    private function scope($query, array $spec, string $table, array $columns): void
    {
        // مدير النظام العام هو حساب المراجعة الشامل: لا يُقيد بشركة أو فرع.
        if ((int) session('hr_user_level', 0) === 3) {
            if (in_array('deleted_at', $columns, true)) {
                $query->whereNull($table.'.deleted_at');
            }
            return;
        }

        if ($spec['scope'] === 'company' && in_array('companies_groups_id', $columns, true)) {
            $query->where($table.'.companies_groups_id', (int) session('companies_groups_id'));
        }
        if ($spec['scope'] === 'company' && in_array('gorup_id', $columns, true)) {
            $query->where($table.'.gorup_id', (int) session('companies_groups_id'));
        }
        if ($spec['scope'] === 'company' && in_array('grop_id', $columns, true)) {
            $query->where($table.'.grop_id', (int) session('companies_groups_id'));
        }

        if ($spec['scope'] === 'branch') {
            if (in_array('companies_groups_id', $columns, true)) {
                $query->where($table.'.companies_groups_id', (int) session('companies_groups_id'));
            }
            if (in_array('gorup_id', $columns, true)) {
                $query->where($table.'.gorup_id', (int) session('companies_groups_id'));
            }
            if (in_array('grop_id', $columns, true)) {
                $query->where($table.'.grop_id', (int) session('companies_groups_id'));
            }
            if (in_array('branch_id', $columns, true)) {
                $query->where($table.'.branch_id', (int) session('hr_branch_id'));
            } elseif (in_array('branch', $columns, true)) {
                $query->where($table.'.branch', (int) session('hr_branch_id'));
            }
        }

        if (in_array('deleted_at', $columns, true)) {
            $query->whereNull($table.'.deleted_at');
        }
    }

    /** @param array<string, mixed> $filters */
    private function applyCaseDashboardFilters($query, string $table, array $columns, array $spec, string $search, array $filters): void
    {
        if ($search !== '') {
            $searchable = array_values(array_intersect($columns, array_merge(
                $spec['fields'],
                ['case_number', 'claimant_cr_number', 'defendant_cr_number', 'claimant_name', 'defendant_name']
            )));
            if ($searchable !== []) {
                $query->where(function ($nested) use ($searchable, $search): void {
                    foreach ($searchable as $column) {
                        $nested->orWhere($column, 'like', '%'.$search.'%');
                    }
                });
            }
        }

        if (($filters['status'] ?? '') !== '' && in_array('status', $columns, true)) {
            $query->where($table.'.status', (string) $filters['status']);
        }

        if (! in_array('date', $columns, true)) {
            return;
        }

        if (! empty($filters['begin_date'])) {
            $query->where($table.'.date', '>=', $this->numericColumn($table, 'date')
                ? strtotime((string) $filters['begin_date'])
                : (string) $filters['begin_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where($table.'.date', '<=', $this->numericColumn($table, 'date')
                ? strtotime((string) $filters['end_date'].' 23:59:59')
                : (string) $filters['end_date'].' 23:59:59');
        }
    }

    private function ownership(array &$values, array $columns, string $scope, bool $creating): void
    {
        $userId = (int) session('hr_user_id');
        if ($scope !== 'global' && in_array('companies_groups_id', $columns, true)) {
            $values['companies_groups_id'] = (int) session('companies_groups_id');
        }
        if ($creating && $scope !== 'global' && in_array('branch_id', $columns, true)) {
            $values['branch_id'] = (int) session('hr_branch_id');
        }
        if ($scope === 'branch') {
            if (in_array('branch_id', $columns, true)) {
                $values['branch_id'] = (int) session('hr_branch_id');
            } elseif (in_array('branch', $columns, true)) {
                $values['branch'] = (int) session('hr_branch_id');
            }
        }
        foreach (['created_by', 'creator', 'user_id'] as $column) {
            if ($creating && in_array($column, $columns, true) && ! array_key_exists($column, $values)) {
                $values[$column] = $userId;
            }
        }
        if ($creating && in_array('userid', $columns, true) && ! array_key_exists('userid', $values)) {
            $values['userid'] = $userId;
        }
        if (in_array('gorup_id', $columns, true) && ! array_key_exists('gorup_id', $values)) {
            $values['gorup_id'] = (int) session('companies_groups_id');
        }
        if (in_array('grop_id', $columns, true) && ! array_key_exists('grop_id', $values)) {
            $values['grop_id'] = (int) session('companies_groups_id');
        }
        if ($creating && in_array('created_at', $columns, true) && ! array_key_exists('created_at', $values)) {
            $values['created_at'] = now();
        }
    }

    /** @return array<int|string, string> */
    private function optionList(string $table): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        $columns = Schema::getColumnListing($table);
        $id = in_array('id', $columns, true) ? 'id' : $columns[0];
        $labels = array_values(array_intersect(['name_ar', 'name_en', 'name', 'hr_first_name'], $columns));
        if ($labels === []) {
            return [];
        }

        $query = DB::table($table)->select(array_unique(array_merge([$id], $labels)));
        if (in_array('publish', $columns, true)) {
            $query->where('publish', 1);
        }
        if ($table === 'branches' && in_array('companies_groups_id', $columns, true)) {
            $query->where('companies_groups_id', (int) session('companies_groups_id'));
        }

        return $query->orderBy($labels[0])->get()->mapWithKeys(function ($row) use ($id, $labels): array {
            $parts = [];
            foreach ($labels as $label) {
                $value = trim((string) ($row->{$label} ?? ''));
                if ($value !== '') {
                    $parts[] = $value;
                }
            }

            return [$row->{$id} => implode(' - ', array_unique($parts))];
        })->all();
    }

    private function numericColumn(string $table, string $column): bool
    {
        if (! Schema::hasColumn($table, $column)) {
            return false;
        }

        return in_array(strtolower(Schema::getColumnType($table, $column)), ['int', 'integer', 'bigint', 'smallint', 'tinyint', 'mediumint', 'decimal', 'float', 'double'], true);
    }

    private function attachmentSpec(string $page): ?array
    {
        return match ($page) {
            'lawsuitapproval' => ['table' => 'lawsuit_attachments', 'foreign' => 'lawsuit_id'],
            'executive_title' => ['table' => 'executive_title_attachments', 'foreign' => 'executive_title_id'],
            'administrative_cases' => ['table' => 'administrative_cases_attachments', 'foreign' => 'administrative_cases_id'],
            'commercial_cases' => ['table' => 'commercial_cases_attachments', 'foreign' => 'commercial_cases_id'],
            'labor_cases' => ['table' => 'labor_cases_attachments', 'foreign' => 'labor_cases_id'],
            'medical_cases' => ['table' => 'medical_cases_attachments', 'foreign' => 'medical_cases_id'],
            'medica_report' => ['table' => 'medica_report_attachments', 'foreign' => 'corpse_id'],
            'rep_ss' => ['table' => 'rep_sfi', 'foreign' => 'id_data'],
            'sit_rep2' => ['table' => 'sit_files', 'foreign' => 'id_data'],
            default => null,
        };
    }

    private function caseActionSpec(string $page): ?array
    {
        return match ($page) {
            'administrative_cases' => ['page' => $page, 'table' => 'administrative_cases_actions', 'foreign' => 'administrative_cases_id', 'statuses' => 'administrative_cases_status', 'files' => ['request_file' => 'administrative_cases_request_file', 'session_file' => 'session_1_file']],
            'commercial_cases' => ['page' => $page, 'table' => 'commercial_cases_actions', 'foreign' => 'commercial_cases_id', 'statuses' => 'commercial_cases_status', 'files' => ['request_file' => 'commercial_cases_request_file', 'session_file' => 'session_1_file']],
            'labor_cases' => ['page' => $page, 'table' => 'labor_cases_actions', 'foreign' => 'labor_cases_id', 'statuses' => 'labor_cases_status', 'files' => ['request_file' => 'labor_cases_request_file', 'session_file' => 'session_1_file']],
            'medical_cases' => ['page' => $page, 'table' => 'medical_cases_actions', 'foreign' => 'medical_cases_id', 'statuses' => 'medical_cases_status', 'files' => ['request_file' => 'medical_cases_request_file', 'session_file' => 'session_1_file']],
            'executive_title' => ['page' => $page, 'table' => 'executive_title_actions', 'foreign' => 'executive_title_id', 'statuses' => 'executive_title_status', 'files' => ['request_file' => 'executive_title_request_file', 'session_file' => 'session_1_file']],
            default => null,
        };
    }

    /** @return array{table:string,foreign:string}|null */
    private function caseStatementSpec(string $page): ?array
    {
        $foreign = match ($page) {
            'administrative_cases' => 'administrative_cases_id',
            'commercial_cases' => 'commercial_cases_id',
            'labor_cases' => 'labor_cases_id',
            'medical_cases' => 'medical_cases_id',
            'executive_title' => 'executive_title_id',
            default => null,
        };

        return $foreign === null ? null : ['table' => $page.'_statement_request', 'foreign' => $foreign];
    }
}
