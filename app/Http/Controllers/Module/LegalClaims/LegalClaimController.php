<?php

namespace App\Http\Controllers\Module\LegalClaims;

use App\Http\Controllers\Controller;
use App\Services\LegalClaims\LegalClaimService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LegalClaimController extends Controller
{
    public function __construct(private readonly LegalClaimService $service) {}

    public function index(Request $request): View
    {
        $filters = ['from' => trim((string) $request->input('from', date('Y-m-d', strtotime('-90 days')))), 'to' => trim((string) $request->input('to', date('Y-m-d'))), 'status_id' => $request->integer('status_id'), 'mobile' => trim((string) $request->input('mobile', '')), 'patient_name' => trim((string) $request->input('patient_name', ''))];
        return view('legal-claims.index', ['claims' => $this->service->list($filters), 'filters' => $filters, 'statuses' => $this->service->lookups()['statuses'], 'homeRoute' => 'branch.dashboard']);
    }

    public function create(): View { return view('legal-claims.create', $this->service->lookups() + ['homeRoute' => 'branch.dashboard']); }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['lawsuit_payment_type_id' => ['required', 'integer'], 'patient_name' => ['required', 'string', 'max:150'], 'file_number' => ['required', 'string', 'max:50'], 'patient_nationality' => ['nullable', 'integer'], 'patient_idno' => ['nullable', 'string', 'max:30'], 'admission_date' => ['nullable', 'date'], 'discharge_date' => ['nullable', 'date'], 'amount_paid' => ['nullable', 'numeric'], 'amount_rest' => ['nullable', 'numeric'], 'liable_name' => ['nullable', 'string', 'max:150'], 'liable_idno' => ['nullable', 'string', 'max:30'], 'liable_mobile' => ['nullable', 'string', 'max:30'], 'service_provided_to_patient' => ['required', 'string', 'max:255'], 'file_1' => ['nullable', 'file', 'max:10240'], 'file_2' => ['nullable', 'file', 'max:10240'], 'file_3' => ['nullable', 'file', 'max:10240'], 'file_4' => ['nullable', 'file', 'max:10240']]);
        $id = $this->service->create($data, $request->allFiles());
        return redirect()->route('modules.legal-claims.show', $id)->with('success', __('legal_claims.created'));
    }

    public function show(int $claim): View { $record = $this->service->find($claim); abort_if($record === null, 404); return view('legal-claims.show', ['record' => $record, 'statuses' => $this->service->lookups()['statuses'], 'timeline' => $this->service->timeline($claim), 'homeRoute' => 'branch.dashboard']); }

    public function action(Request $request, int $claim): RedirectResponse
    {
        $data = $request->validate(['status_id' => ['required', 'integer'], 'details' => ['nullable', 'string', 'max:2000'], 'request_number' => ['nullable', 'string', 'max:100'], 'request_date' => ['nullable', 'date'], 'case_number' => ['nullable', 'string', 'max:100'], 'sessions_number' => ['nullable', 'string', 'max:100'], 'session_summary' => ['nullable', 'string', 'max:2000'], 'sessions_date' => ['nullable', 'date'], 'next_sessions_date' => ['nullable', 'date'], 'lawsuit_request_file' => ['nullable', 'file', 'max:10240'], 'session_1_file' => ['nullable', 'file', 'max:10240']]);
        $this->service->addAction($claim, $data, $request->allFiles());
        return back()->with('success', __('legal_claims.action_added'));
    }

    public function attachment(Request $request, int $claim): RedirectResponse { $request->validate(['file' => ['required', 'file', 'max:10240']]); $this->service->addAttachment($claim, $request->file('file')); return back()->with('success', __('legal_claims.attachment_added')); }
    public function statement(Request $request, int $claim): RedirectResponse { $data = $request->validate(['details' => ['required', 'string', 'max:2000'], 'file' => ['nullable', 'file', 'max:10240']]); $this->service->addStatement($claim, $data['details'], $request->file('file')); return back()->with('success', __('legal_claims.statement_added')); }
    public function download(int $claim, string $kind, ?int $child = null): mixed { return $this->service->download($claim, $kind, $child); }
    public function pdf(int $claim): mixed { $record = $this->service->find($claim); abort_if($record === null, 404); return Pdf::loadView('legal-claims.pdf', ['record' => $record])->setPaper('a4')->download('lawsuit-'.$claim.'.pdf'); }
}
