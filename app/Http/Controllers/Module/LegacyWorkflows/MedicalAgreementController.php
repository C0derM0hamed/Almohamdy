<?php

namespace App\Http\Controllers\Module\LegacyWorkflows;

use App\Http\Controllers\Controller;
use App\Services\LegacyWorkflows\MedicalAgreementService;
use App\Support\LegacyWorkflows\LegacyWorkflowDownload;
use App\Services\Pdf\ArabicPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicalAgreementController extends Controller
{
    public function __construct(private readonly MedicalAgreementService $service, private readonly LegacyWorkflowDownload $downloads) {}

    public function index(Request $request, string $variant): View
    {
        $filters = collect(['from', 'to', 'language', 'creator', 'id_number', 'status'])
            ->mapWithKeys(fn ($key) => [$key => trim((string) $request->input($key, ''))])->all();

        return view('legacy-workflows.medical-agreements.index', $this->service->options($variant) + ['variant' => $variant, 'agreements' => $this->service->list($variant, $filters), 'filters' => $filters, 'homeRoute' => 'branch.dashboard']);
    }

    public function create(string $variant): View
    {
        return view('legacy-workflows.medical-agreements.create', $this->service->options($variant) + ['variant' => $variant, 'homeRoute' => 'branch.dashboard']);
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
        $id = $this->service->create($variant, $data);

        return redirect()->route('modules.medical-agreements.show', [$variant, $id])->with('success', 'تم حفظ الاتفاقية بنجاح.');
    }

    public function show(string $variant, int $agreement): View
    {
        $record = $this->service->find($variant, $agreement);
        abort_if($record === null, 404);

        return view('legacy-workflows.medical-agreements.show', ['variant' => $variant, 'agreement' => $record, 'homeRoute' => 'branch.dashboard']);
    }

    public function pdf(string $variant, int $agreement): mixed
    {
        $record = $this->service->find($variant, $agreement);
        abort_if($record === null, 404);

        return app(ArabicPdfService::class)->loadView('legacy-workflows.medical-agreements.pdf', ['variant' => $variant, 'agreement' => $record])->setPaper('a4')->download('medical-agreement-'.$agreement.'.pdf');
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
