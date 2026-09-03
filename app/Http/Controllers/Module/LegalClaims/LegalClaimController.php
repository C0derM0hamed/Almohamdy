<?php

namespace App\Http\Controllers\Module\LegalClaims;

use App\Http\Controllers\Controller;
use App\Services\LegalClaims\LegalClaimService;
use App\Services\LegalClaims\ClaimDocumentService;
use App\Services\LegalClaims\ClaimSessionService;
use App\Services\LegalClaims\ClaimStatementService;
use App\Services\LegalClaims\ClaimWaiverService;
use App\Services\Pdf\ArabicPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class LegalClaimController extends Controller
{
    public function __construct(
        private readonly LegalClaimService $service,
        private readonly ClaimSessionService $sessions,
        private readonly ClaimStatementService $statements,
        private readonly ClaimDocumentService $documents,
        private readonly ClaimWaiverService $waivers,
    ) {}

    public function index(Request $request): View
    {
        $statementFilter = (string) $request->input('statement_filter', '');
        $filters = [
            'from' => trim((string) $request->input('from', date('Y-m-d', strtotime('-90 days')))),
            'to' => trim((string) $request->input('to', date('Y-m-d'))),
            'status_id' => $request->integer('status_id'),
            'mobile' => trim((string) $request->input('mobile', '')),
            'patient_name' => trim((string) $request->input('patient_name', '')),
            'statement_filter' => in_array($statementFilter, ['has_statements', 'without_statements'], true) ? $statementFilter : '',
        ];
        return view('legal-claims.index', [
            'claims' => $this->service->list($filters),
            'filters' => $filters,
            'statuses' => $this->service->lookups()['statuses'],
            'statusDashboard' => $this->service->statusDashboard($filters),
            'canCreate' => $this->service->canCreate(),
            'homeRoute' => 'branch.dashboard',
        ]);
    }

    public function create(): View
    {
        abort_unless($this->service->canCreate(), 403);

        return view('legal-claims.create', $this->service->lookups() + ['homeRoute' => 'branch.dashboard']);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->service->canCreate(), 403);
        $data = $request->validate([
            'lawsuit_payment_type_id' => ['required', 'integer'], 'patient_name' => ['required', 'string', 'max:150'], 'file_number' => ['required', 'string', 'max:50'],
            'patient_nationality' => ['nullable', 'string', 'max:20'], 'patient_idno' => ['nullable', 'string', 'max:30'], 'admission_date' => ['nullable', 'date'], 'discharge_date' => ['nullable', 'date'],
            'amount_paid' => ['nullable', 'numeric'], 'amount_rest' => ['nullable', 'numeric'], 'liable_name' => ['nullable', 'string', 'max:150'], 'liable_idno' => ['nullable', 'string', 'max:30'],
            'liable_nationality' => ['nullable', 'string', 'max:20'], 'liable_mobile' => ['nullable', 'string', 'max:30'], 'lawsuit_admission_location_id' => ['nullable', 'integer'],
            'covered_amount' => ['nullable', 'numeric'], 'uncovered_amount' => ['nullable', 'numeric'], 'received_date' => ['nullable', 'date'], 'lawsuit_request_status_id' => ['nullable', 'integer'],
            'lawsuit_rejected_reason_id' => ['nullable', 'integer'], 'rejected_reason' => ['nullable', 'string', 'max:1000'], 'date_type' => ['nullable', 'string', 'max:30'],
            'birth_day' => ['nullable', 'integer', 'between:1,31'], 'birth_month' => ['nullable', 'integer', 'between:1,12'], 'birth_year' => ['nullable', 'integer', 'between:1300,2500'],
            'sexCode' => ['nullable', 'string', 'max:20'], 'contractor_approval_date' => ['nullable', 'date'], 'service_provided_to_patient' => ['required', 'string', 'max:255'],
            'file_1' => ['nullable', 'file', 'max:10240'], 'file_2' => ['nullable', 'file', 'max:10240'], 'file_3' => ['nullable', 'file', 'max:10240'], 'file_4' => ['nullable', 'file', 'max:10240'],
        ]);
        if ((int) $data['lawsuit_payment_type_id'] === 1) {
            validator($data, ['amount_paid' => ['required', 'numeric'], 'amount_rest' => ['required', 'numeric']])->validate();
        }
        if (in_array((int) $data['lawsuit_payment_type_id'], [2, 3], true)) {
            validator($data, ['lawsuit_request_status_id' => ['required', 'integer'], 'received_date' => ['required', 'date']])->validate();
        }
        $id = $this->service->create($data, $request->allFiles());
        return redirect()->route('modules.legal-claims.show', $id)->with('success', __('legal_claims.created'));
    }

    public function show(int $claim): View { $lookups = $this->service->lookups(); $record = $this->service->find($claim); abort_if($record === null, 404); return view('legal-claims.show', ['record' => $record, 'statuses' => $lookups['statuses'], 'suspensionStatuses' => $lookups['suspendStatuses'], 'timeline' => $this->service->timeline($claim), 'homeRoute' => 'branch.dashboard']); }

    public function action(Request $request, int $claim): RedirectResponse
    {
        $data = $request->validate(['status_id' => ['required', 'integer'], 'details' => ['nullable', 'string', 'max:2000'], 'applicant' => ['nullable', 'string', 'max:150'], 'request_number' => ['nullable', 'string', 'max:100'], 'request_date' => ['nullable', 'date'], 'case_number' => ['nullable', 'string', 'max:100'], 'sessions_number' => ['nullable', 'string', 'max:100'], 'session_summary' => ['nullable', 'string', 'max:2000'], 'sessions_date' => ['nullable', 'date'], 'next_sessions_date' => ['nullable', 'date'], 'lawsuit_request_file' => ['nullable', 'file', 'max:10240'], 'session_1_file' => ['nullable', 'file', 'max:10240'], 'judgment_instrument' => ['nullable', 'file', 'max:10240']]);
        $this->sessions->record($claim, $data, $request->allFiles());
        return back()->with('success', __('legal_claims.action_added'));
    }

    public function attachment(Request $request, int $claim): RedirectResponse { $request->validate(['file' => ['required', 'file', 'max:10240']]); $this->documents->attach($claim, $request->file('file')); return back()->with('success', __('legal_claims.attachment_added')); }
    public function statement(Request $request, int $claim): RedirectResponse { $data = $request->validate(['details' => ['required', 'string', 'max:2000'], 'file' => ['nullable', 'file', 'max:10240']]); $this->statements->create($claim, $data['details'], $request->file('file')); return back()->with('success', __('legal_claims.statement_added')); }
    public function installment(Request $request, int $claim): RedirectResponse { $data = $request->validate(['installment_date' => ['required', 'date']]); $this->waivers->addInstallment($claim, $data['installment_date']); return back()->with('success', __('legal_claims.installment_added')); }
    public function paid(int $claim, int $installment): RedirectResponse { $this->waivers->markPaid($claim, $installment); return back()->with('success', __('legal_claims.installment_paid')); }
    public function suspension(Request $request, int $claim): RedirectResponse { $data = $request->validate(['status_id' => ['required', 'integer'], 'total_amount' => ['nullable', 'numeric'], 'amount_waived' => ['nullable', 'numeric'], 'file' => ['nullable', 'file', 'max:10240']]); $this->waivers->requestWaiver($claim, $data, $request->file('file')); return back()->with('success', __('legal_claims.suspension_added')); }
    public function download(int $claim, string $kind, ?int $child = null): mixed { return $this->service->download($claim, $kind, $child); }
    public function pdf(int $claim): mixed { $record = $this->service->find($claim); abort_if($record === null, 404); return app(ArabicPdfService::class)->loadView('legal-claims.pdf', ['record' => $record])->setPaper('a4')->download('lawsuit-'.$claim.'.pdf'); }
    public function suspensionPdf(int $claim): mixed { $record = $this->service->find($claim); abort_if($record === null, 404); return app(ArabicPdfService::class)->loadView('legal-claims.suspension-pdf', ['record' => $record])->setPaper('a4')->download('lawsuit-suspension-'.$claim.'.pdf'); }
    public function guarantee(Request $request): JsonResponse { abort_unless($this->service->canCreate(), 403); return response()->json(['data' => $this->service->paymentGuarantee((string) $request->query('file_number', ''))]); }
    public function claimSheetPdf(int $claim): mixed { $record = $this->service->find($claim); abort_if($record === null, 404); return app(ArabicPdfService::class)->loadView('legal-claims.claim-sheet-pdf', ['record' => $record])->setPaper('a4')->download('lawsuit-claim-sheet-'.$claim.'.pdf'); }
}
