<?php

namespace App\Http\Controllers\Module\LegacyWorkflows;

use App\Http\Controllers\Controller;
use App\Services\LegacyWorkflows\MedicalAgreementService;
use App\Services\LegacyWorkflows\YakeenClient;
use App\Support\LegacyWorkflows\LegacyWorkflowDownload;
use App\Services\Pdf\ArabicPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\View\View;

class MedicalAgreementController extends Controller
{
    public function __construct(
        private readonly MedicalAgreementService $service,
        private readonly LegacyWorkflowDownload $downloads,
        private readonly YakeenClient $yakeen,
    ) {}

    public function index(Request $request, string $variant): View
    {
        $filters = collect(['from', 'to', 'language', 'creator', 'id_number', 'status'])
            ->mapWithKeys(fn ($key) => [$key => trim((string) $request->input($key, ''))])->all();

        return view('legacy-workflows.medical-agreements.index', $this->service->options($variant) + ['variant' => $variant, 'agreements' => $this->service->list($variant, $filters), 'filters' => $filters, 'homeRoute' => 'branch.dashboard']);
    }

    public function create(string $variant): RedirectResponse
    {
        // The legacy screen opens the new agreement form in a modal on the
        // listing page. Keep direct/old links to /create compatible with that
        // behavior instead of rendering a second full-page form.
        $this->service->authorize($variant);

        return redirect()
            ->route('modules.medical-agreements.index', $variant)
            ->with('open_new_medical_agreement', true);
    }

    public function store(Request $request, string $variant): RedirectResponse
    {
        $data = $request->validate([
            'language' => ['required', 'integer', 'in:1,2'], 'contractor_type' => ['required', 'integer', 'in:1,2'],
            'patient_name_ar' => ['nullable', 'string', 'max:200', 'required_without:patient_name_en'], 'patient_name_en' => ['nullable', 'string', 'max:120', 'required_without:patient_name_ar'],
            'patient_idno' => ['required', 'string', 'max:12'], 'patient_file_number' => ['required', 'string', 'max:20'],
            'patient_nationality' => ['required', 'integer'], 'contractor_name_ar' => ['nullable', 'string', 'max:150'],
            'contractor_name_en' => ['nullable', 'string', 'max:150'], 'contractor_idno' => ['nullable', 'required_if:contractor_type,1', 'string', 'max:15'],
            'contractor_mobile' => ['required', 'string', 'max:16'], 'contractor_nationality' => ['nullable', 'required_if:contractor_type,1', 'integer'],
            'relative' => ['nullable', 'integer'], 'date_type' => ['nullable', 'integer', 'in:1,2'], 'birth_day' => ['nullable', 'integer', 'between:1,31'],
            'birth_month' => ['nullable', 'integer', 'between:1,12'], 'birth_year' => ['nullable', 'integer', 'between:1300,2100'],
            'patient_id_type' => ['nullable', 'integer'], 'contractor_id_type' => ['nullable', 'integer'], 'sex_code' => ['nullable', 'integer'],
            'email' => ['nullable', 'email', 'max:150'],
        ]);

        if (in_array($variant, [MedicalAgreementService::STANDARD, MedicalAgreementService::SADQ], true) && !app()->environment('testing')) {
            $results = session('medical_agreements.'.$variant.'.yakeen', []);
            abort_unless(is_array($results['patient'] ?? null), 422, 'استعلم عن بيانات المريض من يقين قبل حفظ الاتفاقية.');
            abort_unless(trim((string) ($results['patient']['id_number'] ?? '')) === trim((string) $data['patient_idno']), 422, 'أعد الاستعلام عن المريض بعد تغيير رقم الهوية.');
            if ((int) $data['contractor_type'] === 1) {
                abort_unless(is_array($results['contractor'] ?? null), 422, 'استعلم عن بيانات المتعهد من يقين قبل حفظ الاتفاقية.');
                abort_unless(trim((string) ($results['contractor']['id_number'] ?? '')) === trim((string) $data['contractor_idno']), 422, 'أعد الاستعلام عن المتعهد بعد تغيير رقم الهوية.');
            }
            $data['yakeen_results'] = $results;
        }

        try {
            $id = $this->service->create($variant, $data);
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors(['agreement' => $exception->getMessage() ?: 'تعذر إنشاء الاتفاقية.']);
        }

        if (in_array($variant, [MedicalAgreementService::STANDARD, MedicalAgreementService::SADQ], true)) {
            session()->forget('medical_agreements.'.$variant.'.yakeen');
        }

        return redirect()->route('modules.medical-agreements.show', [$variant, $id])->with('success', $variant === MedicalAgreementService::STANDARD ? 'تم حفظ الاتفاقية بنجاح.' : 'تم إنشاء الاتفاقية وإرسال دعوة التوقيع عبر منصة صادق.');
    }

    public function yakeenLookup(Request $request, string $variant): \Illuminate\Http\JsonResponse
    {
        abort_unless(in_array($variant, [MedicalAgreementService::STANDARD, MedicalAgreementService::SADQ], true), 404);
        $this->service->authorize($variant);
        $data = $request->validate([
            'subject' => ['required', 'in:patient,contractor'],
            'id_type' => ['required', 'integer', 'between:1,5'],
            'id_number' => ['required', 'string', 'max:20'],
            'birth_year' => ['nullable', 'integer', 'between:1300,2100'],
            'birth_month' => ['nullable', 'integer', 'between:1,12'],
            'nationality' => ['nullable', 'integer'],
        ]);

        try {
            $result = $this->yakeen->lookup(
                (int) $data['id_type'],
                $data['id_number'],
                isset($data['birth_year']) ? (int) $data['birth_year'] : null,
                isset($data['birth_month']) ? (int) $data['birth_month'] : null,
                isset($data['nationality']) ? (int) $data['nationality'] : null,
            );
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json(['message' => $exception->getMessage() ?: 'تعذر الاستعلام من يقين.'], 422);
        }

        $key = 'medical_agreements.'.$variant.'.yakeen';
        $saved = session($key, []);
        $saved[$data['subject']] = $result + [
            'id_type' => (int) $data['id_type'],
            'id_number' => trim($data['id_number']),
        ];
        session([$key => $saved]);

        return response()->json(['message' => 'تم جلب البيانات من يقين.', 'data' => $result]);
    }

    public function show(string $variant, int $agreement): View
    {
        $record = $this->service->find($variant, $agreement);
        abort_if($record === null, 404);

        return view('legacy-workflows.medical-agreements.show', ['variant' => $variant, 'agreement' => $record, 'homeRoute' => 'branch.dashboard']);
    }

    public function timeline(string $variant, int $agreement): JsonResponse
    {
        $timeline = $this->service->timeline($variant, $agreement);
        abort_if($timeline === null, 404);

        return response()->json($timeline + [
            'detail_url' => route('modules.medical-agreements.show', [$variant, $agreement]),
            'pdf_url' => route('modules.medical-agreements.pdf', [$variant, $agreement]),
        ]);
    }

    public function pdf(string $variant, int $agreement): mixed
    {
        $record = $this->service->find($variant, $agreement);
        abort_if($record === null, 404);

        if ($variant !== MedicalAgreementService::STANDARD) {
            $signed = $this->service->downloadSadqDocument($variant, $agreement);
            if ($signed !== null) {
                return response($signed, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="medical-agreement-'.$agreement.'-signed.pdf"',
                ]);
            }
        }

        return app(ArabicPdfService::class)->loadView('legacy-workflows.medical-agreements.pdf', ['variant' => $variant, 'agreement' => $record])->setPaper('a4')->download('medical-agreement-'.$agreement.'.pdf');
    }

    public function refreshStatus(string $variant, int $agreement): RedirectResponse
    {
        $this->service->refreshSadqStatus($variant, $agreement);

        return back()->with('success', 'تم تحديث حالة التوقيع من منصة صادق.');
    }

    public function remind(string $variant, int $agreement): RedirectResponse
    {
        $this->service->remindSadq($variant, $agreement);

        return back()->with('success', 'تم إعادة إرسال تذكير التوقيع.');
    }

    public function attach(Request $request, string $variant, int $agreement): RedirectResponse
    {
        $data = $request->validate(['attachment' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,pdf,docx', 'max:10240']]);
        $this->service->attach($variant, $agreement, $data['attachment']);

        return back()->with('success', 'تم إرفاق الملف.');
    }

    public function attachment(string $variant, int $agreement, int $attachment): mixed
    {
        $record = $this->service->attachment($variant, $agreement, $attachment);
        abort_if($record === null, 404);

        return $this->downloads->download((string) $record->file_name, ['payment_guarantee_files']);
    }

    public function deleteAttachment(string $variant, int $agreement, int $attachment): RedirectResponse
    {
        $this->service->deleteAttachment($variant, $agreement, $attachment);

        return back()->with('success', 'تم حذف الملف.');
    }
}
