<?php

namespace App\Http\Controllers\Module\AdmissionCalculator;

use App\Http\Controllers\Controller;
use App\Services\AdmissionInpatient\AdmissionInpatientService;
use App\Services\Pdf\ArabicPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdmissionCalculatorController extends Controller
{
    public function __construct(private readonly AdmissionInpatientService $service) {}

    public function index(Request $request, string $type = 'standard'): View
    {
        $filters = ['file_number' => trim((string) $request->input('file_number', '')), 'from' => trim((string) $request->input('from', '')), 'to' => trim((string) $request->input('to', ''))];
        return view('admission-inpatient.calculator.index', ['type' => $type, 'records' => $this->service->calculatorList($type, $filters), 'filters' => $filters, 'homeRoute' => 'branch.dashboard']);
    }

    public function create(string $type = 'standard'): View
    {
        return view('admission-inpatient.calculator.form', $this->service->calculatorOptions($type) + ['type' => $type, 'mode' => 'direct', 'row' => null, 'homeRoute' => 'branch.dashboard']);
    }

    public function store(Request $request, string $type = 'standard'): RedirectResponse
    {
        $data = $request->validate(['patient_name' => ['required', 'string', 'max:200'], 'file_number' => ['nullable', 'string', 'max:100'], 'nationality' => ['required', 'integer', 'min:1'], 'room' => ['required', 'integer', 'min:1'], 'procedurs' => ['nullable'], 'doctor' => ['required', 'string', 'max:150'], 'days' => ['required', 'integer', 'min:1'], 'discount' => ['nullable', 'numeric', 'min:0', 'max:100'], 'tools_value' => ['nullable', 'numeric', 'min:0'], 'lang' => ['nullable', 'in:ar,en,1,2'], 'vat' => ['nullable', 'numeric'], 'code_total' => ['nullable', 'numeric'], 'room_price' => ['nullable', 'numeric']]);
        $id = $this->service->calculatorStore($type, $data, 'direct');
        return redirect()->route('modules.admission-calculator.show', [$type, $id])->with('success', __('admission_calculator.created'));
    }

    public function show(string $type, int $id): View
    {
        $record = $this->service->calculatorFind($type, $id);
        abort_if($record === null, 404);
        return view('admission-inpatient.calculator.show', ['record' => $record, 'type' => $type, 'homeRoute' => 'branch.dashboard']);
    }

    public function edit(string $type, int $id): View
    {
        $record = $this->service->calculatorFind($type, $id);
        abort_if($record === null, 404);
        return view('admission-inpatient.calculator.form', $this->service->calculatorOptions($type) + ['row' => $record, 'type' => $type, 'mode' => 'direct', 'homeRoute' => 'branch.dashboard']);
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $this->service->calculatorUpdate($type, $id, $request->validate(['patient_name' => ['required', 'string', 'max:200'], 'file_number' => ['nullable', 'string', 'max:100'], 'nationality' => ['required', 'integer', 'min:1'], 'room' => ['required', 'integer', 'min:1'], 'procedurs' => ['nullable'], 'doctor' => ['required', 'string', 'max:150'], 'days' => ['required', 'integer', 'min:1'], 'discount' => ['nullable', 'numeric', 'min:0', 'max:100'], 'tools_value' => ['nullable', 'numeric', 'min:0'], 'lang' => ['nullable', 'in:ar,en,1,2']]));
        return redirect()->route('modules.admission-calculator.show', [$type, $id])->with('success', 'تم تحديث التسعيرة.');
    }

    public function sms(Request $request, string $type, int $id): RedirectResponse
    {
        $data = $request->validate(['mobile' => ['required', 'string', 'regex:/^\+?[0-9\s-]{9,20}$/'], 'language' => ['nullable', 'in:ar,en']]);
        $this->service->sendCalculatorSms($type, $id, $data['mobile'], $data['language'] ?? 'ar');
        return back()->with('success', 'تم إرسال الرابط.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $this->service->calculatorDelete($type, $id);
        return redirect()->route('modules.admission-calculator.index', $type)->with('success', __('admission_calculator.deleted'));
    }

    public function pdf(string $type, int $id): mixed
    {
        $record = $this->service->calculatorFind($type, $id);
        abort_if($record === null, 404);
        return app(ArabicPdfService::class)->loadView('admission-inpatient.calculator.pdf', ['record' => $record, 'type' => $type])->setPaper('a4')->download('admission-calculator-'.$id.'.pdf');
    }
}
