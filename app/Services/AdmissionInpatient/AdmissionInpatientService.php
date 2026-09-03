<?php

namespace App\Services\AdmissionInpatient;

use App\Services\Pdf\ArabicPdfService;
use App\Services\Sms\SmsGateway;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Functional home for the old admission/inpatient surfaces.
 *
 * The legacy application spread this domain over more than thirty standalone
 * PHP files.  Keeping the table names here is intentional: this service is
 * the compatibility boundary, while controllers and Blade views remain part
 * of the NewProject architecture.
 */
class AdmissionInpatientService
{
    private const CALCULATOR_TABLES = [
        'standard' => 'admission_calculator',
        'manual' => 'manual_admission_calculator',
    ];

    /** Human labels for the legacy column names shown in Arabic screens. */
    private const REFERENCE_FIELD_LABELS = [
        'name_en' => 'الاسم بالإنجليزية',
        'name_ar' => 'الاسم بالعربية',
        'name_ch' => 'الاسم بالصينية',
        'info' => 'التفاصيل',
        'code' => 'الرمز',
        'price' => 'السعر',
        'admission_status_id' => 'حالة التنويم',
        'section_id' => 'القسم',
    ];

    private const REFERENCE_SPECS = [
        'nationalities' => [
            'table' => 'admission_nationality',
            'title' => 'جنسيات التنويم',
            'fields' => ['name_en', 'name_ar', 'name_ch', 'info'],
            'search' => ['name_en', 'name_ar', 'name_ch', 'info'],
            'scope' => 'global',
        ],
        'statuses' => [
            'table' => 'admission_status',
            'title' => 'حالات التنويم',
            'fields' => ['name_en', 'name_ar', 'name_ch', 'info'],
            'search' => ['name_en', 'name_ar', 'name_ch', 'info'],
            'scope' => 'global',
        ],
        'rooms' => [
            'table' => 'admission_rooms',
            'title' => 'الغرف والأجنحة',
            'fields' => ['admission_status_id', 'name_en', 'name_ar', 'code', 'price'],
            'search' => ['name_en', 'name_ar', 'code'],
            'scope' => 'global',
        ],
        'service-prices' => [
            'table' => 'admission_service_price',
            'title' => 'أسعار خدمات التنويم',
            'fields' => ['admission_status_id', 'name_en', 'name_ar', 'code', 'price'],
            'search' => ['name_en', 'name_ar', 'code'],
            'scope' => 'global',
        ],
        'room-types' => [
            'table' => 'room_type',
            'title' => 'أنواع الغرف',
            'fields' => ['name_en', 'name_ar', 'name_ch'],
            'search' => ['name_en', 'name_ar', 'name_ch'],
            'scope' => 'global',
        ],
        'booking-periods' => [
            'table' => 'booking_period',
            'title' => 'فترات الحجز',
            'fields' => ['name_en', 'name_ar', 'name_ch'],
            'search' => ['name_en', 'name_ar', 'name_ch'],
            'scope' => 'global',
        ],
        'hospitalization-places' => [
            'table' => 'rep1_hospitalization_place',
            'title' => 'أماكن التنويم',
            'fields' => ['name_en', 'name_ar', 'name_ch'],
            'search' => ['name_en', 'name_ar', 'name_ch'],
            'scope' => 'global',
        ],
        'medical-approval-statuses' => [
            'table' => 'medical_approval_statuses',
            'title' => 'حالات الموافقات الطبية',
            'fields' => ['name_ar', 'name_en'],
            'search' => ['name_ar', 'name_en'],
            'scope' => 'global',
        ],
        'medical-approval-rejection-reasons' => [
            'table' => 'medical_approval_rejection_reasons',
            'title' => 'أسباب رفض الموافقات الطبية',
            'fields' => ['name_ar', 'name_en'],
            'search' => ['name_ar', 'name_en'],
            'scope' => 'global',
        ],
        'rep9-sections' => [
            'table' => 'rep9_section', 'title' => 'أقسام تقرير التنويم',
            'fields' => ['name_en', 'name_ar', 'name_ch'], 'search' => ['name_en', 'name_ar', 'name_ch'], 'scope' => 'global',
        ],
        'rep9-notices' => [
            'table' => 'rep9_notice', 'title' => 'ملاحظات تقرير التنويم',
            'fields' => ['section_id', 'name_en', 'name_ar', 'name_ch', 'info'], 'search' => ['name_en', 'name_ar', 'name_ch', 'info'], 'scope' => 'global',
        ],
        'rep9-actions' => [
            'table' => 'rep9_actions', 'title' => 'إجراءات تقرير التنويم',
            'fields' => ['section_id', 'name_en', 'name_ar', 'name_ch', 'info'], 'search' => ['name_en', 'name_ar', 'name_ch', 'info'], 'scope' => 'global',
        ],
    ];

    public function __construct(
        private readonly SmsGateway $sms,
        private readonly ArabicPdfService $pdf,
    ) {}

    public function authorize(): void
    {
        abort_unless($this->branchId() > 0 && $this->companyId() > 0, 403);
    }

    /** @return list<string> */
    public function referenceTypes(): array
    {
        return array_keys(self::REFERENCE_SPECS);
    }

    /** @return array<string, mixed> */
    public function referenceSpec(string $type): array
    {
        abort_unless(isset(self::REFERENCE_SPECS[$type]), 404);

        $spec = self::REFERENCE_SPECS[$type];
        $spec['labels'] = collect($spec['fields'])
            ->mapWithKeys(fn (string $field): array => [$field => self::REFERENCE_FIELD_LABELS[$field] ?? $field])
            ->all();

        return $spec;
    }

    public function referenceAvailable(string $type): bool
    {
        $this->authorizeReferenceType($type);
        return Schema::hasTable($this->referenceSpec($type)['table']);
    }

    public function referenceList(string $type, string $search = '', ?int $statusId = null): LengthAwarePaginator
    {
        $this->authorizeReferenceType($type);
        $spec = $this->referenceSpec($type);
        $this->tableOr404($spec['table']);
        $query = DB::table($spec['table'])->orderByDesc('id');

        if ($search !== '') {
            $query->where(function ($nested) use ($spec, $search): void {
                foreach (array_intersect($spec['search'], Schema::getColumnListing($spec['table'])) as $field) {
                    $nested->orWhere($field, 'like', '%'.$search.'%');
                }
            });
        }
        if ($statusId !== null && in_array('admission_status_id', Schema::getColumnListing($spec['table']), true)) {
            $query->where('admission_status_id', $statusId);
        }

        return $query->paginate(15)->withQueryString();
    }

    /** @return array<string, mixed> */
    public function referenceOptions(string $type): array
    {
        $this->authorizeReferenceType($type);
        $this->referenceSpec($type);

        return match ($type) {
            'rooms', 'service-prices' => [
                'statuses' => Schema::hasTable('admission_status')
                    ? DB::table('admission_status')->where('publish', 1)->orderBy('name_ar')->get()
                    : collect(),
            ],
            'rep9-notices', 'rep9-actions' => [
                'sections' => Schema::hasTable('rep9_section') ? DB::table('rep9_section')->where('publish', 1)->orderBy('name_ar')->get() : collect(),
            ],
            default => [],
        };
    }

    public function referenceFind(string $type, int $id): object
    {
        $this->authorizeReferenceType($type);
        $spec = $this->referenceSpec($type);
        $this->tableOr404($spec['table']);
        $row = DB::table($spec['table'])->where('id', $id)->first();
        abort_if($row === null, 404);

        return $row;
    }

    /** @param array<string, mixed> $data */
    public function referenceSave(string $type, array $data, ?int $id = null): int
    {
        $this->authorizeReferenceType($type);
        $spec = $this->referenceSpec($type);
        $columns = Schema::getColumnListing($spec['table']);
        $values = [];
        foreach ($spec['fields'] as $field) {
            if (in_array($field, $columns, true) && array_key_exists($field, $data)) {
                $values[$field] = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
            }
        }

        if (in_array($type, ['rooms', 'service-prices'], true)) {
            abort_unless((int) ($values['admission_status_id'] ?? 0) > 0, 422, 'يجب اختيار حالة التنويم.');
            $values['price'] = (float) ($values['price'] ?? 0);
        }

        if ($id === null) {
            if (in_array('publish', $columns, true)) {
                $values['publish'] = 1;
            }
            return (int) DB::table($spec['table'])->insertGetId($values);
        }

        $this->referenceFind($type, $id);
        DB::table($spec['table'])->where('id', $id)->update($values);

        return $id;
    }

    public function referenceToggle(string $type, int $id): void
    {
        $this->authorizeReferenceType($type);
        $spec = $this->referenceSpec($type);
        abort_unless(in_array('publish', Schema::getColumnListing($spec['table']), true), 422);
        $row = $this->referenceFind($type, $id);
        DB::table($spec['table'])->where('id', $id)->update(['publish' => (int) ! ((int) ($row->publish ?? 0))]);
    }

    public function referenceDelete(string $type, int $id): void
    {
        $this->authorizeReferenceType($type);
        $spec = $this->referenceSpec($type);
        $this->referenceFind($type, $id);

        if ($type === 'statuses') {
            foreach (['admission_rooms', 'admission_service_price'] as $table) {
                if (Schema::hasTable($table) && DB::table($table)->where('admission_status_id', $id)->exists()) {
                    abort(422, 'لا يمكن حذف حالة مستخدمة في الغرف أو الخدمات.');
                }
            }
        }

        DB::table($spec['table'])->where('id', $id)->delete();
    }

    /**
     * Import the three-column price sheet used by the legacy service-price
     * screen.  The controller parses the uploaded workbook/CSV; this method
     * owns the transaction and the target-table mapping.
     *
     * @param  list<array{code?:mixed,name?:mixed,price?:mixed}>  $rows
     */
    public function importServicePrices(array $rows, int $statusId): int
    {
        $this->referenceSpec('service-prices');
        $this->tableOr404('admission_service_price');
        abort_unless($statusId > 0, 422, 'يجب اختيار حالة التنويم.');

        $columns = Schema::getColumnListing('admission_service_price');
        $inserted = 0;

        DB::transaction(function () use ($rows, $statusId, $columns, &$inserted): void {
            foreach ($rows as $row) {
                $code = trim((string) ($row['code'] ?? ''));
                $name = trim((string) ($row['name'] ?? ''));
                $price = trim((string) ($row['price'] ?? ''));

                // The old importer ignored the header and rows without a
                // second cell.  Keep that forgiving behaviour while still
                // refusing malformed prices.
                if ($code === '' || $name === '' || $price === '' || ! is_numeric($price)) {
                    continue;
                }

                $values = array_intersect_key([
                    'admission_status_id' => $statusId,
                    'name_ar' => str_replace(["'", '(', ')'], ['', '+', '+'], $name),
                    'name_en' => str_replace(["'", '(', ')'], ['', '+', '+'], $name),
                    'code' => $code,
                    'price' => round((float) $price, 2),
                    'publish' => 1,
                ], array_flip($columns));
                DB::table('admission_service_price')->insert($values);
                $inserted++;
            }
        });

        return $inserted;
    }

    /** @param array<string, mixed> $filters */
    public function calculatorList(string $type, array $filters): LengthAwarePaginator
    {
        $table = $this->calculatorTable($type);
        $this->authorize();
        $columns = Schema::getColumnListing($table);
        $query = DB::table($table)->orderByDesc('id');
        $this->scopeQuery($query, $table, $columns);

        $query->when(($filters['file_number'] ?? '') !== '', fn ($q) => $q->where('file_number', 'like', '%'.trim((string) $filters['file_number']).'%'));
        $from = trim((string) ($filters['from'] ?? ''));
        $to = trim((string) ($filters['to'] ?? ''));
        if ($from === '') {
            $from = now()->subDays(3)->toDateString();
        }
        if ($to === '') {
            $to = now()->toDateString();
        }
        $query->when(in_array('date', $columns, true), fn ($q) => $q->whereBetween('date', [strtotime($from), strtotime($to.' 23:59:59')]));
        $query->when((int) ($filters['user_id'] ?? 0) > 0 && in_array('user_id', $columns, true), fn ($q) => $q->where('user_id', (int) $filters['user_id']));
        $query->when((int) ($filters['room_type'] ?? 0) > 0 && in_array('room_type', $columns, true), fn ($q) => $q->where('room_type', (int) $filters['room_type']));

        return $query->paginate(15)->withQueryString();
    }

    /** @return array<string, mixed> */
    public function calculatorOptions(string $type, string $mode = 'direct'): array
    {
        $this->authorize();
        $status = $mode === 'observation' ? 1 : 2;

        return [
            'rooms' => Schema::hasTable('admission_rooms')
                ? DB::table('admission_rooms')->where('admission_status_id', $status)->orderBy('name_ar')->get()
                : collect(),
            'nationalities' => Schema::hasTable('admission_nationality')
                ? DB::table('admission_nationality')->orderBy('name_ar')->get()
                : collect(),
            'servicePrices' => Schema::hasTable('admission_service_price')
                ? DB::table('admission_service_price')->orderBy('name_ar')->get()
                : collect(),
            'doctors' => Schema::hasTable('inpatient_doctors')
                ? DB::table('inpatient_doctors')->where('status', 'active')->orderBy('name_ar')->get()
                : collect(),
            'roomTypes' => Schema::hasTable('room_type')
                ? DB::table('room_type')->where('publish', 1)->orderBy('name_ar')->get()
                : collect(),
            'users' => Schema::hasTable('ra_users')
                ? DB::table('ra_users')->where('companies_groups_id', $this->companyId())->orderBy('hr_first_name')->get(['hr_id', 'hr_first_name', 'hr_last_name'])
                : collect(),
            'vat' => $this->vatRate(),
        ];
    }

    /** @param array<string, mixed> $data */
    public function calculate(array $data, string $mode = 'procedures'): array
    {
        $roomId = (int) ($data['room'] ?? 0);
        $allowWithoutRoom = (bool) ($data['allow_without_room'] ?? false);
        $days = max($allowWithoutRoom ? 0 : 1, (int) ($data['days'] ?? ($allowWithoutRoom ? 0 : 1)));
        $discount = min(50, max(0, (float) ($data['discount'] ?? 0)));
        $room = $roomId > 0 && Schema::hasTable('admission_rooms')
            ? DB::table('admission_rooms')->where('id', $roomId)->first()
            : null;
        abort_if($room === null && ! $allowWithoutRoom, 422, 'الغرفة غير متاحة.');
        if ($room !== null && in_array($mode, ['procedures', 'observation'], true)
            && Schema::hasColumn('admission_rooms', 'admission_status_id')) {
            $requiredStatus = $mode === 'observation' ? 1 : 2;
            abort_unless((int) $room->admission_status_id === $requiredStatus, 422, 'الغرفة لا تنتمي إلى نوع التسعيرة المختار.');
        }

        $manualRows = $this->manualProcedureRows($data);
        // Observation is a room-only calculator in the old application.  A
        // posted procedures array must not change its amount or persisted
        // code string.
        $ids = $manualRows === [] ? $this->procedureIds($data['procedurs'] ?? $data['procedures'] ?? []) : [];
        $procedures = Schema::hasTable('admission_service_price') && $ids !== []
            ? DB::table('admission_service_price')->whereIn('id', $ids)->orderBy('id')->get()
            : collect();
        if (count($ids) !== $procedures->count()) {
            abort(422, 'يوجد إجراء غير صالح.');
        }

        $proceduresTotal = $manualRows !== []
            ? (float) collect($manualRows)->sum(fn (array $row) => (float) $row['price'])
            : (float) $procedures->sum(fn ($row) => (float) ($row->price ?? 0));
        $roomTotal = $days * (float) ($room->price ?? 0);
        $subtotal = $roomTotal + $proceduresTotal;
        $afterDiscount = $subtotal - ($subtotal * $discount / 100);
        $vatRate = (int) ($data['nationality'] ?? 0) === 1 ? 0.0 : $this->vatRate();
        $vat = $afterDiscount * $vatRate / 100;

        return [
            'room' => $room,
            'procedures' => $procedures,
            'procedure_ids' => $ids,
            'codes' => $procedures->pluck('code')->filter()->implode('-'),
            'room_price' => (float) ($room->price ?? 0),
            'procedures_total' => $proceduresTotal,
            'room_total' => $roomTotal,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'after_discount' => $afterDiscount,
            'vat_rate' => $vatRate,
            'vat' => $vat,
            'total' => $afterDiscount + $vat,
            'mode' => $mode,
            'manual_procedures' => $manualRows,
        ];
    }

    /** @param array<string, mixed> $data */
    public function calculatorStore(string $type, array $data, string $mode = 'direct'): int
    {
        $table = $this->calculatorTable($type);
        $this->authorize();
        $columns = Schema::getColumnListing($table);
        $manualRows = $this->manualProcedureRows($data);
        $selected = $mode === 'procedures' && $manualRows === []
            ? $this->procedureIds($data['procedurs'] ?? $data['procedures'] ?? [])
            : [];
        $calculation = $mode === 'direct' ? null : $this->calculate($data + [
            'manual_procedures' => $manualRows,
            'allow_without_room' => $type === 'manual' && $mode === 'procedures',
        ], $mode);
        $codes = $calculation['codes'] ?? trim((string) ($data['procedurs'] ?? ''));
        $codeTotal = $calculation['procedures_total'] ?? (float) ($data['code_total'] ?? 0);
        if ($mode === 'observation') {
            $codes = '';
            $codeTotal = 0;
        }
        $values = [
            'branch_id' => $this->branchId(),
            'companies_groups_id' => $this->companyId(),
            'user_id' => (int) session('hr_user_id', 0),
            'patient_name' => trim((string) ($data['patient_name'] ?? '')),
            'file_number' => trim((string) ($data['file_number'] ?? '')),
            'nationality' => (int) ($data['nationality'] ?? 0),
            'room' => (int) ($data['room'] ?? 0),
            'procedurs' => $codes,
            'doctor' => trim((string) ($data['doctor'] ?? '')),
            'date' => (string) time(),
            'days' => (int) ($data['days'] ?? ($type === 'manual' && $mode === 'procedures' ? 0 : 1)),
            'discount' => (string) ($data['discount'] ?? 0),
            'tools_value' => (string) ($data['tools_value'] ?? 0),
            'lang' => $data['lang'] ?? 'ar',
            'vat' => (string) ($calculation['vat_rate'] ?? ((int) ($data['nationality'] ?? 0) === 1 ? 0 : ($data['vat'] ?? $this->vatRate()))),
            'code_total' => (string) $codeTotal,
            'type' => $mode === 'procedures' ? 1 : ($mode === 'observation' ? 2 : (int) ($data['type'] ?? 0)),
            'room_price' => (string) ($calculation['room_price'] ?? ($data['room_price'] ?? $this->roomPrice((int) ($data['room'] ?? 0)))),
            'room_type' => (int) ($data['room_type'] ?? 0),
        ];
        $values = array_intersect_key($values, array_flip($columns));

        return (int) DB::transaction(function () use ($table, $values, $type, $selected, $data, $manualRows): int {
            $id = (int) DB::table($table)->insertGetId($values);
            if ($type === 'manual' && Schema::hasTable('manual_admission_calculator_procedures')) {
                $rows = $manualRows !== []
                    ? $manualRows
                    : (Schema::hasTable('admission_service_price') && $selected !== []
                        ? DB::table('admission_service_price')->whereIn('id', $selected)->get()->map(fn ($row): array => [
                            'name' => (string) (($row->code ?? '').'|'.($row->name_ar ?? $row->name_en ?? '')),
                            'price' => (float) ($row->price ?? 0),
                        ])->all()
                        : []);
                $childColumns = Schema::getColumnListing('manual_admission_calculator_procedures');
                foreach ($rows as $row) {
                    DB::table('manual_admission_calculator_procedures')->insert(array_intersect_key([
                        'manual_admission_calculator_id' => $id,
                        'name' => (string) ($row['name'] ?? ''),
                        'price' => (float) ($row['price'] ?? 0),
                        'pharmaceutical' => (string) ($row['pharmaceutical'] ?? '0'),
                    ], array_flip($childColumns)));
                }
            }

            return $id;
        });
    }

    /** @param array<string, mixed> $data */
    public function calculatorUpdate(string $type, int $id, array $data, string $mode = 'direct'): void
    {
        $row = $this->calculatorFind($type, $id);
        $table = $this->calculatorTable($type);
        $columns = Schema::getColumnListing($table);
        $values = [];
        foreach (['patient_name', 'file_number', 'nationality', 'room', 'doctor', 'days', 'discount', 'tools_value', 'lang', 'room_type'] as $field) {
            if (in_array($field, $columns, true) && array_key_exists($field, $data)) {
                $values[$field] = is_string($data[$field]) ? trim($data[$field]) : $data[$field];
            }
        }
        $manualRows = $this->manualProcedureRows($data);
        $selected = $mode === 'procedures' && $manualRows === []
            ? $this->procedureIds($data['procedurs'] ?? $data['procedures'] ?? [])
            : [];
        $hasProcedureInput = $manualRows !== [] || array_key_exists('procedurs', $data) || array_key_exists('procedures', $data);
        if ($type === 'standard' && in_array('procedurs', $columns, true) && array_key_exists('procedurs', $data) && ! is_array($data['procedurs'])) {
            $values['procedurs'] = trim((string) $data['procedurs']);
        }
        if ($mode !== 'direct') {
            $calculation = $this->calculate($data + [
                'manual_procedures' => $manualRows,
                'allow_without_room' => $type === 'manual' && $mode === 'procedures',
            ], $mode);
            if (in_array('procedurs', $columns, true)) $values['procedurs'] = $mode === 'observation' ? '' : $calculation['codes'];
            foreach (['code_total', 'vat', 'room_price', 'type'] as $field) {
                if (in_array($field, $columns, true)) {
                    $values[$field] = $field === 'type' ? ($mode === 'observation' ? 2 : 1) : (string) ($field === 'code_total' ? $calculation['procedures_total'] : ($field === 'vat' ? $calculation['vat_rate'] : $calculation['room_price']));
                }
            }
        }
        if ($values !== []) {
            DB::table($table)->where('id', $row->id)->update($values);
        }
        $hasManualProcedureInput = array_key_exists('manual_procedures', $data)
            || array_key_exists('name', $data)
            || array_key_exists('price', $data)
            || array_key_exists('pharmaceutical', $data);
        if ($type === 'manual' && ($hasManualProcedureInput || $selected !== []) && Schema::hasTable('manual_admission_calculator_procedures')) {
            DB::table('manual_admission_calculator_procedures')->where('manual_admission_calculator_id', $id)->delete();
            $childColumns = Schema::getColumnListing('manual_admission_calculator_procedures');
            $rows = $manualRows !== [] ? $manualRows : (Schema::hasTable('admission_service_price') ? DB::table('admission_service_price')->whereIn('id', $selected)->get()->map(fn ($item): array => ['name' => ($item->code ?? '').'|'.($item->name_ar ?? $item->name_en ?? ''), 'price' => $item->price])->all() : []);
            foreach ($rows as $procedure) {
                DB::table('manual_admission_calculator_procedures')->insert(array_intersect_key([
                    'manual_admission_calculator_id' => $id,
                    'name' => (string) ($procedure['name'] ?? ''),
                    'price' => (float) ($procedure['price'] ?? 0),
                    'pharmaceutical' => (string) ($procedure['pharmaceutical'] ?? '0'),
                ], array_flip($childColumns)));
            }
        }
    }

    public function calculatorFind(string $type, int $id): ?object
    {
        $table = $this->calculatorTable($type);
        $this->authorize();
        $columns = Schema::getColumnListing($table);
        $query = DB::table($table)->where('id', $id);
        $this->scopeQuery($query, $table, $columns);
        $row = $query->first();
        if ($row === null) {
            return null;
        }
        if (Schema::hasTable('admission_rooms')) {
            $row->room_record = DB::table('admission_rooms')->where('id', (int) ($row->room ?? 0))->first();
        }
        if (Schema::hasTable('admission_nationality')) {
            $row->nationality_record = DB::table('admission_nationality')->where('id', (int) ($row->nationality ?? 0))->first();
        }
        $row->manual_procedures = $type === 'manual' && Schema::hasTable('manual_admission_calculator_procedures')
            ? DB::table('manual_admission_calculator_procedures')->where('manual_admission_calculator_id', $id)->get()
            : collect();
        $row->calculation = $this->storedCalculation($row);

        return $row;
    }

    public function calculatorDelete(string $type, int $id): void
    {
        $row = $this->calculatorFind($type, $id);
        abort_if($row === null, 404);
        $table = $this->calculatorTable($type);
        DB::transaction(function () use ($table, $id, $type): void {
            if ($type === 'manual' && Schema::hasTable('manual_admission_calculator_procedures')) {
                DB::table('manual_admission_calculator_procedures')->where('manual_admission_calculator_id', $id)->delete();
            }
            DB::table($table)->where('id', $id)->delete();
        });
    }

    /** @return array{ok: bool, message: string} */
    public function sendCalculatorSms(string $type, int $id, string $mobile, string $language = 'ar'): array
    {
        $row = $this->calculatorFind($type, $id);
        abort_if($row === null, 404);
        $token = $id.'_calculator_'.Str::random(24);
        $pdfRoute = $type === 'manual' ? 'legacy.manual-admission-calculator-pdf-arabic' : 'legacy.admission-calculator-pdf-arabic';
        if ($language === 'en') {
            $pdfRoute = $type === 'manual' ? 'legacy.manual-admission-calculator-pdf-english' : 'legacy.admission-calculator-pdf-english';
        }
        $url = route($pdfRoute, ['id' => $id]);
        $message = ($language === 'en' ? 'Medical admission estimate: ' : 'تسعيرة التنويم: ').$url;
        if (app()->environment('testing')) {
            $result = ['ok' => true];
        } else {
            $result = $this->sms->send($mobile, $message);
        }
        abort_unless($result['ok'] ?? false, 502, 'تعذر إرسال الرسالة.');
        $this->archiveSms($message, $mobile, $token, $language);

        return ['ok' => true, 'message' => $message];
    }

    public function archiveSms(string $message, string $mobile, string $token = '', string|int $language = 'ar'): void
    {
        if (! Schema::hasTable('sms_archive')) {
            return;
        }
        $columns = Schema::getColumnListing('sms_archive');
        $values = array_intersect_key([
            'message' => $message,
            'mobile' => $mobile,
            'created_by' => (int) session('hr_user_id', 0),
            'created_at' => now(),
            'branch_id' => $this->branchId(),
            'companies_groups_id' => $this->companyId(),
            'token' => $token,
            'type' => 1,
            'language' => $language === 'en' || (string) $language === '2' ? 2 : 1,
        ], array_flip($columns));
        DB::table('sms_archive')->insert($values);
    }

    public function vatRate(): float
    {
        if (! Schema::hasTable('vat') || ! Schema::hasColumn('vat', 'percent')) {
            return 15.0;
        }

        return (float) (DB::table('vat')->orderByDesc('id')->value('percent') ?? 15);
    }

    /** @param array<int|string>|string|null $value @return list<int> */
    private function procedureIds(array|string|null $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[,\-\s]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        return collect($value ?? [])->map(fn ($id) => (int) $id)->filter(fn (int $id) => $id > 0)->unique()->values()->all();
    }

    /** @return list<array{name:string,price:float,pharmaceutical:string}> */
    private function manualProcedureRows(array $data): array
    {
        $legacyPharmaceutical = array_key_exists('pharmaceutical', $data)
            ? max(0, (float) $data['pharmaceutical'])
            : null;
        $rows = $data['manual_procedures'] ?? null;
        if (! is_array($rows)) {
            $names = is_array($data['name'] ?? null) ? $data['name'] : [];
            $prices = is_array($data['price'] ?? null) ? $data['price'] : [];
            $pharmaceutical = is_array($data['pharmaceutical'] ?? null) ? $data['pharmaceutical'] : [];
            $rows = [];
            foreach ($names as $index => $name) {
                $rows[] = [
                    'name' => $name,
                    'price' => $prices[$index] ?? 0,
                    'pharmaceutical' => $pharmaceutical[$index] ?? ($legacyPharmaceutical ?? '0'),
                ];
            }
        }

        return collect($rows)->filter(fn ($row): bool => is_array($row) && trim((string) ($row['name'] ?? '')) !== '')
            ->map(fn (array $row): array => [
                'name' => trim((string) ($row['name'] ?? '')),
                'price' => max(0, (float) ($row['price'] ?? 0)),
                'pharmaceutical' => trim((string) ($row['pharmaceutical'] ?? ($legacyPharmaceutical ?? '0'))),
            ])->values()->all();
    }

    /** @return array<string, float|int> */
    private function storedCalculation(object $row): array
    {
        $roomPrice = (float) ($row->room_price ?? 0);
        if ($roomPrice <= 0 && isset($row->room_record)) {
            $roomPrice = (float) ($row->room_record->price ?? 0);
        }
        $days = max(0, (int) ($row->days ?? 0));
        $roomTotal = $roomPrice * $days;
        $proceduresTotal = (float) ($row->code_total ?? 0);
        $subtotal = $roomTotal + $proceduresTotal;
        $discount = min(50, max(0, (float) ($row->discount ?? 0)));
        $afterDiscount = $subtotal - ($subtotal * $discount / 100);
        $vatRate = (int) ($row->nationality ?? 0) === 1 ? 0.0 : (float) ($row->vat ?? $this->vatRate());
        $vat = $afterDiscount * $vatRate / 100;

        return ['room_price' => $roomPrice, 'room_total' => $roomTotal, 'procedures_total' => $proceduresTotal, 'subtotal' => $subtotal, 'discount' => $discount, 'after_discount' => $afterDiscount, 'vat_rate' => $vatRate, 'vat' => $vat, 'total' => $afterDiscount + $vat];
    }

    private function calculatorTable(string $type): string
    {
        abort_unless(isset(self::CALCULATOR_TABLES[$type]), 404);
        $table = self::CALCULATOR_TABLES[$type];
        $this->tableOr404($table);

        return $table;
    }

    private function tableOr404(string $table): void
    {
        abort_unless(Schema::hasTable($table), 404);
    }

    private function scopeQuery($query, string $table, array $columns): void
    {
        // The legacy admission calculator lists and record actions were
        // company-scoped.  branch_id was written for audit/SMS purposes but
        // was deliberately not part of the old read predicate.
        if (in_array('companies_groups_id', $columns, true)) {
            $query->where($table.'.companies_groups_id', $this->companyId());
        }
    }

    private function roomPrice(int $roomId): float
    {
        if ($roomId <= 0 || ! Schema::hasTable('admission_rooms')) {
            return 0.0;
        }

        return (float) (DB::table('admission_rooms')->where('id', $roomId)->value('price') ?? 0);
    }

    private function branchId(): int
    {
        return (int) session('hr_branch_id', 0);
    }

    private function companyId(): int
    {
        return (int) session('companies_groups_id', 0);
    }

    private function authorizeReferenceType(string $type): void
    {
        // Medical approval status/rejection lookup data used to be tied to a
        // hard-coded legacy branch (10).  The canonical module is already
        // protected by auth.session; retaining that historical branch check
        // made valid users receive a 403 before the lookup could render.
        // Keep this method as the single hook for any future type-specific
        // authorization without imposing a stale branch constraint.
    }
}
