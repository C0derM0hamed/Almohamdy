<?php

namespace App\Http\Controllers\Module\LegacySidebar;

use App\Http\Controllers\Controller;
use App\Services\LegacySidebarPageService;
use App\Services\Pdf\ArabicPdfService;
use App\Services\Sms\SmsGateway;
use App\Services\Auth\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LegacySidebarPageController extends Controller
{
    public function __construct(private readonly LegacySidebarPageService $service, private readonly ArabicPdfService $pdf, private readonly SmsGateway $sms, private readonly PermissionService $permissions) {}

    public function index(Request $request, string $page): View
    {
        $this->guard($page);
        $spec = $this->service->spec($page);
        $filters = $request->query();
        // Preserve the old case URLs, which used statusId/mobile, while the
        // rebuilt screens use the clearer status/search parameter names.
        if (($filters['status'] ?? '') === '' && ($filters['statusId'] ?? '') !== '') {
            $filters['status'] = $filters['statusId'];
        }
        if (($filters['search'] ?? '') === '' && ($filters['mobile'] ?? '') !== '') {
            $filters['search'] = $filters['mobile'];
        }
        $search = trim((string) ($filters['search'] ?? $filters['keyword'] ?? ''));

        return view('legacy-sidebar.index', [
            'page' => $page,
            'spec' => $spec,
            'rows' => $this->service->list($page, $search, $filters),
            'columns' => $this->service->columns($page),
            'available' => $this->service->available($page),
            'search' => $search,
            'supportsAttachments' => $this->service->supportsAttachments($page),
            'supportsRequiredDocuments' => $this->service->supportsRequiredDocuments($page),
            'options' => $this->service->options($page),
            'caseStatuses' => $this->service->caseStatuses($page),
            'caseDashboard' => $this->service->caseDashboard($page, $search, $filters),
        ]);
    }

    public function create(string $page): View
    {
        $this->guard($page);
        $spec = $this->service->spec($page);
        abort_unless($spec['create'] && $spec['fields'] !== [] && $this->service->available($page), 404);

        return view('legacy-sidebar.form', [
            'page' => $page,
            'spec' => $spec,
            'columns' => $this->service->columns($page),
            'row' => null,
            'options' => $this->service->options($page),
            'workflowState' => null,
            'defaults' => $this->service->defaults($page),
        ]);
    }

    public function compose(string $page): View
    {
        $this->guard($page);
        abort_unless($this->service->spec($page)['mode'] === 'sms', 404);

        return view('legacy-sidebar.sms-form', ['page' => $page, 'spec' => $this->service->spec($page), 'senders' => $this->service->smsSenders()]);
    }

    public function sendSms(Request $request, string $page): RedirectResponse
    {
        $this->guard($page);
        $senders = $this->service->smsSenders();
        $data = $request->validate([
            'mobile' => ['required', 'string', 'max:10000'],
            'message' => ['required', 'string', 'max:1000'],
            'sender' => ['nullable', 'string', Rule::in(array_keys($senders))],
            'type' => ['nullable', 'integer', 'min:0'],
            'language' => ['nullable', 'integer', Rule::in([1, 2])],
        ]);
        $count = $this->service->sendSms($page, $data, $this->sms);

        return redirect()->route('modules.legacy-sidebar.index', $page)->with('success', 'تم إرسال/تسجيل '.$count.' رسالة.');
    }

    public function store(Request $request, string $page): RedirectResponse
    {
        $this->guard($page);
        $spec = $this->service->spec($page);
        $data = $this->normalise($request->validate($this->rules($spec['fields'], $page, true)), $page);
        $id = $this->service->save($page, $data);
        if (in_array($page, ['rep_ss', 'sit_rep2'], true)) {
            $this->service->uploadAttachments($page, $id, $request->file('fileToUpload', []), $request->input('filename', []));
        }
        if ($page === 'medical_approval_notifications') {
            $this->service->logMedicalApproval($id, (int) $data['medical_approval_status_id'], isset($data['rejection_reason_id']) ? (int) $data['rejection_reason_id'] : null, $data['notes'] ?? null);
        }
        if ($page === 'financial_claim_notice') {
            $result = $this->sms->send((string) $data['mobile'], (string) $data['content']);
            abort_unless($result['ok'], 502, 'تم حفظ الإشعار، لكن تعذر إرسال رسالة الجوال.');
            $this->service->archiveSms((string) $data['mobile'], (string) $data['content']);
        }

        return redirect()->route('modules.legacy-sidebar.index', $page)->with('success', 'تم حفظ البيانات بنجاح.');
    }

    public function show(string $page, int $item): View
    {
        $this->guard($page);
        return view('legacy-sidebar.show', [
            'page' => $page,
            'spec' => $this->service->spec($page),
            'row' => $this->service->find($page, $item),
            'columns' => $this->service->columns($page),
            'attachments' => $this->service->attachments($page, $item),
            'requiredDocuments' => $this->service->requiredDocuments($page, $item),
            'supportsAttachments' => $this->service->supportsAttachments($page),
            'supportsRequiredDocuments' => $this->service->supportsRequiredDocuments($page),
            'workflowHistory' => $this->service->workflowHistory($page, $item),
            'options' => $this->service->options($page),
            'caseStatuses' => $this->service->caseStatuses($page),
            'caseStatements' => $this->service->caseStatements($page, $item),
            'canReplyToCaseStatements' => $this->service->canReplyToCaseStatements($page),
        ]);
    }

    public function edit(string $page, int $item): View
    {
        $this->guard($page);
        $spec = $this->service->spec($page);
        abort_unless($spec['fields'] !== [], 404);

        return view('legacy-sidebar.form', [
            'page' => $page,
            'spec' => $spec,
            'columns' => $this->service->columns($page),
            'row' => $this->service->find($page, $item),
            'options' => $this->service->options($page),
            'workflowState' => $this->service->workflowState($page, $item),
            'defaults' => $this->service->defaults($page),
        ]);
    }

    public function update(Request $request, string $page, int $item): RedirectResponse
    {
        $this->guard($page);
        $spec = $this->service->spec($page);
        $data = $this->normalise($request->validate($this->rules($spec['fields'], $page, false)), $page);
        $this->service->save($page, $data, $item);
        if (in_array($page, ['rep_ss', 'sit_rep2'], true)) {
            $this->service->uploadAttachments($page, $item, $request->file('fileToUpload', []), $request->input('filename', []));
        }
        if ($page === 'medical_approval_notifications' && array_key_exists('medical_approval_status_id', $data)) {
            $this->service->logMedicalApproval($item, (int) $data['medical_approval_status_id'], isset($data['rejection_reason_id']) ? (int) $data['rejection_reason_id'] : null, $data['notes'] ?? null);
        }

        return redirect()->route('modules.legacy-sidebar.index', $page)->with('success', 'تم تحديث البيانات بنجاح.');
    }

    public function toggle(string $page, int $item): RedirectResponse
    {
        $this->guard($page);
        $this->service->toggle($page, $item);

        return back()->with('success', 'تم تغيير الحالة.');
    }

    public function destroy(string $page, int $item): RedirectResponse
    {
        $this->guard($page);
        $this->service->delete($page, $item);

        return back()->with('success', 'تم حذف السجل.');
    }

    public function action(Request $request, string $page, int $item, string $action): RedirectResponse
    {
        $this->guard($page);
        $reason = $action === 'reject' ? $request->validate(['reason' => ['required', 'string', 'max:2000']])['reason'] : null;
        if ($page === 'medical_approval_notifications' && $action === 'send') {
            $delivery = $this->service->medicalApprovalDeliveryData($item);
            abort_if($delivery['mobiles'] === [] && $delivery['emails'] === [], 422, 'لا يوجد مستلمون نشطون لقسم التحصيل أو النسخ في هذه الشركة.');
            $notification = $delivery['notification'];
            $message = 'إشعار موافقة طبية - المريض: '.($notification->patient_name ?? '').' - الهوية: '.($notification->patient_identity ?? '').' - الغرفة: '.($notification->room_number ?? '');
            foreach ($delivery['mobiles'] as $mobile) {
                $result = $this->sms->send($mobile, $message);
                abort_unless($result['ok'], 502, 'تعذر إرسال إشعار التحصيل إلى أحد أرقام الجوال.');
                $this->service->archiveSms($mobile, $message);
            }
            foreach ($delivery['emails'] as $email) {
                Mail::raw($message, fn ($mail) => $mail->to($email)->subject('إشعار موافقة طبية للتحصيل'));
            }
        }
        $this->service->action($page, $item, $action, $reason);

        return back()->with('success', 'تم تنفيذ العملية بنجاح.');
    }

    public function status(Request $request, string $page, int $item): RedirectResponse
    {
        $this->guard($page);
        abort_unless(in_array($page, ['rep_ss', 'sit_rep2'], true), 404);
        $data = $request->validate([
            'status_order' => ['required', 'integer', 'in:1,2,3,4'],
            'becuse' => ['nullable', 'string', 'max:4000'],
            'send_Section' => ['nullable', 'integer', 'min:1'],
        ]);
        $this->service->updateRequestStatus($page, $item, (int) $data['status_order'], $data['becuse'] ?? null, isset($data['send_Section']) ? (int) $data['send_Section'] : null);

        return back()->with('success', 'تم تحديث حالة الطلب.');
    }

    public function storeCaseAction(Request $request, string $page, int $item): RedirectResponse
    {
        $this->guard($page);
        $data = $request->validate([
            'status_id' => ['required', 'integer', Rule::in(array_keys($this->service->caseStatuses($page)))],
            'details' => ['nullable', 'string', 'max:4000'],
            'request_number' => ['nullable', 'string', 'max:100'],
            'applicant' => ['nullable', 'string', 'max:255'],
            'request_date' => ['nullable', 'date'],
            'case_number' => ['nullable', 'string', 'max:100'],
            'sessions_number' => ['nullable', 'string', 'max:100'],
            'session_summary' => ['nullable', 'string', 'max:4000'],
            'judgment_instrument' => ['nullable', 'string', 'max:255'],
            'sessions_date' => ['nullable', 'date'],
            'next_sessions_date' => ['nullable', 'date'],
            'request_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:15360'],
            'session_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:15360'],
        ]);
        $this->service->addCaseAction($page, $item, $data, $request->allFiles());

        return back()->with('success', 'تمت إضافة إجراء القضية وتحديث حالتها.');
    }

    public function storeCaseStatement(Request $request, string $page, int $item): RedirectResponse
    {
        $this->guard($page);
        $data = $request->validate([
            'details' => ['required', 'string', 'max:4000'],
            'summary' => ['nullable', 'string', 'max:4000'],
            'section' => ['nullable', 'integer', 'min:1'],
            'file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:15360'],
        ]);
        $this->service->addCaseStatement($page, $item, $data, $request->file('file'));

        return back()->with('success', 'تم تسجيل طلب الإفادة.');
    }

    public function replyCaseStatement(Request $request, string $page, int $item, int $statement): RedirectResponse
    {
        $this->guard($page);
        $data = $request->validate([
            'reply' => ['required', 'string', 'max:4000'],
            'file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:15360'],
        ]);
        $this->service->replyCaseStatement($page, $item, $statement, $data, $request->file('file'));

        return back()->with('success', 'تم تسجيل الإفادة والرد.');
    }

    public function downloadCaseAction(string $page, int $item, int $caseAction, string $kind): BinaryFileResponse
    {
        $this->guard($page);

        return response()->download($this->service->downloadCaseActionFile($page, $item, $caseAction, $kind));
    }

    public function uploadAttachment(Request $request, string $page, int $item): RedirectResponse
    {
        $this->guard($page);
        $file = $request->validate(['attachment' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx', 'max:15360']])['attachment'];
        $this->service->uploadAttachment($page, $item, $file);

        return back()->with('success', 'تم رفع المرفق.');
    }

    public function downloadAttachment(string $page, int $item, int $attachment): BinaryFileResponse
    {
        $this->guard($page);
        return response()->download($this->service->downloadAttachment($page, $item, $attachment));
    }

    public function uploadRequiredDocument(Request $request, string $page, int $item, int $document): RedirectResponse
    {
        $this->guard($page);
        $file = $request->validate(['attachment' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx', 'max:15360']])['attachment'];
        $this->service->uploadRequiredDocument($page, $item, $document, $file);

        return back()->with('success', 'تم رفع المستند وتحديث حالة الاستكمال.');
    }

    public function downloadRequiredDocument(string $page, int $item, int $document): BinaryFileResponse
    {
        $this->guard($page);
        return response()->download($this->service->downloadRequiredDocument($page, $item, $document));
    }

    public function pdf(string $page, int $item)
    {
        $this->guard($page);
        abort_unless(in_array($page, [
            'medica_report',
            'rep_ss',
            'sit_rep2',
            'financial_claim_notice',
            'archives',
            'lawsuit_complete_documents',
            'executive_title',
            'executive_title_complete_documents',
            'lawsuitapproval',
            'administrative_cases',
            'commercial_cases',
            'labor_cases',
            'medical_cases',
        ], true), 404);

        if ($page !== 'medica_report') {
            return $this->pdf->loadView('legacy-sidebar.financial-request-pdf', [
                'record' => $this->service->find($page, $item),
                'page' => $page,
                'attachments' => $this->service->attachments($page, $item),
                'history' => $this->service->workflowHistory($page, $item),
            ])->stream($page.'-'.$item.'.pdf');
        }

        return $this->pdf->loadView('legacy-office.medical-report-pdf', ['record' => $this->service->find($page, $item)])->stream('medical-report-'.$item.'.pdf');
    }

    private function rules(array $fields, string $page, bool $creating = true): array
    {
        $rules = [];
        if ($page === 'emergency_new_call') {
            $fields[] = 'responsibles';
        }
        if ($page === 'medical_approval_notifications') {
            $fields[] = 'medical_approval_status_id';
            $fields[] = 'rejection_reason_id';
        }
        $required = match ($page) {
            'medica_report' => ['patient_name', 'nationality', 'file_number', 'entry_date', 'exit_date', 'medical_diagnosis', 'treatment', 'recommendation', 'report_type'],
            'birth_notification' => ['newborn_file_number', 'mother_file_number', 'gender', 'newborn_type', 'newborn_status', 'birth_status', 'birth_notification_obstetrics', 'language'],
            'financial_claim_notice' => ['mobile', 'contract_type', 'file_number', 'patient_name', 'patient_id_number', 'amount_due', 'content'],
            'administrative_cases' => ['claim_amount'],
            'commercial_cases' => ['commercial_cases_payment_type_id', 'claimant_name', 'defendant_name', 'claim_amount'],
            'labor_cases' => ['labor_cases_payment_type_id', 'claimant_name', 'defendant_name', 'claim_amount'],
            'medical_cases' => ['claimant_name', 'defendant_name', 'specialty'],
            'medical_approval_notifications' => ['patient_name', 'patient_identity', 'clinician_id', 'medical_approval_status_id'],
            'executive_title' => ['patient_name'],
            'rep_ss' => ['name', 'no_file', 'service', 'Paymentـstatus', 'Patientـname', 'dateOut', 'dateIn', 'countries', 'branches_departments', 'onid', 'details'],
            'sit_rep2' => ['name', 'onid', 'no_lawsuit', 'Paymentـstatus', 'lawsuit_date', 'send_Section', 'note'],
            'adm_country' => ['name_ar', 'name_en'],
            'archives' => ['name', 'no_id', 'no_lawsuit', 'Paymentـstatus', 'lawsuit_date', 'note'],
            'branches_emails' => ['branches_id'],
            'central_follow_up' => ['name', 'caller_name', 'caller_section', 'ext_number'],
            'centralsections' => ['name_ar'],
            'city' => ['country_id', 'name_ar', 'name_ch'],
            'central_ext' => ['floor_id', 'section_id'],
            'emergency_new_call' => ['period', 'location', 'code', 'code_reason'],
            'info' => ['name', 'info'],
            'lawsuit_users_mobile' => ['name_ar', 'mobile'],
            'psychosocial_assessment_all' => ['the_name', 'gender', 'file_no', 'room_no', 'religion', 'nationality', 'education_level', 'id_no', 'age', 'address', 'occupation', 'mobile_no', 'medical_diagnosis', 'room_type'],
            'sanad_reg' => ['first_no', 'last_no', 'branch'],
            'sanad_track1' => ['sanad_no', 'branch'],
            'shift_schedule' => ['eventTitle', 'eventStartDate', 'eventEndDate'],
            default => [],
        };
        foreach ($fields as $field) {
            $rules[$field] = [
                in_array($field, $required, true) ? 'required' : 'nullable',
                str_ends_with($field, '_id') || in_array($field, ['gender', 'language', 'status', 'period', 'location', 'code', 'code_reason', 'age', 'family_number', 'hospitalization_days', 'eventTitle', 'eventLocation', 'branch', 'first_no', 'last_no', 'paid', 'sanad_no', 'contract_type', 'report_type', 'newborn_type', 'newborn_status', 'birth_status', 'room_type', 'branches_departmentsid', 'mainsection', 'subsections', 'employeeId', 'sen', 'type', 'lang', 'loca', 'Section', 'depart', 'employee'], true) ? 'integer' : 'string',
                str_ends_with($field, '_id') ? 'min:1' : 'max:20000',
            ];
        }

        foreach ($this->service->options($page) as $field => $options) {
            if (isset($rules[$field]) && $options !== []) {
                $rules[$field][] = Rule::in(array_keys($options));
            }
        }
        if (isset($rules['mobile'])) {
            $rules['mobile'][] = 'regex:/^\+?[0-9\s-]{9,20}$/';
        }

        if (in_array($page, ['rep_ss', 'sit_rep2'], true)) {
            $rules['fileToUpload'] = [$creating ? 'required' : 'nullable', 'array', 'min:1'];
            $rules['fileToUpload.*'] = ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:15360'];
            $rules['filename'] = [$creating ? 'required' : 'nullable', 'array', 'min:1'];
            $rules['filename.*'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    private function normalise(array $data, string $page): array
    {
        if ($page === 'financial_claim_notice' && isset($data['content'])) {
            $branchName = DB::table('branches')->where('id', (int) session('hr_branch_id'))->value('name_ar');
            $data['content'] = str_replace('[branch]', trim((string) $branchName), (string) $data['content']);
        }
        if ($page === 'medical_approval_notifications') {
            foreach (['clinician_id', 'inpatient_clinician_id', 'medical_approval_cc_ids'] as $field) {
                if (array_key_exists($field, $data) && is_array($data[$field])) {
                    $data[$field] = implode(',', array_filter($data[$field], static fn ($value): bool => trim((string) $value) !== ''));
                }
            }
        }

        return $data;
    }

    private function guard(string $page): void
    {
        if (in_array($page, ['adm_country', 'city', 'onlinetody', 'sanad_reg', 'sanad_track1'], true)) {
            abort_unless($this->permissions->isAdmin(), 403);
        }
    }
}
