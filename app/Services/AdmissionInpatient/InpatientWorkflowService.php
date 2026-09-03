<?php

namespace App\Services\AdmissionInpatient;

use App\Services\Auth\PermissionService;
use App\Services\Sms\SmsGateway;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Workflows which used to live beside the calculator in the legacy project. */
class InpatientWorkflowService
{
    private const CONSENT_BRANCHES = [1, 9, 34];
    private const MEDICAL_APPROVAL_BRANCH = 10;

    public function __construct(
        private readonly SmsGateway $sms,
        private readonly PermissionService $permissions,
    ) {}

    public function authorize(): void
    {
        abort_unless($this->branchId() > 0 && $this->companyId() > 0, 403);
    }

    public function authorizeReport9(): void
    {
        $this->authorize();
        // Reports are stored in the fixed inpatient-report branch (9), but
        // the operator's active branch is not an authorization boundary.
        // The old comparison rejected authenticated staff from every other
        // branch with a 403 even though they were using the canonical module.
    }

    /** @param array<string, mixed> $filters */
    public function consentList(array $filters = []): LengthAwarePaginator
    {
        $this->authorizeConsent();
        $this->tableOr404('hospital_admission_consent');
        $query = DB::table('hospital_admission_consent')->orderByDesc('id');
        $this->scopeConsent($query);
        if (Schema::hasColumn('hospital_admission_consent', 'publish')) {
            $query->where('publish', 1);
        }
        $query->when(($filters['search'] ?? '') !== '', function ($q) use ($filters): void {
            $search = trim((string) $filters['search']);
            $q->where(function ($nested) use ($search): void {
                foreach (['patient_name_ar', 'patient_name_en', 'patient_idno', 'patient_file_number', 'contractor_name_ar', 'contractor_name_en', 'contractor_idno', 'reference_number'] as $column) {
                    if (Schema::hasColumn('hospital_admission_consent', $column)) {
                        $nested->orWhere($column, 'like', '%'.$search.'%');
                    }
                }
            });
        });
        $query->when(($filters['from'] ?? '') !== '', fn ($q) => $q->where('created_at', '>=', $filters['from'].' 00:00:00'));
        $query->when(($filters['to'] ?? '') !== '', fn ($q) => $q->where('created_at', '<=', $filters['to'].' 23:59:59'));
        $query->when(($filters['duty_status'] ?? '') !== '' && Schema::hasColumn('hospital_admission_consent', 'duty_manager_approval_status'), fn ($q) => $q->where('duty_manager_approval_status', $filters['duty_status']));
        $query->when(($filters['contract_status'] ?? '') !== '' && Schema::hasColumn('hospital_admission_consent', 'contract_approval_status'), fn ($q) => $q->where('contract_approval_status', $filters['contract_status']));
        $query->when(($filters['language'] ?? '') !== '' && Schema::hasColumn('hospital_admission_consent', 'language'), fn ($q) => $q->where('language', (int) $filters['language']));
        $query->when((int) ($filters['user_id'] ?? 0) > 0 && Schema::hasColumn('hospital_admission_consent', 'user_id'), fn ($q) => $q->where('user_id', (int) $filters['user_id']));
        $query->when(($filters['patient_idno'] ?? '') !== '' && Schema::hasColumn('hospital_admission_consent', 'patient_idno'), function ($q) use ($filters): void {
            $idno = trim((string) $filters['patient_idno']);
            $q->where(function ($nested) use ($idno): void {
                $nested->where('patient_idno', 'like', '%'.$idno.'%');
                if (Schema::hasColumn('hospital_admission_consent', 'contractor_idno')) {
                    $nested->orWhere('contractor_idno', 'like', '%'.$idno.'%');
                }
            });
        });

        return $query->paginate(15)->withQueryString();
    }

    /** @return array<string, mixed> */
    public function consentOptions(): array
    {
        $this->authorizeConsent();

        return [
            'templates' => Schema::hasTable('hospital_admission_consent_template')
                ? DB::table('hospital_admission_consent_template')->where('publish', 1)->orderByDesc('id')->get()
                : collect(),
            'countries' => $this->firstExistingRows(['country_yakeen', 'country'], ['DESCRIPTION', 'name_ar', 'name_en']),
            'idTypes' => Schema::hasTable('idtype') ? DB::table('idtype')->orderBy('id')->get() : collect(),
            'relatives' => Schema::hasTable('relatives') ? DB::table('relatives')->orderBy('id')->get() : collect(),
            'managers' => Schema::hasTable('ra_users')
                ? DB::table('ra_users')->where('branch_id', 1)->where('companies_groups_id', $this->companyId())->where('activated', 1)->where('isSearchedField', 1)->orderBy('hr_first_name')->get()
                : collect(),
            'users' => Schema::hasTable('ra_users')
                ? DB::table('ra_users')
                    ->where('companies_groups_id', $this->companyId())
                    ->when(Schema::hasColumn('ra_users', 'branch_id'), fn ($q) => $q->where('branch_id', $this->branchId()))
                    ->when(Schema::hasColumn('ra_users', 'hr_user_level'), fn ($q) => $q->where('hr_user_level', 1))
                    ->when(Schema::hasColumn('ra_users', 'isSearchedField'), fn ($q) => $q->where('isSearchedField', 1))
                    ->orderBy('hr_first_name')->get(['hr_id', 'hr_first_name', 'hr_last_name'])
                : collect(),
        ];
    }

    public function consentFind(int $id): ?object
    {
        $this->authorizeConsent();
        $this->tableOr404('hospital_admission_consent');
        $query = DB::table('hospital_admission_consent')->where('id', $id);
        $this->scopeConsent($query);

        return $query->first();
    }

    /** @param array<string, mixed> $data */
    public function consentSave(array $data, ?int $id = null): int
    {
        $this->authorizeConsent();
        $this->tableOr404('hospital_admission_consent');
        $columns = Schema::getColumnListing('hospital_admission_consent');
        $patientIsContractor = (int) ($data['contractor_type'] ?? 1) === 2;
        $patientAr = trim((string) ($data['patient_name_ar'] ?? ''));
        $patientEn = trim((string) ($data['patient_name_en'] ?? ''));
        $values = [
            'branch_id' => $this->branchId(),
            'companies_groups_id' => $this->companyId(),
            'user_id' => (int) session('hr_user_id', 0),
            'patient_name_ar' => $patientAr ?: null,
            'patient_name_en' => $patientEn ?: null,
            'patient_idno' => trim((string) ($data['patient_idno'] ?? '')),
            'patient_file_number' => trim((string) ($data['patient_file_number'] ?? '')),
            'patient_nationality' => (int) ($data['patient_nationality'] ?? 0),
            'contractor_name_ar' => $patientIsContractor ? ($patientAr ?: null) : (trim((string) ($data['contractor_name_ar'] ?? '')) ?: null),
            'contractor_name_en' => $patientIsContractor ? ($patientEn ?: null) : (trim((string) ($data['contractor_name_en'] ?? '')) ?: null),
            'contractor_idno' => $patientIsContractor ? trim((string) ($data['patient_idno'] ?? '')) : trim((string) ($data['contractor_idno'] ?? '')),
            'contractor_mobile' => trim((string) ($data['contractor_mobile'] ?? '')),
            'contractor_nationality' => $patientIsContractor ? (int) ($data['patient_nationality'] ?? 0) : (int) ($data['contractor_nationality'] ?? 0),
            'created_at' => now(),
            'status' => 0,
            'type' => 1,
            'payment_type' => 0,
            'deserved_amount' => '0',
            'paid_amount' => '0',
            'relative' => $patientIsContractor ? '0' : (string) ($data['relative'] ?? '0'),
            'reference_number' => trim((string) ($data['reference_number'] ?? Str::uuid())),
            'language' => (int) ($data['language'] ?? 1),
            'contractor_type' => (int) ($data['contractor_type'] ?? 1),
            'date_type' => (int) ($data['date_type'] ?? 0),
            'birth_day' => (int) ($data['birth_day'] ?? 0),
            'birth_month' => (int) ($data['birth_month'] ?? 0),
            'birth_year' => (int) ($data['birth_year'] ?? 0),
            'publish' => 1,
            'pateintIDType' => (int) ($data['patient_id_type'] ?? 0),
            'contractorIDType' => $patientIsContractor ? (int) ($data['patient_id_type'] ?? 0) : (int) ($data['contractor_id_type'] ?? 0),
            'sexCode' => (int) ($data['sex_code'] ?? 0),
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
            'title' => trim((string) ($data['consent_title'] ?? $data['title'] ?? '')) ?: null,
            'consent_content' => trim((string) ($data['consent_content'] ?? '')) ?: null,
            'payment_type' => (int) ($data['payment_type'] ?? 0),
            'deserved_amount' => (string) ($data['deserved_amount'] ?? '0'),
            'paid_amount' => (string) ($data['paid_amount'] ?? '0'),
        ];
        if ($id === null && in_array('token', $columns, true)) {
            $values['token'] = Str::random(48);
        }
        $values = array_intersect_key($values, array_flip($columns));

        if ($id === null) {
            $newId = (int) DB::table('hospital_admission_consent')->insertGetId($values);
            $this->notifyDutyManagers($data['duty_managers_ids'] ?? []);
            return $newId;
        }

        $existing = $this->consentFind($id);
        abort_if($existing === null, 404);
        unset($values['branch_id'], $values['companies_groups_id'], $values['user_id'], $values['created_at'], $values['token']);
        DB::table('hospital_admission_consent')->where('id', $id)->update($values);

        return $id;
    }

    public function consentDelete(int $id): void
    {
        $row = $this->consentFind($id);
        abort_if($row === null, 404);
        DB::table('hospital_admission_consent')->where('id', $id)->delete();
    }

    public function consentToggle(int $id): void
    {
        $row = $this->consentFind($id);
        abort_if($row === null, 404);
        abort_unless(Schema::hasColumn('hospital_admission_consent', 'publish'), 422);
        DB::table('hospital_admission_consent')->where('id', $id)->update([
            'publish' => (int) ! ((int) ($row->publish ?? 0)),
        ]);
    }

    /**
     * The legacy list exposed this as hospital_admission_consent.php?do=timeline.
     * Unlike the company-wide list, the old timeline was branch-scoped.
     *
     * @return array{row: object, items: list<array<string, string>>}
     */
    public function consentTimeline(int $id): array
    {
        $this->authorizeConsent();
        $this->tableOr404('hospital_admission_consent');
        $row = DB::table('hospital_admission_consent')
            ->where('id', $id)
            ->where('branch_id', $this->branchId())
            ->where('companies_groups_id', $this->companyId())
            ->first();
        abort_if($row === null, 404);

        $creator = Schema::hasTable('ra_users')
            ? DB::table('ra_users')->where('hr_id', (int) ($row->user_id ?? 0))->first()
            : null;
        $dutyManager = Schema::hasTable('ra_users')
            ? DB::table('ra_users')->where('hr_id', (int) ($row->duty_manager_id ?? 0))->first()
            : null;
        $creatorName = trim((string) ($creator->hr_first_name ?? '').' '.(string) ($creator->hr_last_name ?? ''));
        $dutyName = trim((string) ($dutyManager->hr_first_name ?? '').' '.(string) ($dutyManager->hr_last_name ?? ''));

        $items = [[
            'date' => (string) ($row->created_at ?? ''),
            'title' => 'إنشاء الإقرار',
            'status' => 'completed',
            'body' => 'تم إنشاء إقرار التنويم بواسطة: '.($creatorName !== '' ? $creatorName : (string) ($row->user_id ?? '')),
        ]];
        $dutyStatus = (string) ($row->duty_manager_approval_status ?? '0');
        if (in_array($dutyStatus, ['1', '2'], true)) {
            $items[] = [
                'date' => (string) ($row->duty_manager_approval_date ?? ''),
                'title' => 'قرار المدير المناوب',
                'status' => $dutyStatus === '1' ? 'completed' : 'rejected',
                'body' => ($dutyStatus === '1' ? 'تمت الموافقة' : 'تم الرفض')
                    .' بواسطة: '.($dutyName !== '' ? $dutyName : (string) ($row->duty_manager_id ?? ''))
                    .(! empty($row->duty_manager_note) ? "\nالملاحظة: ".trim((string) $row->duty_manager_note) : ''),
            ];
        } else {
            $items[] = ['date' => '', 'title' => 'قرار المدير المناوب', 'status' => 'pending', 'body' => 'بانتظار اتخاذ القرار'];
        }
        if ($dutyStatus === '1') {
            $items[] = [
                'date' => (string) ($row->duty_manager_approval_date ?? ''),
                'title' => 'إرسال المعاملة للمتعهد',
                'status' => 'completed',
                'body' => 'تم إرسال رابط اتخاذ القرار إلى جوال المتعهد بعد موافقة المدير المناوب',
            ];
        }
        if (! empty($row->contract_otp_sent_at)) {
            $items[] = [
                'date' => (string) $row->contract_otp_sent_at,
                'title' => 'إرسال رمز التحقق OTP',
                'status' => 'completed',
                'body' => 'تم إرسال رمز تحقق إلى جوال المتعهد قبل حفظ القرار',
            ];
        }
        $contractStatus = (string) ($row->contract_approval_status ?? '0');
        if (in_array($contractStatus, ['1', '2'], true)) {
            $items[] = [
                'date' => (string) ($row->contract_approval_date ?? ''),
                'title' => 'قرار المتعهد',
                'status' => $contractStatus === '1' ? 'completed' : 'rejected',
                'body' => ($contractStatus === '1' ? 'وافق المتعهد على الإقرار' : 'رفض المتعهد الإقرار')
                    .(! empty($row->contract_note) ? "\nالسبب: ".trim((string) $row->contract_note) : ''),
            ];
        } elseif ($dutyStatus === '1') {
            $items[] = ['date' => '', 'title' => 'قرار المتعهد', 'status' => 'pending', 'body' => 'بانتظار قرار المتعهد'];
        }

        return ['row' => $row, 'items' => $items];
    }

    /** ReInvition action from the legacy Sadq-enabled consent page. */
    public function resendConsentInvitation(string $documentId, string $mobile, string $email = ''): void
    {
        $this->authorizeConsent();
        $documentId = trim($documentId);
        $mobile = trim($mobile);
        $email = trim($email);
        abort_unless($documentId !== '' && $mobile !== '', 422, 'بيانات إعادة الدعوة غير مكتملة.');

        $result = app()->environment('testing')
            ? ['errorCode' => 0]
            : app(\App\Services\LegacyWorkflows\SadqClient::class)->sendSignReminder($documentId, $mobile, $email);
        abort_unless((int) ($result['errorCode'] ?? 1) === 0, 502, 'تعذر إرسال تذكير التوقيع.');
    }

    /** Public token lookup used by the contractor PDF/approval pages. */
    public function consentByToken(string $token): ?object
    {
        $this->tableOr404('hospital_admission_consent');
        $row = DB::table('hospital_admission_consent')->where('token', trim($token))->first();
        if ($row === null || (string) ($row->duty_manager_approval_status ?? '0') !== '1') {
            return null;
        }
        return $row;
    }

    public function dutyDecision(int $id, int $status, ?string $note = null): void
    {
        $row = $this->consentFind($id);
        abort_if($row === null, 404);
        $columns = Schema::getColumnListing('hospital_admission_consent');
        $values = array_intersect_key([
            'duty_manager_approval_status' => $status,
            'duty_manager_approval_date' => now(),
            'duty_manager_id' => (int) session('hr_user_id', 0),
            'duty_manager_note' => trim((string) $note),
        ], array_flip($columns));
        DB::table('hospital_admission_consent')->where('id', $id)->update($values);
        if ($status === 1 && ! empty($row->contractor_mobile) && ! empty($row->token)) {
            $link = route('legacy.hospital-admission-consent-contract-approval', ['id' => $row->token]);
            $message = 'تمت الموافقة على إقرار التنويم. للاطلاع على الإقرار واتخاذ القرار: '.$link;
            $mobile = trim((string) $row->contractor_mobile);
            if (! app()->environment('testing')) {
                $result = $this->sms->send($mobile, $message);
                abort_unless($result['ok'] ?? false, 502, 'تعذر إرسال رابط الإقرار للمتعهد.');
            }
            $this->archiveSms($message, $mobile);
        }
    }

    /**
     * Verify the contractor OTP.  A call without a code sends a new code and
     * returns requires_otp=true; this mirrors the public legacy approval page.
     *
     * @return array{requires_otp:bool,verified:bool,message:string}
     */
    public function contractDecision(string $token, int $status, ?string $note, string $otp = '', bool $resend = false, ?string $ip = null): array
    {
        $this->tableOr404('hospital_admission_consent');
        $row = DB::table('hospital_admission_consent')->where('token', $token)->first();
        abort_if($row === null || (string) ($row->duty_manager_approval_status ?? '0') !== '1', 404);
        abort_if(in_array((string) ($row->contract_approval_status ?? '0'), ['1', '2'], true), 422, 'تم حفظ قرار المتعهد مسبقاً.');
        abort_unless(in_array($status, [1, 2], true) && ($status !== 2 || trim((string) $note) !== ''), 422, 'يجب اختيار القرار وكتابة سبب الرفض.');

        $columns = Schema::getColumnListing('hospital_admission_consent');
        $expires = ! empty($row->contract_otp_expires_at) ? strtotime((string) $row->contract_otp_expires_at) : 0;
        $attempts = (int) ($row->contract_otp_attempts ?? 0);
        if ($resend || trim($otp) === '' || empty($row->contract_otp_code) || $expires < time()) {
            $code = (string) random_int(1000, 9999);
            $update = array_intersect_key([
                'contract_otp_code' => $code,
                'contract_otp_expires_at' => now()->addMinutes(5),
                'contract_otp_attempts' => 0,
                'contract_otp_sent_at' => now(),
            ], array_flip($columns));
            DB::table('hospital_admission_consent')->where('id', $row->id)->update($update);
            $mobile = trim((string) ($row->contractor_mobile ?? ''));
            if (! app()->environment('testing') && $mobile !== '') {
                $this->sms->send($mobile, 'رمز التحقق الخاص بقرار موافقة المتعهد هو '.$code);
            }
            $this->archiveSms('رمز التحقق الخاص بقرار موافقة المتعهد هو '.$code, $mobile, (string) $token, $row->language ?? 1);

            return ['requires_otp' => true, 'verified' => false, 'message' => 'تم إرسال رمز التحقق إلى جوال المتعهد.'];
        }

        abort_if($attempts >= 5, 422, 'تم تجاوز عدد محاولات رمز التحقق.');
        if (! hash_equals((string) $row->contract_otp_code, preg_replace('/\D+/', '', $otp) ?: '')) {
            if (in_array('contract_otp_attempts', $columns, true)) {
                DB::table('hospital_admission_consent')->where('id', $row->id)->increment('contract_otp_attempts');
            }
            return ['requires_otp' => true, 'verified' => false, 'message' => 'رمز التحقق غير صحيح.'];
        }

        $update = array_intersect_key([
            'contract_approval_status' => $status,
            'contract_approval_date' => now(),
            'contract_ip' => substr((string) $ip, 0, 45),
            'contract_note' => $status === 1 ? '' : trim((string) $note),
            'contract_otp_code' => null,
            'contract_otp_expires_at' => null,
            'contract_otp_attempts' => 0,
        ], array_flip($columns));
        DB::table('hospital_admission_consent')->where('id', $row->id)->update($update);

        return ['requires_otp' => false, 'verified' => true, 'message' => 'تم التحقق وحفظ قرار المتعهد بنجاح.'];
    }

    /** @return LengthAwarePaginator */
    public function templateList(string $search = ''): LengthAwarePaginator
    {
        $this->authorizeConsent();
        $this->tableOr404('hospital_admission_consent_template');
        $query = DB::table('hospital_admission_consent_template')->orderByDesc('id');
        $this->scopeConsentTemplate($query);
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', '%'.$search.'%')->orWhere('consent_content', 'like', '%'.$search.'%');
            });
        }

        return $query->paginate(15)->withQueryString();
    }

    /** @param array<string, mixed> $data */
    public function templateSave(array $data, ?int $id = null): int
    {
        $this->authorizeConsent();
        $this->tableOr404('hospital_admission_consent_template');
        $columns = Schema::getColumnListing('hospital_admission_consent_template');
        $values = array_intersect_key([
            'date' => time(),
            'branch_id' => $this->branchId(),
            'title' => trim((string) ($data['title'] ?? '')),
            'consent_content' => trim((string) ($data['consent_content'] ?? '')),
            'created_by' => (int) session('hr_user_id', 0),
            'created_at' => now(),
            'companies_groups_id' => $this->companyId(),
            'publish' => (int) ($data['publish'] ?? 1),
        ], array_flip($columns));
        if ($id === null) {
            return (int) DB::table('hospital_admission_consent_template')->insertGetId($values);
        }
        $row = DB::table('hospital_admission_consent_template')->where('id', $id);
        $this->scopeConsentTemplate($row);
        abort_if($row->first() === null, 404);
        unset($values['branch_id'], $values['companies_groups_id'], $values['created_by'], $values['created_at']);
        DB::table('hospital_admission_consent_template')->where('id', $id)->update($values);

        return $id;
    }

    public function templateToggle(int $id): void
    {
        $this->authorizeConsent();
        $row = DB::table('hospital_admission_consent_template')->where('id', $id);
        $this->scopeConsentTemplate($row);
        $record = $row->first();
        abort_if($record === null, 404);
        DB::table('hospital_admission_consent_template')->where('id', $id)->update(['publish' => (int) ! ((int) $record->publish)]);
    }

    public function templateFind(int $id): ?object
    {
        $this->authorizeConsent();
        $this->tableOr404('hospital_admission_consent_template');
        $query = DB::table('hospital_admission_consent_template')->where('id', $id);
        $this->scopeConsentTemplate($query);

        return $query->first();
    }

    public function templateDelete(int $id): void
    {
        $this->authorizeConsent();
        $row = DB::table('hospital_admission_consent_template')->where('id', $id);
        $this->scopeConsentTemplate($row);
        abort_if($row->first() === null, 404);
        DB::table('hospital_admission_consent_template')->where('id', $id)->delete();
    }

    /** @return LengthAwarePaginator */
    public function doctorList(string $search = '', string $status = ''): LengthAwarePaginator
    {
        $this->authorize();
        $this->tableOr404('inpatient_doctors');
        $query = DB::table('inpatient_doctors')->orderByDesc('id');
        $this->scope($query, 'inpatient_doctors');
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                foreach (['doctor_id_no', 'name_ar', 'name_en', 'designation_ar', 'designation_en', 'email', 'mobile_no'] as $field) {
                    if (Schema::hasColumn('inpatient_doctors', $field)) {
                        $q->orWhere($field, 'like', '%'.$search.'%');
                    }
                }
            });
        }
        if (in_array($status, ['active', 'inactive'], true) && Schema::hasColumn('inpatient_doctors', 'status')) $query->where('status', $status);

        return $query->paginate(15)->withQueryString();
    }

    public function doctorFind(int $id): ?object
    {
        $this->authorize();
        $query = DB::table('inpatient_doctors')->where('id', $id);
        $this->scope($query, 'inpatient_doctors');

        return $query->first();
    }

    /** @param array<string, mixed> $data */
    public function doctorSave(array $data, ?int $id = null): int
    {
        $this->authorize();
        $this->tableOr404('inpatient_doctors');
        $columns = Schema::getColumnListing('inpatient_doctors');
        $doctorId = preg_replace('/\D+/', '', trim((string) ($data['doctor_id_no'] ?? '')));
        $mobile = preg_replace('/\D+/', '', trim((string) ($data['mobile_no'] ?? '')));
        $email = filter_var(trim((string) ($data['email'] ?? '')), FILTER_SANITIZE_EMAIL);
        abort_unless($doctorId !== '' && ctype_digit($doctorId) && (int) $doctorId > 0, 422, 'رقم معرف الطبيب يجب أن يكون رقمياً وأكبر من صفر.');
        abort_unless((string) ($data['name_ar'] ?? '') !== '' && (string) ($data['name_en'] ?? '') !== '', 422, 'اسما الطبيب بالعربية والإنجليزية مطلوبان.');
        abort_unless(filter_var($email, FILTER_VALIDATE_EMAIL), 422, 'البريد الإلكتروني غير صحيح.');
        abort_unless((bool) preg_match('/^05[0-9]{8}$/', $mobile), 422, 'رقم الجوال يجب أن يكون بصيغة 05XXXXXXXX.');
        $duplicate = DB::table('inpatient_doctors')
            ->where('doctor_id_no', $doctorId)
            ->where('branch_id', $this->branchId())
            ->when($id !== null, fn ($q) => $q->where('id', '!=', $id))
            ->exists();
        abort_if($duplicate, 422, 'رقم الهوية/المعرف موجود مسبقاً في هذا الفرع.');
        $values = array_intersect_key([
            'companies_groups_id' => $this->companyId(),
            'branch_id' => $this->branchId(),
            'doctor_id_no' => $doctorId,
            'name_ar' => trim((string) ($data['name_ar'] ?? '')),
            'name_en' => trim((string) ($data['name_en'] ?? '')),
            'designation_ar' => trim((string) ($data['designation_ar'] ?? '')),
            'designation_en' => trim((string) ($data['designation_en'] ?? '')),
            'email' => $email,
            'mobile_no' => $mobile,
            'status' => (string) ($data['status'] ?? 'active'),
            'created_by' => (int) session('hr_user_id', 0),
            'updated_by' => (int) session('hr_user_id', 0),
            'created_at' => now(),
            'updated_at' => now(),
        ], array_flip($columns));
        if ($id === null) {
            return (int) DB::table('inpatient_doctors')->insertGetId($values);
        }
        abort_if($this->doctorFind($id) === null, 404);
        unset($values['branch_id'], $values['companies_groups_id'], $values['created_by'], $values['created_at']);
        DB::table('inpatient_doctors')->where('id', $id)->update($values);

        return $id;
    }

    public function doctorToggle(int $id): void
    {
        $row = $this->doctorFind($id);
        abort_if($row === null, 404);
        DB::table('inpatient_doctors')->where('id', $id)->update(['status' => ($row->status ?? 'active') === 'active' ? 'inactive' : 'active']);
    }

    public function doctorDelete(int $id): void
    {
        abort_if($this->doctorFind($id) === null, 404);
        DB::table('inpatient_doctors')->where('id', $id)->delete();
    }

    /** @param array<string, mixed> $filters */
    public function approvalList(array $filters = []): LengthAwarePaginator
    {
        $this->authorizeMedicalApproval();
        $this->tableOr404('medical_approval_notifications');
        $query = DB::table('medical_approval_notifications')->orderByDesc('id');
        $this->scope($query, 'medical_approval_notifications');
        $this->applyApprovalFilters($query, $filters);
        $this->whereLatestApprovalStatus($query, (int) ($filters['status'] ?? 0));

        $paginator = $query->paginate(15)->withQueryString();
        $paginator->setCollection($paginator->getCollection()->map(function (object $row): object {
            $row->logs = Schema::hasTable('medical_approval_notification_logs')
                ? DB::table('medical_approval_notification_logs as logs')
                    ->leftJoin('medical_approval_statuses as statuses', 'statuses.id', '=', 'logs.medical_approval_status_id')
                    ->leftJoin('medical_approval_rejection_reasons as reasons', 'reasons.id', '=', 'logs.rejection_reason_id')
                    ->where('logs.medical_approval_notifications_id', $row->id)
                    ->orderByDesc('logs.id')
                    ->select('logs.*', 'statuses.name_ar as status_name', 'reasons.name_ar as reason_name')
                    ->get()
                : collect();
            return $row;
        }));

        return $paginator;
    }

    /** @return array{total:int,approved:int,not_approved:int,transferred:int} */
    public function approvalStats(array $filters = []): array
    {
        $this->authorizeMedicalApproval();
        $base = DB::table('medical_approval_notifications');
        $this->scope($base, 'medical_approval_notifications');
        $this->applyApprovalFilters($base, $filters);
        $total = (clone $base)->count();
        $approvedQuery = clone $base;
        $this->whereLatestApprovalStatus($approvedQuery, 9);
        $approved = Schema::hasTable('medical_approval_notification_logs') ? $approvedQuery->count() : 0;
        $notApprovedQuery = clone $base;
        $this->whereLatestApprovalStatus($notApprovedQuery, -1);
        $notApproved = Schema::hasTable('medical_approval_notification_logs') ? $notApprovedQuery->count() : 0;
        $transferred = Schema::hasColumn('medical_approval_notifications', 'sent_to_collection') ? (clone $base)->where('sent_to_collection', 1)->count() : 0;
        return ['total' => (int) $total, 'approved' => (int) $approved, 'not_approved' => (int) $notApproved, 'transferred' => (int) $transferred];
    }

    /** @param array<string, mixed> $filters */
    private function applyApprovalFilters($query, array $filters): void
    {
        if (($filters['search'] ?? '') !== '') {
            $search = trim((string) $filters['search']);
            $query->where(function ($nested) use ($search): void {
                foreach (['patient_name', 'patient_identity', 'room_number'] as $field) {
                    if (Schema::hasColumn('medical_approval_notifications', $field)) {
                        $nested->orWhere('medical_approval_notifications.'.$field, 'like', '%'.$search.'%');
                    }
                }
            });
        }
        if (($filters['sent'] ?? '') !== '' && Schema::hasColumn('medical_approval_notifications', 'sent_to_collection')) {
            $query->where('medical_approval_notifications.sent_to_collection', (int) $filters['sent']);
        }
        if (($filters['from'] ?? '') !== '' && Schema::hasColumn('medical_approval_notifications', 'created_at')) {
            $query->where('medical_approval_notifications.created_at', '>=', $filters['from'].' 00:00:00');
        }
        if (($filters['to'] ?? '') !== '' && Schema::hasColumn('medical_approval_notifications', 'created_at')) {
            $query->where('medical_approval_notifications.created_at', '<=', $filters['to'].' 23:59:59');
        }
    }

    private function whereLatestApprovalStatus($query, int $status): void
    {
        if ($status === 0 || ! Schema::hasTable('medical_approval_notification_logs')) {
            return;
        }

        $query->whereExists(function ($nested) use ($status): void {
            $nested->select(DB::raw(1))
                ->from('medical_approval_notification_logs as latest_log')
                ->whereColumn('latest_log.medical_approval_notifications_id', 'medical_approval_notifications.id')
                ->whereRaw('latest_log.id = (select max(l2.id) from medical_approval_notification_logs l2 where l2.medical_approval_notifications_id = latest_log.medical_approval_notifications_id)')
                ->when($status > 0, fn ($q) => $q->where('latest_log.medical_approval_status_id', $status), fn ($q) => $q->where('latest_log.medical_approval_status_id', '<>', 9));
        });
    }

    /** @return list<object> */
    private function approvalClinicians(): array
    {
        if (! Schema::hasTable('clinicians')) return [];
        // Old medical_approval_notifications.php exposed all published
        // clinicians; only inpatient doctors were company-scoped.
        $query = DB::table('clinicians')->where('publish', 1);
        return $query->orderBy('name_ar')->get()->all();
    }

    /** @return list<object> */
    private function approvalInpatientDoctors(): array
    {
        if (! Schema::hasTable('inpatient_doctors')) return [];
        $query = DB::table('inpatient_doctors')->where('status', 'active');
        if (Schema::hasColumn('inpatient_doctors', 'companies_groups_id')) {
            $query->where('companies_groups_id', $this->companyId());
        }
        return $query->orderBy('name_ar')->get()->all();
    }

    /** @return array<string, mixed> */
    public function approvalOptions(): array
    {
        $this->authorizeMedicalApproval();

        return [
            'statuses' => Schema::hasTable('medical_approval_statuses') ? DB::table('medical_approval_statuses')->orderBy('id')->get() : collect(),
            'reasons' => Schema::hasTable('medical_approval_rejection_reasons') ? DB::table('medical_approval_rejection_reasons')->orderBy('name_ar')->get() : collect(),
            'clinicians' => $this->approvalClinicians(),
            'inpatientDoctors' => $this->approvalInpatientDoctors(),
            'cc' => $this->contactRows('cc', true),
        ];
    }

    public function approvalFind(int $id): ?object
    {
        $this->authorizeMedicalApproval();
        $query = DB::table('medical_approval_notifications')->where('id', $id);
        $this->scope($query, 'medical_approval_notifications');
        $row = $query->first();
        if ($row === null) {
            return null;
        }
        $row->logs = Schema::hasTable('medical_approval_notification_logs')
            ? DB::table('medical_approval_notification_logs as logs')
                ->leftJoin('medical_approval_statuses as statuses', 'statuses.id', '=', 'logs.medical_approval_status_id')
                ->leftJoin('medical_approval_rejection_reasons as reasons', 'reasons.id', '=', 'logs.rejection_reason_id')
                ->where('logs.medical_approval_notifications_id', $id)->orderByDesc('logs.id')
                ->select('logs.*', 'statuses.name_ar as status_name', 'reasons.name_ar as reason_name')->get()
            : collect();

        return $row;
    }

    /** @param array<string, mixed> $data */
    public function approvalSave(array $data, ?int $id = null): int
    {
        $this->authorizeMedicalApproval();
        $this->tableOr404('medical_approval_notifications');
        $columns = Schema::getColumnListing('medical_approval_notifications');
        $values = array_intersect_key([
            'companies_groups_id' => $this->companyId(),
            'branch_id' => $this->branchId(),
            'patient_name' => trim((string) ($data['patient_name'] ?? '')),
            'patient_identity' => trim((string) ($data['patient_identity'] ?? '')),
            'room_number' => trim((string) ($data['room_number'] ?? '')) ?: null,
            'clinician_id' => $this->csv($data['clinician_id'] ?? []),
            'inpatient_clinician_id' => $this->csv($data['inpatient_clinician_id'] ?? []),
            'medical_approval_cc_ids' => $this->csv($data['medical_approval_cc_ids'] ?? []),
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            'created_by' => (int) session('hr_user_id', 0),
            'updated_by' => (int) session('hr_user_id', 0),
            'created_at' => now(),
            'updated_at' => now(),
        ], array_flip($columns));
        $status = (int) ($data['medical_approval_status_id'] ?? 0);
        $reason = (int) ($data['rejection_reason_id'] ?? 0) ?: null;
        abort_unless($status > 0, 422, 'يجب اختيار حالة الموافقة الطبية.');
        abort_unless($this->csvIds($data['clinician_id'] ?? []) !== [] || $this->csvIds($data['inpatient_clinician_id'] ?? []) !== [], 422, 'يجب اختيار طبيب واحد على الأقل.');

        return (int) DB::transaction(function () use ($id, $values, $status, $reason): int {
            if ($id === null) {
                $notificationId = (int) DB::table('medical_approval_notifications')->insertGetId($values);
            } else {
                abort_if($this->approvalFind($id) === null, 404);
                unset($values['branch_id'], $values['companies_groups_id'], $values['created_by'], $values['created_at']);
                DB::table('medical_approval_notifications')->where('id', $id)->update($values);
                $notificationId = $id;
            }
            $this->approvalLog($notificationId, $status, $reason, (string) ($values['notes'] ?? ''));
            return $notificationId;
        });
    }

    /** Notify the selected clinicians and CC contacts after create/update. */
    public function approvalNotifySelected(int $id): void
    {
        $row = $this->approvalFind($id);
        abort_if($row === null, 404);
        $latest = collect($row->logs)->first();
        $message = 'ID:'.($row->patient_identity ?? '').' Patient:'.($row->patient_name ?? '').' '.(($row->room_number ?? '') !== '' ? 'Room:'.$row->room_number : '').' Status: '.($latest->status_name ?? '').' Reason: '.($latest->reason_name ?? '').' '.($latest->notes ?? '');
        $recipients = collect();
        foreach ($this->approvalRecipients($row, false) as $recipient) $recipients->push($recipient);
        foreach ($recipients->pluck('mobile')->filter()->unique() as $mobile) {
            if (! app()->environment('testing')) { $result = $this->sms->send((string) $mobile, $message); abort_unless($result['ok'] ?? false, 502, 'تعذر إرسال إشعار الموافقة الطبية.'); }
            $this->archiveSms($message, (string) $mobile);
        }
        if (! app()->environment('testing')) foreach ($recipients->pluck('email')->filter()->unique() as $email) Mail::raw($message, fn ($mail) => $mail->to($email)->subject('إشعار موافقة طبية'));
    }

    public function approvalDelete(int $id): void
    {
        abort_if($this->approvalFind($id) === null, 404);
        DB::transaction(function () use ($id): void {
            if (Schema::hasTable('medical_approval_notification_logs')) {
                DB::table('medical_approval_notification_logs')->where('medical_approval_notifications_id', $id)->delete();
            }
            DB::table('medical_approval_notifications')->where('id', $id)->delete();
        });
    }

    public function approvalSend(int $id): void
    {
        $row = $this->approvalFind($id);
        abort_if($row === null, 404);
        $latest = collect($row->logs)->first();
        $status = $latest->status_name ?? '';
        $reason = $latest->reason_name ?? '';
        $message = 'ID:'.($row->patient_identity ?? '').' Patient:'.($row->patient_name ?? '').' '.(($row->room_number ?? '') !== '' ? 'Room:'.$row->room_number : '').' Status: '.$status.' Reason: '.$reason.' '.($latest->notes ?? '');
        $mobiles = [];
        $emails = [];
        // Transfer is a separate legacy action: it notifies active
        // collections only, while create/update notify the selected doctors
        // and CC recipients.
        foreach ($this->approvalCollectionRecipients() as $recipient) {
            if (($recipient->mobile ?? '') !== '') {
                $mobiles[] = trim((string) $recipient->mobile);
            }
            if (($recipient->email ?? '') !== '') {
                $emails[] = trim((string) $recipient->email);
            }
        }
        foreach (array_unique($mobiles) as $mobile) {
            if (! app()->environment('testing')) {
                $result = $this->sms->send($mobile, $message);
                abort_unless($result['ok'] ?? false, 502, 'تعذر إرسال إشعار الموافقة الطبية.');
            }
            $this->archiveSms($message, $mobile);
        }
        if (! app()->environment('testing')) {
            foreach (array_unique($emails) as $email) {
                Mail::raw($message, fn ($mail) => $mail->to($email)->subject('إشعار موافقة طبية'));
            }
        }
        $columns = Schema::getColumnListing('medical_approval_notifications');
        $updates = array_intersect_key(['sent_to_collection' => 1, 'sent_to_collection_by' => (int) session('hr_user_id', 0), 'sent_to_collection_at' => now()], array_flip($columns));
        DB::table('medical_approval_notifications')->where('id', $id)->update($updates);
    }

    /** @return LengthAwarePaginator */
    public function contactList(string $kind, string $search = ''): LengthAwarePaginator
    {
        $table = $this->contactTable($kind);
        $this->authorizeMedicalApproval();
        // The old contacts screen listed the company's contacts across
        // branches. Mutations remain branch+company scoped in contactFind(),
        // matching the legacy update/delete helper.
        $query = DB::table($table)->where('companies_groups_id', $this->companyId())->orderByDesc('id');
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('employee_name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')->orWhere('mobile', 'like', '%'.$search.'%');
            });
        }

        return $query->paginate(15)->withQueryString();
    }

    public function contactFind(string $kind, int $id): ?object
    {
        $table = $this->contactTable($kind);
        $this->authorizeMedicalApproval();
        return DB::table($table)->where('id', $id)->where('companies_groups_id', $this->companyId())->where('branch_id', $this->branchId())->first();
    }

    /** @param array<string, mixed> $data */
    public function contactSave(string $kind, array $data, ?int $id = null): int
    {
        $table = $this->contactTable($kind);
        $this->authorizeMedicalApproval();
        abort_unless(trim((string) ($data['email'] ?? '')) !== '' || trim((string) ($data['mobile'] ?? '')) !== '', 422, 'يجب إدخال البريد الإلكتروني أو الجوال.');
        $columns = Schema::getColumnListing($table);
        $values = array_intersect_key([
            'companies_groups_id' => $this->companyId(),
            'branch_id' => $this->branchId(),
            'employee_name' => trim((string) ($data['employee_name'] ?? '')),
            'email' => trim((string) ($data['email'] ?? '')),
            'mobile' => trim((string) ($data['mobile'] ?? '')),
            'status' => (string) ($data['status'] ?? 'active'),
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => (int) session('hr_user_id', 0),
            'updated_by' => (int) session('hr_user_id', 0),
        ], array_flip($columns));
        if ($id === null) {
            return (int) DB::table($table)->insertGetId($values);
        }
        abort_if($this->contactFind($kind, $id) === null, 404);
        unset($values['companies_groups_id'], $values['branch_id'], $values['created_at'], $values['created_by']);
        DB::table($table)->where('id', $id)->update($values);
        return $id;
    }

    public function contactToggle(string $kind, int $id): void
    {
        $row = $this->contactFind($kind, $id);
        abort_if($row === null, 404);
        DB::table($this->contactTable($kind))->where('id', $id)->update(['status' => ($row->status ?? 'active') === 'active' ? 'inactive' : 'active']);
    }

    public function contactDelete(string $kind, int $id): void
    {
        abort_if($this->contactFind($kind, $id) === null, 404);
        DB::table($this->contactTable($kind))->where('id', $id)->delete();
    }

    /** @return list<object> */
    private function contactRows(string $kind, bool $activeOnly = false): array
    {
        $table = $kind === 'cc' ? 'medical_approval_cc' : 'medical_approval_collections';
        if (! Schema::hasTable($table)) {
            return [];
        }
        $query = DB::table($table)->where('companies_groups_id', $this->companyId())->orderBy('employee_name');
        if ($activeOnly) {
            $query->where('status', 'active');
        }
        return $query->get()->all();
    }

    private function contactTable(string $kind): string
    {
        abort_unless(in_array($kind, ['cc', 'collections'], true), 404);
        $table = $kind === 'cc' ? 'medical_approval_cc' : 'medical_approval_collections';
        $this->tableOr404($table);
        return $table;
    }

    /** @return list<object> */
    private function approvalRecipients(object $row, bool $includeCollections = false): array
    {
        $recipients = collect();
        foreach ($this->csvIds($row->clinician_id ?? '') as $id) {
            if (Schema::hasTable('clinicians')) {
                $record = DB::table('clinicians')->where('id', $id)->first(['email', 'mobile']);
                if ($record) $recipients->push($record);
            }
        }
        foreach ($this->csvIds($row->inpatient_clinician_id ?? '') as $id) {
            if (Schema::hasTable('inpatient_doctors')) {
                $record = DB::table('inpatient_doctors')->where('id', $id)->where('companies_groups_id', $this->companyId())->first(['email', 'mobile_no as mobile']);
                if ($record) $recipients->push($record);
            }
        }
        foreach ($this->csvIds($row->medical_approval_cc_ids ?? '') as $id) {
            if (Schema::hasTable('medical_approval_cc')) {
                $record = DB::table('medical_approval_cc')->where('id', $id)->where('companies_groups_id', $this->companyId())->first(['email', 'mobile']);
                if ($record) $recipients->push($record);
            }
        }
        if ($includeCollections) {
            foreach ($this->approvalCollectionRecipients() as $record) {
                $recipients->push($record);
            }
        }
        return $recipients->all();
    }

    /** @return list<object> */
    private function approvalCollectionRecipients(): array
    {
        // The old transfer query was company-wide and did not add branch_id.
        return $this->contactRows('collections', true);
    }

    private function approvalLog(int $id, int $status, ?int $reason, string $notes): void
    {
        if (! Schema::hasTable('medical_approval_notification_logs')) return;
        DB::table('medical_approval_notification_logs')->insert(array_intersect_key([
            'medical_approval_notifications_id' => $id,
            'medical_approval_status_id' => $status,
            'rejection_reason_id' => $reason,
            'notes' => $notes ?: null,
            'created_at' => now(),
            'created_by' => (int) session('hr_user_id', 0),
        ], array_flip(Schema::getColumnListing('medical_approval_notification_logs'))));
    }

    private function archiveSms(string $message, string $mobile): void
    {
        if (! Schema::hasTable('sms_archive')) return;
        DB::table('sms_archive')->insert(array_intersect_key([
            'message' => $message, 'mobile' => $mobile, 'created_by' => (int) session('hr_user_id', 0),
            'created_at' => now(), 'branch_id' => $this->branchId(), 'companies_groups_id' => $this->companyId(), 'type' => 1, 'language' => 2,
        ], array_flip(Schema::getColumnListing('sms_archive'))));
    }

    /** Notify the selected duty managers just as the legacy consent screen did. */
    private function notifyDutyManagers(array|string|null $ids): void
    {
        if (! Schema::hasTable('ra_users')) {
            return;
        }
        foreach ($this->csvIds($ids) as $id) {
            $columns = Schema::getColumnListing('ra_users');
            $manager = DB::table('ra_users')->where('hr_id', $id)->first();
            if ($manager === null) {
                continue;
            }
            $mobile = trim((string) ($manager->mobile ?? ''));
            $email = trim((string) ($manager->hr_email_address ?? ''));
            $message = 'تم إنشاء إقرار تنويم جديد';
            if ($mobile !== '') {
                if (! app()->environment('testing')) {
                    $result = $this->sms->send($mobile, $message);
                    abort_unless($result['ok'] ?? false, 502, 'تعذر إشعار مدير المناوبة.');
                }
                $this->archiveSms($message, $mobile);
            }
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && ! app()->environment('testing')) {
                Mail::raw($message, fn ($mail) => $mail->to($email)->subject('إقرار تنويم جديد'));
            }
        }
    }

    private function authorizeConsent(): void
    {
        $this->authorize();
        abort_unless(in_array($this->branchId(), self::CONSENT_BRANCHES, true), 403);
    }

    private function authorizeMedicalApproval(): void
    {
        $this->authorize();
        // The legacy screen was available to the medical-approval branch and
        // to the system administrator. Keep the branch boundary for normal
        // users, but do not block an administrator whose active branch is a
        // different branch.
        abort_unless($this->branchId() === self::MEDICAL_APPROVAL_BRANCH || $this->permissions->isAdmin(), 403);
    }

    private function scopeConsent($query): void
    {
        // The old consent list intentionally showed the company's records
        // across its allowed consent branches; creation still records the
        // current branch for audit/scoping.
        $query->where('companies_groups_id', $this->companyId());
    }

    private function scopeConsentTemplate($query): void
    {
        $query->where('hospital_admission_consent_template.companies_groups_id', $this->companyId());
    }

    /** @param array<int|string>|string|null $value */
    private function csv(array|string|null $value): string
    {
        if (is_string($value)) return implode(',', $this->csvIds($value));
        return implode(',', collect($value ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->values()->all());
    }

    /** @return list<int> */
    private function csvIds(array|string|null $value): array
    {
        $parts = is_array($value) ? $value : preg_split('/[,\s]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
        return collect($parts ?: [])->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
    }

    /** @param list<string> $tables @param list<string> $preferred */
    private function firstExistingRows(array $tables, array $preferred): array
    {
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) continue;
            $columns = Schema::getColumnListing($table);
            $select = array_values(array_intersect($preferred, $columns));
            return DB::table($table)->orderBy($select[0] ?? 'id')->get()->all();
        }
        return [];
    }

    /** @param array<string, mixed> $filters */
    public function packageList(array $filters = []): LengthAwarePaginator
    {
        $this->authorize();
        $this->tableOr404('hospitalization_packages');
        $query = DB::table('hospitalization_packages')->orderByDesc('id');
        $this->scope($query, 'hospitalization_packages');
        foreach (['specialized_clinics_id', 'insurance_companies_id'] as $field) {
            if ((int) ($filters[$field] ?? 0) > 0 && Schema::hasColumn('hospitalization_packages', $field)) {
                $query->where($field, (int) $filters[$field]);
            }
        }
        if (($filters['search'] ?? '') !== '') {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search): void {
                foreach (['code', 'name_ar', 'name_en', 'notice_ar', 'notice_en'] as $field) {
                    if (Schema::hasColumn('hospitalization_packages', $field)) $q->orWhere($field, 'like', '%'.$search.'%');
                }
            });
        }
        return $query->paginate(15)->withQueryString();
    }

    /** @return array<string, mixed> */
    public function packageOptions(): array
    {
        $this->authorize();
        return [
            'clinics' => Schema::hasTable('specialized_clinics') ? DB::table('specialized_clinics')->where('publish', 1)->orderBy('id')->get() : collect(),
            'insurance' => Schema::hasTable('insurance_companies') ? DB::table('insurance_companies')->where('publish', 1)->orderBy('id')->get() : collect(),
        ];
    }

    public function packageFind(int $id): ?object
    {
        $this->authorize();
        $query = DB::table('hospitalization_packages')->where('id', $id);
        $this->scope($query, 'hospitalization_packages');
        return $query->first();
    }

    /** @param array<string, mixed> $data */
    public function packageSave(array $data, ?int $id = null): int
    {
        $this->authorize();
        $this->tableOr404('hospitalization_packages');
        $columns = Schema::getColumnListing('hospitalization_packages');
        $values = array_intersect_key([
            'specialized_clinics_id' => (int) ($data['specialized_clinics_id'] ?? 0),
            'code' => trim((string) ($data['code'] ?? '')),
            'days' => (int) ($data['days'] ?? 0),
            'price' => (float) ($data['price'] ?? 0),
            'name_ar' => trim((string) ($data['name_ar'] ?? '')),
            'name_en' => trim((string) ($data['name_en'] ?? '')),
            'notice_ar' => trim((string) ($data['notice_ar'] ?? '')),
            'notice_en' => trim((string) ($data['notice_en'] ?? '')),
            'insurance_companies_id' => (int) ($data['insurance_companies_id'] ?? 0),
            'created_at' => now(),
            'created_by' => (int) session('hr_user_id', 0),
            'updated_at' => now(),
            'updated_by' => (int) session('hr_user_id', 0),
            'companies_groups_id' => $this->companyId(),
            'publish' => (int) ($data['publish'] ?? 1),
        ], array_flip($columns));
        if ($id === null) {
            // The legacy form displayed a price input but intentionally
            // persisted zero; keep that data contract for new records.
            if (array_key_exists('price', $values)) $values['price'] = 0;
            return (int) DB::table('hospitalization_packages')->insertGetId($values);
        }
        abort_if($this->packageFind($id) === null, 404);
        unset($values['companies_groups_id'], $values['created_at'], $values['created_by']);
        DB::table('hospitalization_packages')->where('id', $id)->update($values);
        return $id;
    }

    public function packageToggle(int $id): void
    {
        $row = $this->packageFind($id); abort_if($row === null, 404);
        abort_unless(Schema::hasColumn('hospitalization_packages', 'publish'), 422);
        DB::table('hospitalization_packages')->where('id', $id)->update(['publish' => (int) ! ((int) $row->publish)]);
    }

    public function packageDelete(int $id): void
    {
        abort_if($this->packageFind($id) === null, 404);
        DB::table('hospitalization_packages')->where('id', $id)->delete();
    }

    /**
     * Read-only catalog behind the legacy packages_details.php screen.  That
     * page is not the hospitalization_packages CRUD; it switches between the
     * five package tables for a clinic/insurer/code-section combination.
     * Keeping the table map explicit prevents user input becoming a table name.
     *
     * @param array<string, int> $filters
     * @return array<string, mixed>
     */
    public function packageCatalog(array $filters = []): array
    {
        $this->authorize();
        $catalog = [
            'pharmacy' => 'pharmacy_packages',
            'rays' => 'rays_packages',
            'laboratory' => 'laboratory_packages',
            'natural_therapy' => 'natural_therapy_packages',
            'surgeries' => 'surgeries_packages',
        ];
        $rows = [];
        foreach ($catalog as $kind => $table) {
            if (! Schema::hasTable($table)) continue;
            $query = DB::table($table)->where('publish', 1);
            foreach (['specialized_clinics_id', 'insurance_companies_id', 'codes_sections_id'] as $field) {
                if ((int) ($filters[$field] ?? 0) > 0 && Schema::hasColumn($table, $field)) $query->where($field, (int) $filters[$field]);
            }
            $rows[$kind] = $query->orderByDesc('id')->get();
        }
        return [
            'filters' => $filters,
            'clinic' => Schema::hasTable('specialized_clinics') && (int) ($filters['specialized_clinics_id'] ?? 0) > 0 ? DB::table('specialized_clinics')->where('id', (int) $filters['specialized_clinics_id'])->first() : null,
            'insurance' => Schema::hasTable('insurance_companies') && (int) ($filters['insurance_companies_id'] ?? 0) > 0 ? DB::table('insurance_companies')->where('id', (int) $filters['insurance_companies_id'])->first() : null,
            'section' => Schema::hasTable('codes_sections') && (int) ($filters['codes_sections_id'] ?? 0) > 0 ? DB::table('codes_sections')->where('id', (int) $filters['codes_sections_id'])->first() : null,
            'clinics' => Schema::hasTable('specialized_clinics') ? DB::table('specialized_clinics')->where('publish', 1)->orderBy('id')->get() : collect(),
            'insuranceCompanies' => Schema::hasTable('insurance_companies') ? DB::table('insurance_companies')->where('publish', 1)->orderBy('id')->get() : collect(),
            'sections' => Schema::hasTable('codes_sections') ? DB::table('codes_sections')->where('publish', 1)->orderBy('id')->get() : collect(),
            'rows' => $rows,
        ];
    }

    /** @param array<string, mixed> $filters */
    public function report9List(array $filters = []): LengthAwarePaginator
    {
        $this->authorizeReport9();
        $this->tableOr404('report_9');
        $query = DB::table('report_9')->orderByDesc('id');
        $this->scopeReport9($query);
        if (($filters['from'] ?? '') !== '' && Schema::hasColumn('report_9', 'date')) $query->where('date', '>=', strtotime((string) $filters['from']));
        if (($filters['to'] ?? '') !== '' && Schema::hasColumn('report_9', 'date')) $query->where('date', '<=', strtotime((string) $filters['to'].' 23:59:59'));
        $query->when((int) ($filters['period_id'] ?? 0) > 0 && Schema::hasColumn('report_9', 'period'), fn ($q) => $q->where('period', (int) $filters['period_id']));
        $query->when((int) ($filters['creator'] ?? 0) > 0 && Schema::hasColumn('report_9', 'creator'), fn ($q) => $q->where('creator', (int) $filters['creator']));
        $paginator = $query->paginate(15)->withQueryString();
        $paginator->setCollection($paginator->getCollection()->map(function (object $row): object {
            $row->period_record = Schema::hasTable('duty_period')
                ? DB::table('duty_period')->where('id', (int) ($row->period ?? 0))->first()
                : null;
            $row->place_record = Schema::hasTable('branches_area')
                ? DB::table('branches_area')->where('id', (int) ($row->rep_place ?? 0))->first()
                : null;
            $row->creator_record = Schema::hasTable('ra_users')
                ? DB::table('ra_users')->where('hr_id', (int) ($row->creator ?? 0))->first()
                : null;
            return $row;
        }));
        return $paginator;
    }

    /** @param array<string, mixed> $filters @return array{attendees:int,absence:int,latecomers:int,permissible:int} */
    public function report9AttendanceTotals(array $filters = []): array
    {
        $this->authorizeReport9();
        $this->tableOr404('report_9');
        $query = DB::table('report_9')->select('report_9.id');
        $this->scopeReport9($query);
        if (($filters['from'] ?? '') !== '' && Schema::hasColumn('report_9', 'date')) $query->where('date', '>=', strtotime((string) $filters['from']));
        if (($filters['to'] ?? '') !== '' && Schema::hasColumn('report_9', 'date')) $query->where('date', '<=', strtotime((string) $filters['to'].' 23:59:59'));
        $query->when((int) ($filters['period_id'] ?? 0) > 0 && Schema::hasColumn('report_9', 'period'), fn ($q) => $q->where('period', (int) $filters['period_id']));
        $query->when((int) ($filters['creator'] ?? 0) > 0 && Schema::hasColumn('report_9', 'creator'), fn ($q) => $q->where('creator', (int) $filters['creator']));
        $ids = $query->pluck('id');
        if ($ids->isEmpty() || ! Schema::hasTable('employees_attendance')) return ['attendees' => 0, 'absence' => 0, 'latecomers' => 0, 'permissible' => 0];

        $totals = DB::table('employees_attendance')->whereIn('report_id', $ids)->where('branch_id', $this->reportBranchId())->where('companies_groups_id', $this->companyId())
            ->selectRaw('COALESCE(SUM(attendees),0) as attendees, COALESCE(SUM(absence),0) as absence, COALESCE(SUM(latecomers),0) as latecomers, COALESCE(SUM(permissible),0) as permissible')
            ->first();
        return [
            'attendees' => (int) ($totals->attendees ?? 0),
            'absence' => (int) ($totals->absence ?? 0),
            'latecomers' => (int) ($totals->latecomers ?? 0),
            'permissible' => (int) ($totals->permissible ?? 0),
        ];
    }

    /** @return array<string, mixed> */
    public function report9Options(): array
    {
        $this->authorizeReport9();
        return [
            'sections' => $this->publishedRows('rep9_section'),
            'notices' => $this->publishedRows('rep9_notice'),
            'actions' => $this->publishedRows('rep9_actions'),
            'supportServices' => $this->publishedRows('support_services'),
            'employees' => $this->report9Employees(),
            'periods' => $this->publishedRows('duty_period', 'id'),
            'places' => $this->publishedRows('branches_area', 'name_ar', ['branch_id' => $this->reportBranchId()]),
            'departments' => $this->publishedRows('branches_departments'),
            'maintenanceDepartments' => $this->publishedRows('support_services_departments', 'name_ar', ['branch_id' => $this->reportBranchId()]),
            'maintenanceTypes' => $this->publishedRows('support_services', 'name_ar', ['branch_id' => $this->reportBranchId()]),
            'requestTypes' => $this->publishedRows('maintenance_request_type'),
        ];
    }

    /** @return list<object> */
    public function report9Lookup(string $kind, int $sectionId = 0): array
    {
        $this->authorizeReport9();
        $table = $kind === 'action' ? 'rep9_actions' : 'rep9_notice';
        $this->tableOr404($table);
        $query = DB::table($table)->orderBy('name_ar');
        if (Schema::hasColumn($table, 'publish')) $query->where('publish', 1);
        if ($sectionId > 0 && Schema::hasColumn($table, 'section_id')) $query->where('section_id', $sectionId);
        return $query->get()->all();
    }

    public function report9Find(int $id): ?object
    {
        $this->authorizeReport9();
        $query = DB::table('report_9')->where('id', $id);
        $this->scopeReport9($query);
        $row = $query->first();
        if ($row === null) return null;
        $row->entries = Schema::hasTable('report_9_report')
            ? DB::table('report_9_report')->where('report_id', $id)->when(Schema::hasColumn('report_9_report', 'branch_id'), fn ($q) => $q->where('branch_id', $this->reportBranchId()))->orderBy('id')->get()
            : collect();
        $row->support_services = Schema::hasTable('report_9_support_services')
            ? DB::table('report_9_support_services')->where('report_id', $id)->when(Schema::hasColumn('report_9_support_services', 'branch_id'), fn ($q) => $q->where('branch_id', $this->reportBranchId()))->orderBy('id')->get()
            : collect();
        $row->period_record = Schema::hasTable('duty_period') ? DB::table('duty_period')->where('id', (int) ($row->period ?? 0))->first() : null;
        $row->place_record = Schema::hasTable('branches_area') ? DB::table('branches_area')->where('id', (int) ($row->rep_place ?? 0))->first() : null;
        $row->creator_record = Schema::hasTable('ra_users') ? DB::table('ra_users')->where('hr_id', (int) ($row->creator ?? 0))->first() : null;
        $row->attendance = Schema::hasTable('employees_attendance')
            ? DB::table('employees_attendance')->where('report_id', $id)
                ->when(Schema::hasColumn('employees_attendance', 'branch_id'), fn ($q) => $q->where('branch_id', $this->reportBranchId()))
                ->when(Schema::hasColumn('employees_attendance', 'companies_groups_id'), fn ($q) => $q->where('companies_groups_id', $this->companyId()))
                ->first()
            : null;

        foreach ($row->entries as $entry) {
            $entry->location_record = Schema::hasTable('branches_departments') ? DB::table('branches_departments')->where('id', (int) ($entry->location ?? 0))->first() : null;
            $entry->section_record = Schema::hasTable('rep9_section') ? DB::table('rep9_section')->where('id', (int) ($entry->section ?? 0))->first() : null;
            $entry->notice_record = Schema::hasTable('rep9_notice') ? DB::table('rep9_notice')->where('id', (int) ($entry->notice ?? 0))->first() : null;
            $entry->action_record = Schema::hasTable('rep9_actions') ? DB::table('rep9_actions')->where('id', (int) ($entry->action ?? 0))->first() : null;
        }
        foreach ($row->support_services as $entry) {
            $entry->maintenance_department_record = Schema::hasTable('support_services_departments') ? DB::table('support_services_departments')->where('id', (int) ($entry->maintenance_departments ?? 0))->first() : null;
            $entry->maintenance_type_record = Schema::hasTable('support_services') ? DB::table('support_services')->where('id', (int) ($entry->maintenance_type ?? 0))->first() : null;
            $entry->request_type_record = Schema::hasTable('maintenance_request_type') ? DB::table('maintenance_request_type')->where('id', (int) ($entry->maintenance_request_type ?? 0))->first() : null;
        }
        return $row;
    }

    /** The old employee report is a separate report family, without attendance. */
    public function employeeReport9List(array $filters = []): LengthAwarePaginator
    {
        $this->authorizeReport9();
        $this->tableOr404('report_emp_9');
        $query = DB::table('report_emp_9')->orderByDesc('id');
        $this->scopeReport9($query, 'report_emp_9');
        if (($filters['from'] ?? '') !== '' && Schema::hasColumn('report_emp_9', 'date')) $query->where('date', '>=', strtotime((string) $filters['from']));
        if (($filters['to'] ?? '') !== '' && Schema::hasColumn('report_emp_9', 'date')) $query->where('date', '<=', strtotime((string) $filters['to'].' 23:59:59'));
        $query->when((int) ($filters['period_id'] ?? 0) > 0 && Schema::hasColumn('report_emp_9', 'period'), fn ($q) => $q->where('period', (int) $filters['period_id']));
        $query->when((int) ($filters['creator'] ?? 0) > 0 && Schema::hasColumn('report_emp_9', 'creator'), fn ($q) => $q->where('creator', (int) $filters['creator']));
        $paginator = $query->paginate(15)->withQueryString();
        $paginator->setCollection($paginator->getCollection()->map(function (object $row): object {
            $row->period_record = Schema::hasTable('duty_period') ? DB::table('duty_period')->where('id', (int) ($row->period ?? 0))->first() : null;
            $row->place_record = Schema::hasTable('branches_area') ? DB::table('branches_area')->where('id', (int) ($row->rep_place ?? 0))->first() : null;
            $row->creator_record = Schema::hasTable('ra_users') ? DB::table('ra_users')->where('hr_id', (int) ($row->creator ?? 0))->first() : null;
            return $row;
        }));
        return $paginator;
    }

    public function employeeReport9Find(int $id): ?object
    {
        $this->authorizeReport9();
        $this->tableOr404('report_emp_9');
        $query = DB::table('report_emp_9')->where('id', $id);
        $this->scopeReport9($query, 'report_emp_9');
        $row = $query->first();
        if ($row === null) return null;

        $row->entries = Schema::hasTable('report_emp_9_report')
            ? DB::table('report_emp_9_report')->where('report_id', $id)->when(Schema::hasColumn('report_emp_9_report', 'branch_id'), fn ($q) => $q->where('branch_id', $this->reportBranchId()))->orderBy('id')->get()
            : collect();
        $row->support_services = Schema::hasTable('report_emp_9_support_services')
            ? DB::table('report_emp_9_support_services')->where('report_id', $id)->when(Schema::hasColumn('report_emp_9_support_services', 'branch_id'), fn ($q) => $q->where('branch_id', $this->reportBranchId()))->orderBy('id')->get()
            : collect();
        $row->period_record = Schema::hasTable('duty_period') ? DB::table('duty_period')->where('id', (int) ($row->period ?? 0))->first() : null;
        $row->place_record = Schema::hasTable('branches_area') ? DB::table('branches_area')->where('id', (int) ($row->rep_place ?? 0))->first() : null;
        $row->creator_record = Schema::hasTable('ra_users') ? DB::table('ra_users')->where('hr_id', (int) ($row->creator ?? 0))->first() : null;
        $row->attendance = null;

        foreach ($row->entries as $entry) {
            $entry->location_record = Schema::hasTable('branches_departments') ? DB::table('branches_departments')->where('id', (int) ($entry->location ?? 0))->first() : null;
            $entry->section_record = Schema::hasTable('rep9_section') ? DB::table('rep9_section')->where('id', (int) ($entry->section ?? 0))->first() : null;
            $entry->notice_record = Schema::hasTable('rep9_notice') ? DB::table('rep9_notice')->where('id', (int) ($entry->notice ?? 0))->first() : null;
            $entry->action_record = Schema::hasTable('rep9_actions') ? DB::table('rep9_actions')->where('id', (int) ($entry->action ?? 0))->first() : null;
        }
        foreach ($row->support_services as $entry) {
            $entry->maintenance_department_record = Schema::hasTable('support_services_departments') ? DB::table('support_services_departments')->where('id', (int) ($entry->maintenance_departments ?? 0))->first() : null;
            $entry->maintenance_type_record = Schema::hasTable('support_services') ? DB::table('support_services')->where('id', (int) ($entry->maintenance_type ?? 0))->first() : null;
            $entry->request_type_record = Schema::hasTable('maintenance_request_type') ? DB::table('maintenance_request_type')->where('id', (int) ($entry->maintenance_request_type ?? 0))->first() : null;
        }

        return $row;
    }

    /** @param array<string, mixed> $data @param array<string, mixed> $files */
    public function employeeReport9Save(array $data, array $files = [], ?int $id = null): int
    {
        $this->authorizeReport9();
        $this->tableOr404('report_emp_9');
        abort_unless((int) ($data['period_id'] ?? $data['period'] ?? 0) > 0, 422, 'يجب اختيار الفترة.');
        $columns = Schema::getColumnListing('report_emp_9');
        $values = array_intersect_key([
            'period' => (int) ($data['period_id'] ?? $data['period'] ?? 0),
            'date' => is_numeric($data['date'] ?? null) ? (int) $data['date'] : strtotime((string) ($data['date'] ?? now()->toDateString())),
            'branch_id' => $this->reportBranchId(),
            'companies_groups_id' => $this->companyId(),
            'created_at' => now(),
            'creator' => (int) session('hr_user_id', 0),
            'publish' => 1,
            'rep_place' => (int) ($data['rep_place'] ?? 0),
        ], array_flip($columns));

        return (int) DB::transaction(function () use ($id, $values, $data, $files): int {
            if ($id === null) {
                $reportId = (int) DB::table('report_emp_9')->insertGetId($values);
            } else {
                abort_if($this->employeeReport9Find($id) === null, 404);
                $updates = $values;
                unset($updates['branch_id'], $updates['companies_groups_id'], $updates['creator'], $updates['created_at']);
                if (Schema::hasColumn('report_emp_9', 'updator')) $updates['updator'] = (int) session('hr_user_id', 0);
                if (Schema::hasColumn('report_emp_9', 'updated_at')) $updates['updated_at'] = now();
                DB::table('report_emp_9')->where('id', $id)->update($updates);
                $reportId = $id;
            }
            $this->replaceReport9Children($reportId, $data, $files, 'report_emp_9_report', 'report_emp_9_support_services');
            return $reportId;
        });
    }

    public function employeeReport9Delete(int $id): void
    {
        abort_if($this->employeeReport9Find($id) === null, 404);
        DB::transaction(function () use ($id): void {
            foreach (['report_emp_9_report', 'report_emp_9_support_services'] as $table) {
                if (Schema::hasTable($table)) DB::table($table)->where('report_id', $id)->delete();
            }
            DB::table('report_emp_9')->where('id', $id)->delete();
        });
    }

    /** @param array<string, mixed> $data @param array<string, mixed> $files */
    public function report9Save(array $data, array $files = [], ?int $id = null): int
    {
        $this->authorizeReport9();
        $this->tableOr404('report_9');
        abort_unless((int) ($data['period_id'] ?? $data['period'] ?? 0) > 0, 422, 'يجب اختيار الفترة.');
        $columns = Schema::getColumnListing('report_9');
        $values = array_intersect_key([
            'period' => (int) ($data['period_id'] ?? $data['period'] ?? 0),
            'date' => is_numeric($data['date'] ?? null) ? (int) $data['date'] : strtotime((string) ($data['date'] ?? now()->toDateString())),
            'branch_id' => $this->reportBranchId(),
            'companies_groups_id' => $this->companyId(),
            'created_at' => now(),
            'creator' => (int) session('hr_user_id', 0),
            'publish' => 1,
            'rep_place' => (int) ($data['rep_place'] ?? 0),
        ], array_flip($columns));
        return (int) DB::transaction(function () use ($id, $values, $data, $files): int {
            if ($id === null) $reportId = (int) DB::table('report_9')->insertGetId($values);
            else {
                abort_if($this->report9Find($id) === null, 404);
                $updates = $values;
                unset($updates['branch_id'], $updates['companies_groups_id'], $updates['creator'], $updates['created_at']);
                if (Schema::hasColumn('report_9', 'updator')) $updates['updator'] = (int) session('hr_user_id', 0);
                if (Schema::hasColumn('report_9', 'updated_at')) $updates['updated_at'] = now();
                DB::table('report_9')->where('id', $id)->update($updates);
                $reportId = $id;
            }
            $this->replaceReport9Children($reportId, $data, $files);
            $this->replaceReport9Attendance($reportId, $data);
            return $reportId;
        });
    }

    public function report9Delete(int $id): void
    {
        abort_if($this->report9Find($id) === null, 404);
        DB::transaction(function () use ($id): void {
            foreach (['report_9_report', 'report_9_support_services', 'employees_attendance'] as $table) if (Schema::hasTable($table)) DB::table($table)->where('report_id', $id)->delete();
            DB::table('report_9')->where('id', $id)->delete();
        });
    }

    /** @param array<string, mixed> $data @param array<string, mixed> $files */
    private function replaceReport9Children(
        int $reportId,
        array $data,
        array $files,
        string $entriesTable = 'report_9_report',
        string $supportTable = 'report_9_support_services',
    ): void
    {
        if (Schema::hasTable($entriesTable)) {
            DB::table($entriesTable)->where('report_id', $reportId)->delete();
            foreach ((array) ($data['entries'] ?? []) as $index => $entry) {
                if (! is_array($entry)) continue;
                if (trim((string) ($entry['filenumber'] ?? '')) === '') continue;
                $columns = Schema::getColumnListing($entriesTable);
                $row = array_intersect_key([
                    'report_id' => $reportId, 'date' => is_numeric($entry['date'] ?? null) ? (int) $entry['date'] : strtotime((string) ($entry['date'] ?? now()->toDateString())), 'branch_id' => $this->reportBranchId(),
                    'filenumber' => trim((string) ($entry['filenumber'] ?? '')), 'location' => trim((string) ($entry['location'] ?? '')),
                    'room_bod_number' => trim((string) ($entry['room_bod_number'] ?? '')), 'section' => (int) ($entry['section'] ?? 0),
                    'notice' => (int) ($entry['notice'] ?? 0), 'action' => (int) ($entry['action'] ?? 0), 'other' => trim((string) ($entry['other'] ?? '')),
                ], array_flip($columns));
                if (isset($files['entries'][$index]['file']) && is_object($files['entries'][$index]['file'])) {
                    $row['files'] = $files['entries'][$index]['file']->store('admission-inpatient/report9');
                } elseif (trim((string) ($entry['existing_file'] ?? '')) !== '' && in_array('files', $columns, true)) {
                    $row['files'] = trim((string) $entry['existing_file']);
                }
                DB::table($entriesTable)->insert($row);
            }
        }
        if (Schema::hasTable($supportTable)) {
            DB::table($supportTable)->where('report_id', $reportId)->delete();
            foreach ((array) ($data['support_services'] ?? []) as $index => $entry) {
                if (! is_array($entry)) continue;
                if (trim((string) ($entry['maintenance_departments'] ?? '')) === '') continue;
                $columns = Schema::getColumnListing($supportTable);
                DB::table($supportTable)->insert(array_intersect_key([
                    'report_id' => $reportId, 'date' => is_numeric($entry['date'] ?? null) ? (int) $entry['date'] : strtotime((string) ($entry['date'] ?? now()->toDateString())), 'branch_id' => $this->reportBranchId(),
                    'maintenance_departments' => trim((string) ($entry['maintenance_departments'] ?? '')), 'maintenance_type' => trim((string) ($entry['maintenance_type'] ?? '')),
                    'maintenance_request_type' => trim((string) ($entry['maintenance_request_type'] ?? '')), 'description' => trim((string) ($entry['description'] ?? '')),
                    'files' => isset($files['support_services'][$index]['file']) && is_object($files['support_services'][$index]['file'])
                        ? $files['support_services'][$index]['file']->store('admission-inpatient/report9')
                        : trim((string) ($entry['existing_file'] ?? '')),
                ], array_flip($columns)));
            }
        }
    }

    /** @param array<string, mixed> $data */
    private function replaceReport9Attendance(int $reportId, array $data): void
    {
        if (! Schema::hasTable('employees_attendance')) {
            return;
        }

        $columns = Schema::getColumnListing('employees_attendance');
        $values = array_intersect_key([
            'period' => (int) ($data['period_id'] ?? $data['period'] ?? 0),
            'date' => is_numeric($data['date'] ?? null) ? (int) $data['date'] : strtotime((string) ($data['date'] ?? now()->toDateString())),
            'branch_id' => $this->reportBranchId(),
            'attendees' => max(0, (int) ($data['attendees'] ?? 0)),
            'absence' => max(0, (int) ($data['absence'] ?? 0)),
            'latecomers' => max(0, (int) ($data['latecomers'] ?? 0)),
            'permissible' => max(0, (int) ($data['permissible'] ?? 0)),
            'companies_groups_id' => $this->companyId(),
            'creator' => (int) session('hr_user_id', 0),
            'report_id' => $reportId,
        ], array_flip($columns));

        DB::table('employees_attendance')->where('report_id', $reportId)->delete();
        DB::table('employees_attendance')->insert($values);
    }

    private function tableOr404(string $table): void
    {
        abort_unless(Schema::hasTable($table), 404);
    }

    /**
     * Read legacy lookup tables defensively: older installations do not all
     * carry the later publish/branch columns.
     *
     * @param array<string, int> $filters
     */
    private function publishedRows(string $table, string $orderBy = 'name_ar', array $filters = []): mixed
    {
        if (! Schema::hasTable($table)) return collect();

        $columns = Schema::getColumnListing($table);
        $query = DB::table($table);
        foreach ($filters as $column => $value) {
            if (in_array($column, $columns, true)) $query->where($column, $value);
        }
        if (in_array('publish', $columns, true)) $query->where('publish', 1);
        $query->orderBy(in_array($orderBy, $columns, true) ? $orderBy : 'id');

        return $query->get();
    }

    private function report9Employees(): mixed
    {
        if (! Schema::hasTable('ra_users')) return collect();

        $columns = Schema::getColumnListing('ra_users');
        $query = DB::table('ra_users');
        foreach (['branch_id' => $this->reportBranchId(), 'companies_groups_id' => $this->companyId(), 'hr_user_level' => 1] as $column => $value) {
            if (in_array($column, $columns, true)) $query->where($column, $value);
        }
        return $query->orderBy(in_array('hr_first_name', $columns, true) ? 'hr_first_name' : 'id')->get();
    }

    private function scope($query, string $table): void
    {
        $columns = Schema::getColumnListing($table);
        if (in_array('branch_id', $columns, true)) $query->where($table.'.branch_id', $this->branchId());
        if (in_array('companies_groups_id', $columns, true)) $query->where($table.'.companies_groups_id', $this->companyId());
    }

    private function scopeReport9($query, string $table = 'report_9'): void
    {
        $query->where($table.'.branch_id', $this->reportBranchId())->where($table.'.companies_groups_id', $this->companyId());
    }

    private function reportBranchId(): int
    {
        // Old admin and branch report-9 entry points both represented the
        // inpatient section with branch_id=9, independent of the logged-in
        // operator's current branch.
        return 9;
    }

    private function branchId(): int { return (int) session('hr_branch_id', 0); }
    private function companyId(): int { return (int) session('companies_groups_id', 0); }
}
