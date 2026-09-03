<?php

namespace App\Http\Controllers\Module\AdmissionInpatient;

use App\Http\Controllers\Controller;
use App\Services\AdmissionInpatient\AdmissionInpatientService;
use App\Services\AdmissionInpatient\InpatientWorkflowService;
use App\Services\Pdf\ArabicPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AdmissionInpatientController extends Controller
{
    public function __construct(
        private readonly AdmissionInpatientService $admission,
        private readonly InpatientWorkflowService $workflow,
        private readonly ArabicPdfService $pdf,
    ) {}

    public function calculatorIndex(Request $request, string $type): View
    {
        $filters = [
            'file_number' => trim((string) $request->query('file_number', '')),
            'from' => trim((string) $request->query('from', '')),
            'to' => trim((string) $request->query('to', '')),
            'user_id' => (int) $request->query('user_id', 0),
            'room_type' => (int) $request->query('room_type', 0),
        ];
        return view('admission-inpatient.calculator.index', [
            'type' => $type,
            'records' => $this->admission->calculatorList($type, $filters),
            'filters' => $filters,
            'options' => $this->admission->calculatorOptions($type),
            'homeRoute' => 'branch.dashboard',
        ]);
    }

    public function calculatorCreate(string $type, string $mode = 'direct'): View
    {
        abort_unless(in_array($mode, ['direct', 'procedures', 'observation'], true), 404);
        return view('admission-inpatient.calculator.form', $this->admission->calculatorOptions($type, $mode) + [
            'type' => $type,
            'mode' => $mode,
            'row' => null,
            'homeRoute' => 'branch.dashboard',
        ]);
    }

    /** Compatibility screen for branch/admission calculator.php.
     *
     * That legacy page was a preview-only calculator selected by admission
     * status; it did not create a calculator record. Keep that distinction
     * while using the same domain calculation as the stored screens.
     */
    public function calculatorPreviewForm(Request $request): View
    {
        $status = $request->integer('admission_status_id', 2);
        $mode = $status === 1 ? 'observation' : 'procedures';

        return view('admission-inpatient.calculator.preview', $this->admission->calculatorOptions('standard', $mode) + [
            'status' => $status,
            'mode' => $mode,
            'result' => null,
            'input' => [],
            'homeRoute' => 'branch.dashboard',
        ]);
    }

    public function calculatorPreview(Request $request): View
    {
        $data = $request->validate([
            'admission_status_id' => ['required', 'integer', 'in:1,2'],
            'nationality' => ['required', 'integer', 'min:1'],
            'room' => ['required', 'integer', 'min:1'],
            'days' => ['required', 'integer', 'min:1'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:50'],
            'procedurs' => ['nullable', 'array'],
            'procedurs.*' => ['integer', 'min:1'],
        ]);
        $status = (int) $data['admission_status_id'];
        $mode = $status === 1 ? 'observation' : 'procedures';
        $result = $this->admission->calculate($data, $mode);

        return view('admission-inpatient.calculator.preview', $this->admission->calculatorOptions('standard', $mode) + [
            'status' => $status,
            'mode' => $mode,
            'result' => $result,
            'input' => $data,
            'homeRoute' => 'branch.dashboard',
        ]);
    }

    public function calculatorStore(Request $request, string $type, string $mode = 'direct'): RedirectResponse
    {
        $this->normalizeLegacyCalculatorFields($request);
        $data = $request->validate($this->calculatorRules($type, $mode));
        $id = $this->admission->calculatorStore($type, $data, $mode);
        return redirect()->route('modules.admission-inpatient.calculator.show', [$type, $id])->with('success', 'تم حفظ تسعيرة التنويم بنجاح.');
    }

    public function calculatorShow(string $type, int $id): View
    {
        $row = $this->admission->calculatorFind($type, $id);
        abort_if($row === null, 404);
        return view('admission-inpatient.calculator.show', ['record' => $row, 'type' => $type, 'homeRoute' => 'branch.dashboard']);
    }

    public function calculatorEdit(string $type, int $id): View
    {
        $row = $this->admission->calculatorFind($type, $id);
        abort_if($row === null, 404);
        $mode = $this->calculatorMode($type, $row);
        return view('admission-inpatient.calculator.form', $this->admission->calculatorOptions($type, $mode) + [
            'type' => $type, 'mode' => $mode, 'row' => $row, 'homeRoute' => 'branch.dashboard',
        ]);
    }

    public function calculatorUpdate(Request $request, string $type, int $id): RedirectResponse
    {
        $row = $this->admission->calculatorFind($type, $id);
        abort_if($row === null, 404);
        $mode = $this->calculatorMode($type, $row);
        $this->normalizeLegacyCalculatorFields($request);
        $this->admission->calculatorUpdate($type, $id, $request->validate($this->calculatorRules($type, $mode)), $mode);
        return redirect()->route('modules.admission-inpatient.calculator.show', [$type, $id])->with('success', 'تم تحديث التسعيرة.');
    }

    public function calculatorDestroy(string $type, int $id): RedirectResponse
    {
        $this->admission->calculatorDelete($type, $id);
        return redirect()->route('modules.admission-inpatient.calculator.index', $type)->with('success', 'تم حذف التسعيرة.');
    }

    public function calculatorPdf(string $type, int $id): mixed
    {
        $row = $this->admission->calculatorFind($type, $id);
        abort_if($row === null, 404);
        return $this->pdf->loadView('admission-inpatient.calculator.pdf', ['record' => $row, 'type' => $type])->setPaper('a4')->download('admission-calculator-'.$id.'.pdf');
    }

    public function calculatorSms(Request $request, string $type, int $id): RedirectResponse
    {
        $data = $request->validate(['mobile' => ['required', 'string', 'regex:/^\+?[0-9\s-]{9,20}$/'], 'language' => ['nullable', 'in:ar,en']]);
        $this->admission->sendCalculatorSms($type, $id, $data['mobile'], $data['language'] ?? 'ar');
        return back()->with('success', 'تم إرسال رابط التسعيرة إلى الجوال.');
    }

    public function referenceIndex(Request $request, string $type): View
    {
        $spec = $this->admission->referenceSpec($type);
        return view('admission-inpatient.reference.index', [
            'type' => $type, 'spec' => $spec,
            'rows' => $this->admission->referenceList($type, trim((string) $request->query('search', '')), $request->integer('status_id') ?: null),
            'options' => $this->admission->referenceOptions($type),
            'search' => trim((string) $request->query('search', '')),
            'homeRoute' => 'branch.dashboard',
        ]);
    }

    public function referenceCreate(string $type): View
    {
        return view('admission-inpatient.reference.form', [
            'type' => $type, 'spec' => $this->admission->referenceSpec($type), 'row' => null,
            'options' => $this->admission->referenceOptions($type), 'homeRoute' => 'branch.dashboard',
        ]);
    }

    public function referenceStore(Request $request, string $type): RedirectResponse
    {
        $this->admission->referenceSave($type, $request->validate($this->referenceRules($type)));
        return redirect()->route('modules.admission-inpatient.reference.index', $type)->with('success', 'تم حفظ البيانات المرجعية.');
    }

    public function referenceEdit(string $type, int $reference): View
    {
        return view('admission-inpatient.reference.form', [
            'type' => $type, 'spec' => $this->admission->referenceSpec($type), 'row' => $this->admission->referenceFind($type, $reference),
            'options' => $this->admission->referenceOptions($type), 'homeRoute' => 'branch.dashboard',
        ]);
    }

    public function referenceUpdate(Request $request, string $type, int $reference): RedirectResponse
    {
        $this->admission->referenceSave($type, $request->validate($this->referenceRules($type)), $reference);
        return redirect()->route('modules.admission-inpatient.reference.index', $type)->with('success', 'تم تحديث البيانات المرجعية.');
    }

    public function referenceToggle(string $type, int $reference): RedirectResponse
    {
        $this->admission->referenceToggle($type, $reference);
        return back()->with('success', 'تم تغيير حالة النشر.');
    }

    public function referenceDestroy(string $type, int $reference): RedirectResponse
    {
        $this->admission->referenceDelete($type, $reference);
        return back()->with('success', 'تم حذف السجل.');
    }

    public function referenceImport(Request $request, string $type): RedirectResponse
    {
        abort_unless($type === 'service-prices', 404);
        $data = $request->validate([
            'status_id' => ['required', 'integer', 'min:1'],
            'file' => ['required', 'file', 'max:10240'],
        ]);
        $rows = $this->parsePriceSheet($request->file('file'));
        $count = $this->admission->importServicePrices($rows, (int) $data['status_id']);
        return back()->with('success', "تم استيراد {$count} خدمة من الملف.");
    }

    public function consentIndex(Request $request): View
    {
        $filters = collect(['search', 'from', 'to', 'duty_status', 'contract_status', 'language', 'user_id', 'patient_idno'])->mapWithKeys(fn ($key) => [$key => trim((string) $request->query($key, ''))])->all();
        return view('admission-inpatient.consents.index', ['consents' => $this->workflow->consentList($filters), 'filters' => $filters, 'options' => $this->workflow->consentOptions(), 'homeRoute' => 'branch.dashboard']);
    }

    public function consentCreate(Request $request): View
    {
        return view('admission-inpatient.consents.form', $this->workflow->consentOptions() + [
            'row' => null,
            'language' => $request->integer('language', 1) === 2 ? 2 : 1,
            'homeRoute' => 'branch.dashboard',
        ]);
    }

    public function consentStore(Request $request): RedirectResponse
    {
        $id = $this->workflow->consentSave($request->validate($this->consentRules()));
        return redirect()->route('modules.admission-inpatient.consents.show', $id)->with('success', 'تم إنشاء إقرار التنويم.');
    }

    public function consentShow(int $consent): View
    {
        $row = $this->workflow->consentFind($consent);
        abort_if($row === null, 404);
        return view('admission-inpatient.consents.show', ['row' => $row, 'homeRoute' => 'branch.dashboard']);
    }

    public function consentEdit(int $consent): View
    {
        $row = $this->workflow->consentFind($consent);
        abort_if($row === null, 404);
        return view('admission-inpatient.consents.form', $this->workflow->consentOptions() + ['row' => $row, 'homeRoute' => 'branch.dashboard']);
    }

    public function consentUpdate(Request $request, int $consent): RedirectResponse
    {
        $this->workflow->consentSave($request->validate($this->consentRules()), $consent);
        return redirect()->route('modules.admission-inpatient.consents.show', $consent)->with('success', 'تم تحديث الإقرار.');
    }

    public function consentDestroy(int $consent): RedirectResponse
    {
        $this->workflow->consentDelete($consent);
        return redirect()->route('modules.admission-inpatient.consents.index')->with('success', 'تم حذف الإقرار.');
    }

    public function consentToggle(int $consent): RedirectResponse
    {
        $this->workflow->consentToggle($consent);
        return back()->with('success', 'تم تغيير حالة نشر الإقرار.');
    }

    public function consentTimeline(int $consent): View
    {
        $timeline = $this->workflow->consentTimeline($consent);
        return view('admission-inpatient.consents.timeline', $timeline + ['homeRoute' => 'branch.dashboard']);
    }

    public function consentReminder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id' => ['required', 'string', 'max:255'],
            'mo' => ['required', 'string', 'max:30'],
            'em' => ['nullable', 'email', 'max:255'],
        ]);
        $this->workflow->resendConsentInvitation((string) $data['id'], (string) $data['mo'], (string) ($data['em'] ?? ''));
        return redirect()->route('modules.admission-inpatient.consents.index')->with('success', 'تم إرسال تذكير التوقيع.');
    }

    public function consentDuty(Request $request, int $consent): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'integer', 'in:1,2'], 'note' => ['nullable', 'string', 'max:2000']]);
        $this->workflow->dutyDecision($consent, (int) $data['status'], $data['note'] ?? null);
        return back()->with('success', 'تم حفظ قرار مدير المناوبة.');
    }

    public function consentPdf(int $consent): mixed
    {
        $row = $this->workflow->consentFind($consent);
        abort_if($row === null, 404);
        return $this->pdf->loadView('admission-inpatient.consents.pdf', ['row' => $row])->setPaper('a4')->download('hospital-admission-consent-'.$consent.'.pdf');
    }

    public function consentPdfToken(string $token): mixed
    {
        $row = $this->workflow->consentByToken($token);
        abort_if($row === null, 404);
        return $this->pdf->loadView('admission-inpatient.consents.pdf', ['row' => $row])->setPaper('a4')->download('hospital-admission-consent-'.$row->id.'.pdf');
    }

    public function templateIndex(Request $request): View
    {
        return view('admission-inpatient.consents.templates.index', ['templates' => $this->workflow->templateList(trim((string) $request->query('search', ''))), 'search' => trim((string) $request->query('search', '')), 'homeRoute' => 'branch.dashboard']);
    }

    public function templateCreate(): View
    {
        return view('admission-inpatient.consents.templates.form', ['row' => null, 'homeRoute' => 'branch.dashboard']);
    }

    public function templateStore(Request $request): RedirectResponse
    {
        $this->workflow->templateSave($request->validate(['title' => ['required', 'string', 'max:255'], 'consent_content' => ['required', 'string'], 'publish' => ['nullable', 'boolean']]));
        return redirect()->route('modules.admission-inpatient.consent-templates.index')->with('success', 'تم حفظ قالب الإقرار.');
    }

    public function templateEdit(int $template): View
    {
        $row = $this->workflow->templateFind($template);
        abort_if($row === null, 404);
        return view('admission-inpatient.consents.templates.form', ['row' => $row, 'homeRoute' => 'branch.dashboard']);
    }

    public function templateUpdate(Request $request, int $template): RedirectResponse
    {
        $this->workflow->templateSave($request->validate(['title' => ['required', 'string', 'max:255'], 'consent_content' => ['required', 'string'], 'publish' => ['nullable', 'boolean']]), $template);
        return redirect()->route('modules.admission-inpatient.consent-templates.index')->with('success', 'تم تحديث قالب الإقرار.');
    }

    public function templateToggle(int $template): RedirectResponse { $this->workflow->templateToggle($template); return back()->with('success', 'تم تغيير حالة القالب.'); }
    public function templateDestroy(int $template): RedirectResponse { $this->workflow->templateDelete($template); return back()->with('success', 'تم حذف القالب.'); }

    public function contractShow(string $token): View
    {
        abort_unless(DB::getSchemaBuilder()->hasTable('hospital_admission_consent'), 404);
        $row = DB::table('hospital_admission_consent')->where('token', $token)->first();
        abort_if($row === null || (string) ($row->duty_manager_approval_status ?? '0') !== '1', 404);
        return view('admission-inpatient.consents.contract', ['row' => $row]);
    }

    public function contractDecision(Request $request, string $token): View
    {
        $data = $request->validate(['contract_approval_status' => ['required', 'integer', 'in:1,2'], 'contract_note' => ['nullable', 'string', 'max:2000'], 'contract_otp' => ['nullable', 'string', 'max:10'], 'resendContractOtp' => ['nullable', 'boolean']]);
        $result = $this->workflow->contractDecision($token, (int) $data['contract_approval_status'], $data['contract_note'] ?? null, (string) ($data['contract_otp'] ?? ''), $request->boolean('resendContractOtp'), $request->ip());
        $row = DB::table('hospital_admission_consent')->where('token', $token)->first();
        return view('admission-inpatient.consents.contract', ['row' => $row, 'result' => $result]);
    }

    public function doctorIndex(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        return view('admission-inpatient.doctors.index', ['doctors' => $this->workflow->doctorList($search, $status), 'search' => $search, 'status' => $status, 'homeRoute' => 'branch.dashboard']);
    }

    public function doctorCreate(): View { return view('admission-inpatient.doctors.form', ['row' => null, 'homeRoute' => 'branch.dashboard']); }
    public function doctorStore(Request $request): RedirectResponse { $id = $this->workflow->doctorSave($request->validate($this->doctorRules())); return redirect()->route('modules.admission-inpatient.doctors.index')->with('success', 'تم حفظ طبيب التنويم.'); }
    public function doctorEdit(int $doctor): View { $row = $this->workflow->doctorFind($doctor); abort_if($row === null, 404); return view('admission-inpatient.doctors.form', ['row' => $row, 'homeRoute' => 'branch.dashboard']); }
    public function doctorUpdate(Request $request, int $doctor): RedirectResponse { $this->workflow->doctorSave($request->validate($this->doctorRules()), $doctor); return redirect()->route('modules.admission-inpatient.doctors.index')->with('success', 'تم تحديث طبيب التنويم.'); }
    public function doctorToggle(int $doctor): RedirectResponse { $this->workflow->doctorToggle($doctor); return back()->with('success', 'تم تغيير حالة الطبيب.'); }
    public function doctorDestroy(int $doctor): RedirectResponse { $this->workflow->doctorDelete($doctor); return back()->with('success', 'تم حذف الطبيب.'); }

    public function approvalIndex(Request $request): View
    {
        $filters = ['search' => trim((string) $request->query('search', '')), 'sent' => trim((string) $request->query('sent', '')), 'status' => $request->integer('status'), 'from' => trim((string) $request->query('from', '')), 'to' => trim((string) $request->query('to', ''))];
        return view('admission-inpatient.approvals.index', ['notifications' => $this->workflow->approvalList($filters), 'stats' => $this->workflow->approvalStats($filters), 'filters' => $filters, 'options' => $this->workflow->approvalOptions(), 'homeRoute' => 'branch.dashboard']);
    }
    public function approvalCreate(): View { return view('admission-inpatient.approvals.form', $this->workflow->approvalOptions() + ['row' => null, 'homeRoute' => 'branch.dashboard']); }
    public function approvalStore(Request $request): RedirectResponse { $id = $this->workflow->approvalSave($request->validate($this->approvalRules())); $this->workflow->approvalNotifySelected($id); return redirect()->route('modules.admission-inpatient.approvals.show', $id)->with('success', 'تم حفظ وإرسال إشعار الموافقة الطبية.'); }
    public function approvalShow(int $notification): View { $row = $this->workflow->approvalFind($notification); abort_if($row === null, 404); return view('admission-inpatient.approvals.show', ['row' => $row, 'homeRoute' => 'branch.dashboard']); }
    public function approvalEdit(int $notification): View { $row = $this->workflow->approvalFind($notification); abort_if($row === null, 404); return view('admission-inpatient.approvals.form', $this->workflow->approvalOptions() + ['row' => $row, 'homeRoute' => 'branch.dashboard']); }
    public function approvalUpdate(Request $request, int $notification): RedirectResponse { $this->workflow->approvalSave($request->validate($this->approvalRules()), $notification); $this->workflow->approvalNotifySelected($notification); return redirect()->route('modules.admission-inpatient.approvals.show', $notification)->with('success', 'تم تحديث وإرسال إشعار الموافقة.'); }
    public function approvalSend(int $notification): RedirectResponse { $this->workflow->approvalSend($notification); return back()->with('success', 'تم إشعار المستلمين وقسم التحصيل.'); }
    public function approvalDestroy(int $notification): RedirectResponse { $this->workflow->approvalDelete($notification); return redirect()->route('modules.admission-inpatient.approvals.index')->with('success', 'تم حذف الإشعار.'); }

    public function contactIndex(Request $request, string $kind): View
    {
        abort_unless(in_array($kind, ['cc', 'collections'], true), 404);
        return view('admission-inpatient.contacts.index', ['kind' => $kind, 'contacts' => $this->workflow->contactList($kind, trim((string) $request->query('search', ''))), 'search' => trim((string) $request->query('search', '')), 'homeRoute' => 'branch.dashboard']);
    }
    public function contactCreate(string $kind): View { abort_unless(in_array($kind, ['cc', 'collections'], true), 404); return view('admission-inpatient.contacts.form', ['kind' => $kind, 'row' => null, 'homeRoute' => 'branch.dashboard']); }
    public function contactStore(Request $request, string $kind): RedirectResponse { $this->workflow->contactSave($kind, $request->validate($this->contactRules())); return redirect()->route('modules.admission-inpatient.contacts.index', $kind)->with('success', 'تم حفظ جهة الاتصال.'); }
    public function contactEdit(string $kind, int $contact): View { $row = $this->workflow->contactFind($kind, $contact); abort_if($row === null, 404); return view('admission-inpatient.contacts.form', ['kind' => $kind, 'row' => $row, 'homeRoute' => 'branch.dashboard']); }
    public function contactUpdate(Request $request, string $kind, int $contact): RedirectResponse { $this->workflow->contactSave($kind, $request->validate($this->contactRules()), $contact); return redirect()->route('modules.admission-inpatient.contacts.index', $kind)->with('success', 'تم تحديث جهة الاتصال.'); }
    public function contactToggle(string $kind, int $contact): RedirectResponse { $this->workflow->contactToggle($kind, $contact); return back()->with('success', 'تم تغيير الحالة.'); }
    public function contactDestroy(string $kind, int $contact): RedirectResponse { $this->workflow->contactDelete($kind, $contact); return back()->with('success', 'تم حذف جهة الاتصال.'); }

    public function packageIndex(Request $request): View { $filters = ['search' => trim((string) $request->query('search', '')), 'specialized_clinics_id' => $request->integer('specialized_clinics_id'), 'insurance_companies_id' => $request->integer('insurance_companies_id')]; return view('admission-inpatient.packages.index', $this->workflow->packageOptions() + ['packages' => $this->workflow->packageList($filters), 'filters' => $filters, 'homeRoute' => 'branch.dashboard']); }
    public function packageCreate(): View { return view('admission-inpatient.packages.form', $this->workflow->packageOptions() + ['row' => null, 'homeRoute' => 'branch.dashboard']); }
    public function packageStore(Request $request): RedirectResponse { $id = $this->workflow->packageSave($request->validate($this->packageRules())); return redirect()->route('modules.admission-inpatient.packages.index')->with('success', 'تم حفظ حزمة التنويم.'); }
    public function packageEdit(int $package): View { $row = $this->workflow->packageFind($package); abort_if($row === null, 404); return view('admission-inpatient.packages.form', $this->workflow->packageOptions() + ['row' => $row, 'homeRoute' => 'branch.dashboard']); }
    public function packageUpdate(Request $request, int $package): RedirectResponse { $this->workflow->packageSave($request->validate($this->packageRules()), $package); return redirect()->route('modules.admission-inpatient.packages.index')->with('success', 'تم تحديث الحزمة.'); }
    public function packageToggle(int $package): RedirectResponse { $this->workflow->packageToggle($package); return back()->with('success', 'تم تغيير حالة الحزمة.'); }
    public function packageDestroy(int $package): RedirectResponse { $this->workflow->packageDelete($package); return back()->with('success', 'تم حذف الحزمة.'); }
    public function packagePdf(int $package): mixed { $row = $this->workflow->packageFind($package); abort_if($row === null, 404); return $this->pdf->loadView('admission-inpatient.packages.pdf', ['row' => $row])->setPaper('a4')->download('hospitalization-package-'.$package.'.pdf'); }
    public function packageCatalog(Request $request): View
    {
        return view('admission-inpatient.packages.catalog', $this->workflow->packageCatalog([
            'specialized_clinics_id' => $request->integer('cid', $request->integer('specialized_clinics_id')),
            'insurance_companies_id' => $request->integer('inid', $request->integer('insurance_companies_id')),
            'codes_sections_id' => $request->integer('id', $request->integer('codes_sections_id')),
        ]) + ['homeRoute' => 'branch.dashboard']);
    }

    public function report9Index(Request $request): View { $filters = ['from' => trim((string) $request->query('from', '')), 'to' => trim((string) $request->query('to', '')), 'period_id' => $request->integer('period_id'), 'creator' => $request->integer('creator')]; return view('admission-inpatient.report9.index', $this->workflow->report9Options() + ['reports' => $this->workflow->report9List($filters), 'attendanceTotals' => $this->workflow->report9AttendanceTotals($filters), 'filters' => $filters, 'homeRoute' => 'branch.dashboard']); }
    public function report9Create(): View { return view('admission-inpatient.report9.form', $this->workflow->report9Options() + ['row' => null, 'homeRoute' => 'branch.dashboard']); }
    public function report9Store(Request $request): RedirectResponse { $id = $this->workflow->report9Save($request->validate($this->report9Rules()), $request->allFiles()); return redirect()->route('modules.admission-inpatient.report9.show', $id)->with('success', 'تم حفظ تقرير التنويم.'); }
    public function report9Show(int $report): View { $row = $this->workflow->report9Find($report); abort_if($row === null, 404); return view('admission-inpatient.report9.show', ['row' => $row, 'homeRoute' => 'branch.dashboard']); }
    public function report9Edit(int $report): View { $row = $this->workflow->report9Find($report); abort_if($row === null, 404); return view('admission-inpatient.report9.form', $this->workflow->report9Options() + ['row' => $row, 'homeRoute' => 'branch.dashboard']); }
    public function report9Update(Request $request, int $report): RedirectResponse { $this->workflow->report9Save($request->validate($this->report9Rules()), $request->allFiles(), $report); return redirect()->route('modules.admission-inpatient.report9.show', $report)->with('success', 'تم تحديث التقرير.'); }
    public function report9Destroy(int $report): RedirectResponse { $this->workflow->report9Delete($report); return redirect()->route('modules.admission-inpatient.report9.index')->with('success', 'تم حذف التقرير.'); }
    public function report9Pdf(int $report): mixed { $row = $this->workflow->report9Find($report); abort_if($row === null, 404); return $this->pdf->loadView('admission-inpatient.report9.pdf', ['row' => $row])->setPaper('a4')->download('inpatient-report-9-'.$report.'.pdf'); }

    public function report9File(int $report, string $kind, int $entry): mixed
    {
        $row = $this->workflow->report9Find($report);
        abort_if($row === null, 404);
        abort_unless(in_array($kind, ['entries', 'support'], true), 404);
        $items = $kind === 'support' ? $row->support_services : $row->entries;
        $file = collect($items)->first(fn ($item): bool => (int) ($item->id ?? 0) === $entry);
        abort_if($file === null || trim((string) ($file->files ?? '')) === '', 404);

        $stored = trim((string) $file->files);
        if (Storage::disk('local')->exists($stored)) {
            return Storage::disk('local')->download($stored, basename($stored), ['X-Content-Type-Options' => 'nosniff']);
        }

        // Existing databases may retain the old ../files/<filename> value.
        $legacyPath = public_path('files/'.basename($stored));
        abort_unless(is_file($legacyPath), 404);
        return response()->download($legacyPath, basename($legacyPath), ['X-Content-Type-Options' => 'nosniff']);
    }

    public function report9Lookup(Request $request, string $kind): mixed
    {
        abort_unless(in_array($kind, ['notice', 'action'], true), 404);
        $sectionId = $request->integer('section_id', (int) explode('-', (string) $request->query('id', '0'))[0]);
        $rows = $this->workflow->report9Lookup($kind, $sectionId);
        if ($request->expectsJson()) {
            return response()->json($rows);
        }
        $language = $request->integer('l', 1) === 2 ? 'en' : 'ar';
        $output = "modelOptions2.push(new Option('".($kind === 'action' ? 'الإجراء' : 'الملاحظة')."', ''));\n";
        foreach ($rows as $row) {
            $name = (string) ($row->{'name_'.$language} ?? $row->name_ar ?? $row->name_en ?? '');
            $output .= 'modelOptions2.push(new Option('.json_encode($name, JSON_UNESCAPED_UNICODE).', '.json_encode((string) $row->id).'));'."\n";
        }
        return response($output)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function employeeReport9Index(Request $request): View
    {
        $filters = [
            'from' => trim((string) $request->query('from', '')),
            'to' => trim((string) $request->query('to', '')),
            'period_id' => $request->integer('period_id'),
            'creator' => $request->integer('creator'),
        ];

        return view('admission-inpatient.employee-report9.index', $this->workflow->report9Options() + [
            'reports' => $this->workflow->employeeReport9List($filters),
            'filters' => $filters,
            'homeRoute' => 'branch.dashboard',
        ]);
    }

    public function employeeReport9Create(): View
    {
        return view('admission-inpatient.report9.form', $this->workflow->report9Options() + [
            'row' => null,
            'employeeMode' => true,
            'homeRoute' => 'branch.dashboard',
        ]);
    }

    public function employeeReport9Store(Request $request): RedirectResponse
    {
        $id = $this->workflow->employeeReport9Save($request->validate($this->report9Rules(false)), $request->allFiles());
        return redirect()->route('modules.admission-inpatient.employee-report9.show', $id)->with('success', 'تم حفظ تقرير الموظفين.');
    }

    public function employeeReport9Show(int $report): View
    {
        $row = $this->workflow->employeeReport9Find($report);
        abort_if($row === null, 404);
        return view('admission-inpatient.report9.show', ['row' => $row, 'employeeMode' => true, 'homeRoute' => 'branch.dashboard']);
    }

    public function employeeReport9Edit(int $report): View
    {
        $row = $this->workflow->employeeReport9Find($report);
        abort_if($row === null, 404);
        return view('admission-inpatient.report9.form', $this->workflow->report9Options() + [
            'row' => $row,
            'employeeMode' => true,
            'homeRoute' => 'branch.dashboard',
        ]);
    }

    public function employeeReport9Update(Request $request, int $report): RedirectResponse
    {
        $this->workflow->employeeReport9Save($request->validate($this->report9Rules(false)), $request->allFiles(), $report);
        return redirect()->route('modules.admission-inpatient.employee-report9.show', $report)->with('success', 'تم تحديث تقرير الموظفين.');
    }

    public function employeeReport9Destroy(int $report): RedirectResponse
    {
        $this->workflow->employeeReport9Delete($report);
        return redirect()->route('modules.admission-inpatient.employee-report9.index')->with('success', 'تم حذف تقرير الموظفين.');
    }

    public function employeeReport9Pdf(int $report): mixed
    {
        $row = $this->workflow->employeeReport9Find($report);
        abort_if($row === null, 404);
        return $this->pdf->loadView('admission-inpatient.report9.pdf', ['row' => $row, 'employeeMode' => true])->setPaper('a4')->download('employee-inpatient-report-9-'.$report.'.pdf');
    }

    public function employeeReport9File(int $report, string $kind, int $entry): mixed
    {
        $row = $this->workflow->employeeReport9Find($report);
        abort_if($row === null, 404);
        abort_unless(in_array($kind, ['entries', 'support'], true), 404);
        $items = $kind === 'support' ? $row->support_services : $row->entries;
        $file = collect($items)->first(fn ($item): bool => (int) ($item->id ?? 0) === $entry);
        abort_if($file === null || trim((string) ($file->files ?? '')) === '', 404);

        $stored = trim((string) $file->files);
        if (Storage::disk('local')->exists($stored)) {
            return Storage::disk('local')->download($stored, basename($stored), ['X-Content-Type-Options' => 'nosniff']);
        }

        $legacyPath = public_path('files/'.basename($stored));
        abort_unless(is_file($legacyPath), 404);
        return response()->download($legacyPath, basename($legacyPath), ['X-Content-Type-Options' => 'nosniff']);
    }

    /** @return list<array{code:string,name:string,price:float}> */
    private function parsePriceSheet(?object $file): array
    {
        abort_if($file === null || ! method_exists($file, 'getRealPath'), 422, 'ملف الاستيراد غير صالح.');
        $path = (string) $file->getRealPath();
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $rows = [];
        if ($extension !== 'xlsx') {
            $handle = fopen($path, 'rb');
            abort_if($handle === false, 422, 'تعذر قراءة الملف.');
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = ['code' => (string) ($row[0] ?? ''), 'name' => (string) ($row[1] ?? ''), 'price' => (float) ($row[2] ?? 0)];
            }
            fclose($handle);
            return $rows;
        }

        $zip = new \ZipArchive();
        abort_if($zip->open($path) !== true, 422, 'ملف XLSX غير صالح.');
        $shared = [];
        if (($sharedXml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $sharedNode = @simplexml_load_string($sharedXml);
            if ($sharedNode !== false) {
                $sharedNode->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                foreach ($sharedNode->xpath('//a:si') ?: [] as $item) {
                    $shared[] = trim(implode('', array_map('strval', $item->xpath('.//a:t') ?: [])));
                }
            }
        }
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        abort_if($sheetXml === false, 422, 'لا توجد ورقة بيانات في الملف.');
        $sheet = @simplexml_load_string($sheetXml);
        abort_if($sheet === false, 422, 'تعذر تحليل ورقة البيانات.');
        $sheet->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        foreach ($sheet->xpath('//a:sheetData/a:row') ?: [] as $rowNode) {
            $values = [];
            foreach ($rowNode->xpath('./a:c') ?: [] as $cell) {
                $value = (string) ($cell->v ?? '');
                if ((string) ($cell['t'] ?? '') === 's') $value = $shared[(int) $value] ?? '';
                if ((string) ($cell['t'] ?? '') === 'inlineStr') $value = trim(implode('', array_map('strval', $cell->xpath('.//a:t') ?: [])));
                $values[] = $value;
            }
            $rows[] = ['code' => (string) ($values[0] ?? ''), 'name' => (string) ($values[1] ?? ''), 'price' => (float) ($values[2] ?? 0)];
        }
        return $rows;
    }

    /** @return array<string, array<int, string|Rule>> */
    private function calculatorRules(string $type, string $mode): array
    {
        $rules = [
            'patient_name' => ['required', 'string', 'max:255'], 'file_number' => ['required', 'string', 'max:100'],
            'nationality' => ['required', 'integer', 'min:1'],
            'room' => [$type === 'manual' && $mode === 'procedures' ? 'nullable' : 'required', 'integer', 'min:1'],
            'doctor' => ['required', 'string', 'max:255'],
            'days' => [$type === 'manual' && $mode === 'procedures' ? 'nullable' : 'required', 'integer', 'min:1'],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:50'], 'tools_value' => ['nullable', 'numeric', 'min:0'],
            // The legacy forms submit this field in two shapes: the standard
            // procedures screen sends an array of service-price IDs, while
            // the manual screen stores the entered code string.  Keep the
            // request rule permissive and let the domain service normalize
            // both representations.
            'lang' => ['nullable', 'in:ar,en,1,2'], 'procedurs' => ['nullable'],
            'room_type' => ['nullable', 'integer', 'min:1'], 'code_total' => ['nullable', 'numeric'],
        ];

        if ($mode === 'procedures' && $type === 'standard') {
            $rules['procedurs'] = ['required', 'array', 'min:1'];
            $rules['procedurs.*'] = ['integer', 'min:1'];
        }
        if ($mode === 'procedures' && $type === 'manual') {
            $rules['manual_procedures'] = ['nullable', 'array'];
            $rules['manual_procedures.*.name'] = ['required_with:manual_procedures.*.price', 'string', 'max:255'];
            $rules['manual_procedures.*.price'] = ['required_with:manual_procedures.*.name', 'numeric', 'min:0'];
            $rules['name'] = ['nullable', 'array'];
            $rules['price'] = ['nullable', 'array'];
            $rules['name.*'] = ['nullable', 'string', 'max:255'];
            $rules['price.*'] = ['nullable', 'numeric', 'min:0'];
            $rules['pharmaceutical'] = ['nullable', 'numeric', 'min:0'];
        }

        return $rules;
    }

    private function referenceRules(string $type): array
    {
        $rules = ['name_en' => ['nullable', 'string', 'max:255'], 'name_ar' => ['nullable', 'string', 'max:255'], 'name_ch' => ['nullable', 'string', 'max:255'], 'info' => ['nullable', 'string', 'max:2000'], 'code' => ['nullable', 'string', 'max:100'], 'price' => ['nullable', 'numeric', 'min:0'], 'section_id' => ['nullable', 'integer', 'min:1'], 'admission_status_id' => ['nullable', 'integer', 'min:1']];
        if (in_array($type, ['rooms', 'service-prices'], true)) $rules['admission_status_id'] = ['required', 'integer', 'min:1'];
        if (in_array($type, ['rep9-notices', 'rep9-actions'], true)) $rules['section_id'] = ['required', 'integer', 'min:1'];
        if (in_array($type, ['medical-approval-statuses', 'medical-approval-rejection-reasons'], true)) $rules['name_ar'] = ['required', 'string', 'max:255'];
        if (in_array($type, ['nationalities', 'statuses', 'rooms', 'service-prices'], true)) $rules['name_en'] = ['required', 'string', 'max:255'];
        return $rules;
    }

    private function consentRules(): array
    {
        return [
            'patient_name_ar' => ['required_without:patient_name_en', 'nullable', 'string', 'max:255'],
            'patient_name_en' => ['required_without:patient_name_ar', 'nullable', 'string', 'max:255'],
            'patient_idno' => ['required', 'string', 'max:30'], 'patient_file_number' => ['required', 'string', 'max:50'],
            'patient_nationality' => ['required', 'integer', 'min:1'], 'contractor_type' => ['required', 'integer', 'in:1,2'],
            'contractor_name_ar' => ['required_if:contractor_type,1', 'nullable', 'string', 'max:255'],
            'contractor_name_en' => ['nullable', 'string', 'max:255'],
            'contractor_idno' => ['required_if:contractor_type,1', 'nullable', 'string', 'max:30'],
            'contractor_mobile' => ['required', 'string', 'regex:/^05[0-9]{8}$/'],
            'contractor_nationality' => ['required_if:contractor_type,1', 'nullable', 'integer', 'min:1'],
            'language' => ['required', 'integer', 'in:1,2'], 'relative' => ['required_if:contractor_type,1', 'nullable', 'integer', 'min:1'],
            'patient_id_type' => ['nullable', 'integer', 'min:1'], 'contractor_id_type' => ['nullable', 'integer', 'min:1'],
            'date_type' => ['nullable', 'integer', 'min:0'], 'birth_day' => ['nullable', 'integer', 'between:0,31'],
            'birth_month' => ['nullable', 'integer', 'between:0,12'], 'birth_year' => ['nullable', 'integer', 'between:0,2100'],
            'sex_code' => ['nullable', 'integer', 'min:0'], 'payment_type' => ['nullable', 'integer', 'min:0'],
            'deserved_amount' => ['nullable', 'numeric', 'min:0'], 'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'email' => ['nullable', 'email', 'max:255'], 'consent_title' => ['required', 'string', 'max:255'],
            'consent_content' => ['required', 'string'], 'template_id' => ['nullable', 'integer', 'min:1'], 'duty_managers_ids' => ['nullable', 'array'],
            'duty_managers_ids.*' => ['integer', 'min:1'],
        ];
    }
    private function doctorRules(): array { return ['doctor_id_no' => ['required', 'string', 'max:30'], 'name_ar' => ['required', 'string', 'max:255'], 'name_en' => ['required', 'string', 'max:255'], 'designation_ar' => ['nullable', 'string', 'max:255'], 'designation_en' => ['nullable', 'string', 'max:255'], 'email' => ['required', 'string', 'max:255'], 'mobile_no' => ['required', 'string', 'max:30'], 'status' => ['required', 'in:active,inactive']]; }
    private function approvalRules(): array { return ['patient_name' => ['required', 'string', 'max:255'], 'patient_identity' => ['required', 'string', 'max:50'], 'room_number' => ['nullable', 'string', 'max:100'], 'clinician_id' => ['nullable', 'array'], 'clinician_id.*' => ['integer', 'min:1'], 'inpatient_clinician_id' => ['nullable', 'array'], 'inpatient_clinician_id.*' => ['integer', 'min:1'], 'medical_approval_cc_ids' => ['required', 'array', 'min:1'], 'medical_approval_cc_ids.*' => ['integer', 'min:1'], 'medical_approval_status_id' => ['required', 'integer', 'min:1'], 'rejection_reason_id' => ['nullable', 'integer', 'min:1'], 'notes' => ['nullable', 'string', 'max:4000']]; }
    private function contactRules(): array { return ['employee_name' => ['required', 'string', 'max:255'], 'email' => ['nullable', 'email', 'max:255'], 'mobile' => ['nullable', 'regex:/^05[0-9]{8}$/'], 'status' => ['required', 'in:active,inactive']]; }
    private function packageRules(): array { return ['specialized_clinics_id' => ['required', 'integer', 'min:1'], 'insurance_companies_id' => ['required', 'integer', 'min:1'], 'code' => ['required', 'string', 'max:100'], 'days' => ['required', 'integer', 'min:1'], 'price' => ['required', 'numeric', 'min:0'], 'name_ar' => ['required', 'string', 'max:255'], 'name_en' => ['nullable', 'string', 'max:255'], 'notice_ar' => ['required', 'string', 'max:4000'], 'notice_en' => ['nullable', 'string', 'max:4000'], 'publish' => ['nullable', 'boolean']]; }
    private function report9Rules(bool $withAttendance = true): array
    {
        $rules = [
            'period' => ['nullable', 'integer', 'min:1'],
            'period_id' => ['required_without:period', 'nullable', 'integer', 'min:1'],
            'date' => ['required', 'date'],
            'rep_place' => ['nullable', 'integer', 'min:1'],
            'entries' => ['nullable', 'array'],
            'entries.*.date' => ['nullable', 'date'],
            'entries.*.filenumber' => ['nullable', 'string', 'max:100'],
            'entries.*.location' => ['nullable', 'integer', 'min:1'],
            'entries.*.room_bod_number' => ['nullable', 'string', 'max:100'],
            'entries.*.section' => ['nullable', 'integer', 'min:1'],
            'entries.*.notice' => ['nullable', 'integer', 'min:1'],
            'entries.*.action' => ['nullable', 'integer', 'min:1'],
            'entries.*.other' => ['nullable', 'string', 'max:4000'],
            'entries.*.existing_file' => ['nullable', 'string', 'max:255'],
            'entries.*.file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif', 'max:10240'],
            'support_services' => ['nullable', 'array'],
            'support_services.*.date' => ['nullable', 'date'],
            'support_services.*.maintenance_departments' => ['nullable', 'integer', 'min:1'],
            'support_services.*.maintenance_type' => ['nullable', 'integer', 'min:1'],
            'support_services.*.maintenance_request_type' => ['nullable', 'integer', 'min:1'],
            'support_services.*.description' => ['nullable', 'string', 'max:4000'],
            'support_services.*.existing_file' => ['nullable', 'string', 'max:255'],
            'support_services.*.file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif', 'max:10240'],
        ];

        if ($withAttendance) {
            $rules += [
                'attendees' => ['required', 'integer', 'min:0'],
                'absence' => ['required', 'integer', 'min:0'],
                'latecomers' => ['required', 'integer', 'min:0'],
                'permissible' => ['required', 'integer', 'min:0'],
            ];
        }

        return $rules;
    }

    private function normalizeLegacyCalculatorFields(Request $request): void
    {
        if (! $request->has('tools_value') && $request->has('tools value')) {
            $request->merge(['tools_value' => $request->input('tools value')]);
        }
    }

    private function calculatorMode(string $type, object $row): string
    {
        return match ((int) ($row->type ?? 0)) {
            1 => 'procedures',
            2 => 'observation',
            default => 'direct',
        };
    }
}
