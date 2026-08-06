<?php

namespace App\Http\Controllers\Module\LegacyWorkflows;

use App\Http\Controllers\Controller;
use App\Services\LegacyWorkflows\GovernmentalServiceWorkflow;
use App\Support\LegacyWorkflows\LegacyWorkflowDownload;
use App\Services\Pdf\ArabicPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GovernmentalServiceController extends Controller
{
    public function __construct(private readonly GovernmentalServiceWorkflow $service, private readonly LegacyWorkflowDownload $downloads) {}

    public function index(Request $request): View
    {
        $filters = collect(['from', 'to', 'status', 'service_id', 'identity'])->mapWithKeys(fn ($key) => [$key => trim((string) $request->input($key, ''))])->all();

        return view('legacy-workflows.governmental-services.index', $this->service->options() + ['records' => $this->service->list($filters), 'filters' => $filters, 'canProcess' => $this->service->canProcess(), 'homeRoute' => 'branch.dashboard']);
    }

    public function create(): View
    {
        return view('legacy-workflows.governmental-services.create', $this->service->options() + ['homeRoute' => 'branch.dashboard']);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'platform_id' => ['required', 'integer'], 'service_id' => ['required', 'integer'], 'patient' => ['required', 'string', 'max:80'],
            'file_number' => ['nullable', 'string', 'max:14'], 'id_no' => ['required', 'string', 'max:12'], 'mobile' => ['required', 'string', 'max:16'],
            'job_title' => ['nullable', 'string', 'max:100'], 'address' => ['nullable', 'string', 'max:100'], 'date_type' => ['nullable', 'integer', 'in:1,2'],
            'birth_day' => ['nullable', 'integer', 'between:1,31'], 'birth_month' => ['nullable', 'integer', 'between:1,12'], 'birth_year' => ['nullable', 'integer'],
            'sponsor_id' => ['nullable', 'string', 'max:12'], 'nationality_type' => ['nullable', 'integer'], 'country' => ['nullable', 'integer'],
            'patient_wife' => ['nullable', 'string', 'max:100'], 'id_no_wife' => ['nullable', 'string', 'max:12'], 'country_wife' => ['nullable', 'integer'],
            'date_type_wife' => ['nullable', 'integer'], 'birth_day_wife' => ['nullable', 'integer'], 'birth_month_wife' => ['nullable', 'integer'],
            'birth_year_wife' => ['nullable', 'integer'], 'married_request_type' => ['nullable', 'integer'], 'mobile_wife' => ['nullable', 'string', 'max:16'],
            'passport_no' => ['nullable', 'string', 'max:16'],
        ]);
        $id = $this->service->create($data);

        return redirect()->route('modules.governmental-services.show', $id)->with('success', 'تم إنشاء الطلب بنجاح.');
    }

    public function show(int $service): View
    {
        $record = $this->service->find($service);
        abort_if($record === null, 404);

        return view('legacy-workflows.governmental-services.show', ['record' => $record, 'statuses' => $this->service->options()['statuses'], 'canProcess' => $this->service->canProcess(), 'homeRoute' => 'branch.dashboard']);
    }

    public function transition(Request $request, int $service): RedirectResponse
    {
        $data = $request->validate(['status_id' => ['required', 'integer'], 'details' => ['nullable', 'string', 'max:200']]);
        $this->service->transition($service, (int) $data['status_id'], $data['details'] ?? null);

        return back()->with('success', 'تم تحديث حالة الطلب.');
    }

    public function attach(Request $request, int $service): RedirectResponse
    {
        $data = $request->validate(['attachment' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,pdf,docx', 'max:10240']]);
        $this->service->attach($service, $data['attachment']);

        return back()->with('success', 'تم إرفاق الملف.');
    }

    public function attachment(int $service, int $attachment): mixed
    {
        $record = $this->service->attachment($service, $attachment);
        abort_if($record === null, 404);

        return $this->downloads->download((string) $record->file_name, ['governmental_services_file']);
    }

    public function deleteAttachment(int $service, int $attachment): RedirectResponse
    {
        $this->service->deleteAttachment($service, $attachment);

        return back()->with('success', 'تم حذف الملف.');
    }

    public function pdf(int $service): mixed
    {
        $record = $this->service->find($service);
        abort_if($record === null, 404);

        return app(ArabicPdfService::class)->loadView('legacy-workflows.governmental-services.pdf', ['record' => $record])->setPaper('a4')->download('governmental-service-'.$service.'.pdf');
    }
}
