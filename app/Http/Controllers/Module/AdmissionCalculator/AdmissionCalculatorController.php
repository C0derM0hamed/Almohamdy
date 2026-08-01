<?php

namespace App\Http\Controllers\Module\AdmissionCalculator;

use App\Http\Controllers\Controller;
use App\Services\AdmissionCalculator\AdmissionCalculatorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdmissionCalculatorController extends Controller
{
    public function __construct(private readonly AdmissionCalculatorService $service) {}
    public function index(Request $request, string $type = 'standard'): View { $filters = ['file_number' => trim((string) $request->input('file_number', '')), 'from' => trim((string) $request->input('from', '')), 'to' => trim((string) $request->input('to', ''))]; return view('admission-calculator.index', ['type' => $type, 'records' => $this->service->list($type, $filters), 'filters' => $filters, 'homeRoute' => 'branch.dashboard']); }
    public function create(string $type = 'standard'): View { return view('admission-calculator.create', $this->service->options() + ['type' => $type, 'homeRoute' => 'branch.dashboard']); }
    public function store(Request $request, string $type = 'standard'): RedirectResponse { $data = $request->validate(['patient_name' => ['required', 'string', 'max:200'], 'file_number' => ['nullable', 'string', 'max:16'], 'nationality' => ['required', 'integer', 'min:1'], 'room' => ['required', 'integer', 'min:1'], 'procedurs' => ['nullable', 'string', 'max:255'], 'doctor' => ['required', 'string', 'max:150'], 'days' => ['required', 'integer', 'min:1'], 'discount' => ['nullable', 'numeric', 'min:0'], 'tools_value' => ['nullable', 'numeric', 'min:0'], 'lang' => ['nullable', 'in:ar,en'], 'vat' => ['nullable', 'numeric'], 'code_total' => ['nullable', 'string', 'max:12'], 'room_price' => ['nullable', 'numeric']]); $id = $this->service->create($type, $data); return redirect()->route('modules.admission-calculator.show', [$type, $id])->with('success', __('admission_calculator.created')); }
    public function show(string $type, int $id): View { $record = $this->service->find($type, $id); abort_if($record === null, 404); return view('admission-calculator.show', ['record' => $record, 'type' => $type, 'homeRoute' => 'branch.dashboard']); }
    public function destroy(string $type, int $id): RedirectResponse { $this->service->delete($type, $id); return redirect()->route('modules.admission-calculator.index', $type)->with('success', __('admission_calculator.deleted')); }
    public function pdf(string $type, int $id): mixed { $record = $this->service->find($type, $id); abort_if($record === null, 404); return Pdf::loadView('admission-calculator.pdf', ['record' => $record, 'type' => $type])->setPaper('a4')->download('admission-calculator-'.$id.'.pdf'); }
}
