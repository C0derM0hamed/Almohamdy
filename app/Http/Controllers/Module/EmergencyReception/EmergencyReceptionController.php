<?php

namespace App\Http\Controllers\Module\EmergencyReception;

use App\Http\Controllers\Controller;
use App\Services\EmergencyReception\EmergencyReceptionService;
use App\Support\EmergencyReception\EmergencyReceptionAccess;
use App\Services\Pdf\ArabicPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class EmergencyReceptionController extends Controller
{
    public function __construct(private readonly EmergencyReceptionService $service) {}

    public function guide(string $guide): View
    {
        EmergencyReceptionAccess::authorize();
        abort_unless(in_array($guide, ['emergency-cases', 'emergency-reception'], true), 404);

        return view('modules.emergency-reception.guide', ['guide' => $guide, 'homeRoute' => 'branch.dashboard']);
    }

    public function index(Request $request, string $type): View
    {
        $filters = ['from' => $request->string('from')->toString(), 'to' => $request->string('to')->toString(), 'status' => $request->input('status', ''), 'user_id' => $request->integer('user_id'), 'search' => $request->string('search')->toString()];

        return view('modules.emergency-reception.index', ['type' => $type, 'definition' => $this->service->definition($type), 'records' => $this->service->list($type, $filters), 'filters' => $filters, 'lookups' => $this->service->lookups(), 'homeRoute' => 'branch.dashboard']);
    }

    public function create(string $type): View
    {
        EmergencyReceptionAccess::authorize();

        return view('modules.emergency-reception.create', ['type' => $type, 'definition' => $this->service->definition($type), 'lookups' => $this->service->lookups(), 'homeRoute' => 'branch.dashboard']);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $definition = $this->service->definition($type);
        $rules = [];
        foreach ($definition['fields'] as $field => [, $kind, $required]) {
            $rule = [$required ? 'required' : 'nullable'];
            if (in_array($kind, ['number', 'country', 'nationality', 'death_reason', 'relative', 'injury', 'room_type', 'receipt_via', 'gender', 'incident_status', 'paramedic', 'language', 'date_type', 'report_type'], true)) {
                $rule[] = 'integer';
            } elseif (in_array($kind, ['date', 'datetime-local'], true)) {
                $rule[] = 'date';
            } else {
                $rule[] = 'string';
                $rule[] = 'max:2000';
            }
            $rules[$field] = $rule;
        }
        $id = $this->service->create($type, $request->validate($rules));

        return redirect()->route('modules.emergency-reception.show', [$type, $id])->with('success', 'تم الحفظ بنجاح');
    }

    public function show(string $type, int $record): View
    {
        $item = $this->service->find($type, $record);
        abort_if($item === null, 404);

        return view('modules.emergency-reception.show', ['type' => $type, 'definition' => $this->service->definition($type), 'record' => $item, 'lookups' => $this->service->lookups(), 'attachments' => $this->service->attachments($type, $record), 'homeRoute' => 'branch.dashboard']);
    }

    public function attachment(Request $request, string $type, int $record): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,pdf,docx', 'max:10240']]);
        $this->service->addAttachment($type, $record, $request->file('file'));

        return back()->with('success', 'تم رفع الملف بنجاح');
    }

    public function medicalReport(Request $request, int $record): RedirectResponse
    {
        $data = $request->validate(['doctor_id' => ['required', 'integer'], 'medical_diagnosis' => ['required', 'string', 'max:5000'], 'recommendation' => ['required', 'string', 'max:5000']]);
        $this->service->addIncidentMedicalReport($record, $data);

        return back()->with('success', 'تم رفع التقرير الطبي');
    }

    public function download(string $type, int $record, int $attachment): mixed
    {
        return $this->service->downloadAttachment($type, $record, $attachment);
    }

    public function pdf(string $type, int $record): mixed
    {
        $item = $this->service->find($type, $record);
        abort_if($item === null, 404);

        return app(ArabicPdfService::class)->loadView('modules.emergency-reception.pdf', ['definition' => $this->service->definition($type), 'record' => $item])->setPaper('a4')->download("{$type}-{$record}.pdf");
    }
}
